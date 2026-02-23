<?php
// M-Pesa Transaction Timeout Handler
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/supabase.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/mpesa_timeout.log');

// Get timeout data
$timeoutData = file_get_contents('php://input');
$data = json_decode($timeoutData, true);

// Log the timeout
error_log("Timeout received: " . $timeoutData);

// Initialize Supabase
$supabase = SupabaseDB::getInstance();

// Process timeout
if (isset($data['Body']['stkCallback'])) {
    $callback = $data['Body']['stkCallback'];
    $checkoutRequestID = $callback['CheckoutRequestID'] ?? '';
    $resultCode = $callback['ResultCode'] ?? '';
    $resultDesc = $callback['ResultDesc'] ?? '';
    
    if ($resultCode == '0' || $resultCode == 0) {
        // Transaction still went through - process normally
        // Update transaction status
        $updateData = [
            'status' => 'completed',
            'result_desc' => $resultDesc,
            'updated_at' => date('c')
        ];
        
        $supabase->update(
            'transactions',
            $updateData,
            ['checkout_request_id' => $checkoutRequestID]
        );
    } else {
        // Mark as timed out
        $updateData = [
            'status' => 'timeout',
            'result_desc' => $resultDesc,
            'updated_at' => date('c')
        ];
        
        $supabase->update(
            'transactions',
            $updateData,
            ['checkout_request_id' => $checkoutRequestID]
        );
        
        error_log("Transaction timed out: " . $checkoutRequestID);
    }
}

// Respond to M-Pesa
header('Content-Type: application/json');
echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Success'
]);
?>
