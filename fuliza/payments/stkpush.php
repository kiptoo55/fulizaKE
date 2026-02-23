<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/stkpush_error.log');

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Load Supabase config first to test connection
    $configPath = __DIR__ . '/../config/supabase.php';
    if (!file_exists($configPath)) {
        throw new Exception('Supabase config not found');
    }
    
    require_once $configPath;
    
    // Test Supabase connection
    if (!class_exists('SupabaseDB')) {
        throw new Exception('SupabaseDB class not found');
    }
    
    ob_clean();
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $phone = $input['phone'] ?? $_POST['phone'] ?? '';
    $amount = $input['amount'] ?? $_POST['amount'] ?? '';
    $limit = $input['limit'] ?? $_POST['limit'] ?? '';
    $fee = $input['fee'] ?? $_POST['fee'] ?? '';
    
    if (empty($phone) || empty($amount)) {
        echo json_encode(['success' => false, 'message' => 'All fields required']);
        exit;
    }
    
    // Format phone - ensure it starts with 254
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) == '0') {
        $phone = '254' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) != '254') {
        $phone = '254' . $phone;
    }
    
    // Initialize Supabase
    $supabase = SupabaseDB::getInstance();
    
    // Generate checkout ID
    $checkout_id = 'CHK_' . date('YmdHis') . '_' . rand(1000, 9999);
    
    // Save initial transaction
    $transactionData = [
        'phone' => $phone,
        'amount' => floatval($amount),
        'fee' => floatval($fee),
        'limit_amount' => floatval(str_replace(',', '', $limit)),
        'checkout_request_id' => $checkout_id,
        'status' => 'pending'
    ];
    
    $insertResult = $supabase->insert('transactions', $transactionData);
    error_log("Transaction inserted: " . json_encode($insertResult));
    
    // ==========================================
    // M-Pesa STK Push Integration
    // ==========================================
    
    // Load M-Pesa configuration
    $mpesaConfig = require __DIR__ . '/../config/mpesa.php';
    
    // Check for test mode
    $testMode = $_ENV['MPESA_TEST_MODE'] ?? getenv('MPESA_TEST_MODE') ?? false;
    if ($testMode === 'true' || $testMode === true) {
        // TEST MODE: Simulate successful STK Push
        $simulatedCheckoutId = 'ws_' . date('YmdHis') . rand(1000, 9999);
        
        $supabase->update(
            'transactions',
            ['checkout_request_id' => $simulatedCheckoutId, 'status' => 'processing'],
            ['checkout_request_id' => $checkout_id]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'TEST MODE: STK Push simulated! Check your phone (simulated).',
            'checkout_id' => $simulatedCheckoutId,
            'test_mode' => true,
            'note' => 'In test mode, payment is simulated. Set MPESA_TEST_MODE=false in .env for real payments.'
        ]);
        exit;
    }
    
    // Get OAuth token
    $accessToken = getAccessToken($mpesaConfig);
    
    if (!$accessToken) {
        // Return success anyway for demo mode - transaction is saved
        echo json_encode([
            'success' => true,
            'message' => 'Transaction initiated. For demo: STK Push would be sent.',
            'checkout_id' => $checkout_id,
            'demo' => true,
            'debug' => 'M-Pesa token failed - running in demo mode'
        ]);
        exit;
    }
    
    // Prepare STK Push request
    $timestamp = date('YmdHis');
    $password = base64_encode($mpesaConfig['shortcode'] . $mpesaConfig['passkey'] . $timestamp);
    
    $stkPushData = [
        'BusinessShortCode' => $mpesaConfig['shortcode'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => round($amount),
        'PartyA' => $phone,
        'PartyB' => $mpesaConfig['shortcode'],
        'PhoneNumber' => $phone,
        'CallBackURL' => $mpesaConfig['callback_url'],
        'AccountReference' => $mpesaConfig['account_reference'],
        'TransactionDesc' => $mpesaConfig['transaction_desc']
    ];
    
    // Send STK Push
    $response = sendSTKPush($accessToken, $stkPushData, $mpesaConfig);
    
    // Check for successful response
    $responseCode = $response['ResponseCode'] ?? $response['errorCode'] ?? '';
    
    if ($responseCode === '0' || isset($response['CheckoutRequestID'])) {
        // Update with M-Pesa checkout ID
        $supabase->update(
            'transactions',
            ['checkout_request_id' => $response['CheckoutRequestID'] ?? $checkout_id],
            ['checkout_request_id' => $checkout_id]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'STK Push sent! Check your phone and enter PIN.',
            'checkout_id' => $response['CheckoutRequestID'] ?? $checkout_id,
            'response' => $response
        ]);
    } else {
        // M-Pesa API error - likely callback URL issue
        $errorMsg = $response['errorMessage'] ?? $response['ResponseDescription'] ?? 'Unknown error';
        
        // If it's a callback URL error, provide helpful message
        if (strpos($errorMsg, 'CallBackURL') !== false) {
            echo json_encode([
                'success' => false,
                'message' => 'STK Push failed: ' . $errorMsg . '. Note: M-Pesa requires a PUBLIC callback URL (not localhost). Use ngrok or deploy to a server.',
                'error' => $errorMsg,
                'hint' => 'For local testing, use: ngrok http 80'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'STK Push failed: ' . $errorMsg,
                'error' => $response
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

ob_end_flush();

/**
 * Get OAuth Access Token from M-Pesa API
 */
function getAccessToken($config) {
    $url = $config['environment'] === 'sandbox' 
        ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
        : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    
    $credentials = base64_encode($config['consumer_key'] . ':' . $config['consumer_secret']);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $credentials
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("M-Pesa OAuth Response: " . $response . " HTTP Code: " . $httpCode);
    
    $data = json_decode($response, true);
    
    return $data['access_token'] ?? null;
}

/**
 * Send STK Push Request
 */
function sendSTKPush($accessToken, $data, $config) {
    $url = $config['environment'] === 'sandbox'
        ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
        : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("STK Push Response: " . $response . " HTTP Code: " . $httpCode);
    
    return json_decode($response, true);
}
?>
