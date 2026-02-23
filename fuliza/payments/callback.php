<?php
// M-Pesa Payment Callback Handler
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/supabase.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/mpesa_callback.log');

// Get callback data
$callbackData = file_get_contents('php://input');
$data = json_decode($callbackData, true);

// Log the callback
error_log("=== M-Pesa Callback Received ===");
error_log("Raw data: " . $callbackData);

// Initialize Supabase
$supabase = SupabaseDB::getInstance();

// Process callback
if (isset($data['Body']['stkCallback'])) {
    $callback = $data['Body']['stkCallback'];
    
    $checkoutRequestID = $callback['CheckoutRequestID'] ?? '';
    $resultCode = $callback['ResultCode'] ?? '';
    $resultDesc = $callback['ResultDesc'] ?? '';
    
    error_log("CheckoutRequestID: " . $checkoutRequestID);
    error_log("ResultCode: " . $resultCode);
    error_log("ResultDesc: " . $resultDesc);
    
    if ($resultCode == 0) {
        // Successful transaction
        $metadata = $callback['CallbackMetadata']['Item'] ?? [];
        
        // Extract values
        $amount = 0;
        $mpesaReceipt = '';
        $phone = '';
        $transactionDate = '';
        
        foreach ($metadata as $item) {
            switch ($item['Name'] ?? '') {
                case 'Amount':
                    $amount = $item['Value'] ?? 0;
                    break;
                case 'MpesaReceiptNumber':
                    $mpesaReceipt = $item['Value'] ?? '';
                    break;
                case 'PhoneNumber':
                    $phone = $item['Value'] ?? '';
                    break;
                case 'TransactionDate':
                    $transactionDate = $item['Value'] ?? '';
                    break;
            }
        }
        
        error_log("Amount: " . $amount);
        error_log("Receipt: " . $mpesaReceipt);
        error_log("Phone: " . $phone);
        
        // Update transaction in Supabase
        $updateData = [
            'status' => 'completed',
            'mpesa_receipt' => $mpesaReceipt,
            'result_desc' => $resultDesc,
            'transaction_date' => $transactionDate ? date('Y-m-d H:i:s', strtotime($transactionDate)) : date('c'),
            'updated_at' => date('c')
        ];
        
        try {
            $updateResult = $supabase->update(
                'transactions',
                $updateData,
                ['checkout_request_id' => $checkoutRequestID]
            );
            
            error_log("Transaction updated successfully: " . $mpesaReceipt);
            
            // Get transaction details for live feed
            try {
                $transaction = $supabase->select(
                    'transactions',
                    ['checkout_request_id' => $checkoutRequestID]
                );
                
                if (!empty($transaction)) {
                    // Generate masked phone
                    $maskedPhone = substr($transaction[0]['phone'], 0, 4) . '****' . substr($transaction[0]['phone'], -2);
                    
                    // Get first letter for avatar
                    $userInitial = strtoupper(substr($transaction[0]['phone'], -1, 1));
                    
                    // Add to live feed
                    $liveFeedData = [
                        'phone_masked' => $maskedPhone,
                        'amount_boosted' => $transaction[0]['limit_amount'],
                        'user_initial' => $userInitial,
                        'created_at' => date('c')
                    ];
                    
                    $supabase->insert('live_feed', $liveFeedData);
                    error_log("Added to live feed");
                }
            } catch (Exception $e) {
                error_log("Live feed error: " . $e->getMessage());
            }
            
        } catch (Exception $e) {
            error_log("Update error: " . $e->getMessage());
        }
        
    } else {
        // Failed transaction
        $updateData = [
            'status' => 'failed',
            'result_desc' => $resultDesc,
            'updated_at' => date('c')
        ];
        
        try {
            $supabase->update(
                'transactions',
                $updateData,
                ['checkout_request_id' => $checkoutRequestID]
            );
            error_log("Transaction failed: " . $resultDesc);
        } catch (Exception $e) {
            error_log("Failed update error: " . $e->getMessage());
        }
    }
} else {
    error_log("Invalid callback data received");
}

// Respond to M-Pesa
header('Content-Type: application/json');
echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Success'
]);
?>
