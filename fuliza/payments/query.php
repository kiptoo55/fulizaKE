<?php
// Turn off all error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

// Clear any previous output
ob_clean();

// Set JSON header FIRST
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Now try to include the config - but catch any errors
try {
    // Define a safe path
    $configPath = __DIR__ . '/../config/supabase.php';
    
    if (!file_exists($configPath)) {
        throw new Exception('Config file not found');
    }
    
    require_once $configPath;
    
    // Check if class exists
    if (!class_exists('SupabaseDB')) {
        throw new Exception('SupabaseDB class not found');
    }
    
    // Get checkout ID
    $checkout_id = $_POST['checkout_id'] ?? $_GET['checkout_id'] ?? '';
    
    if (empty($checkout_id)) {
        echo json_encode(['success' => false, 'message' => 'Checkout ID required']);
        exit;
    }
    
    $supabase = SupabaseDB::getInstance();
    
    // Query transaction
    $result = $supabase->select('transactions', ['checkout_request_id' => $checkout_id]);
    
    if (!empty($result) && is_array($result) && isset($result[0])) {
        $transaction = $result[0];
        echo json_encode([
            'success' => true,
            'status' => $transaction['status'] ?? 'pending',
            'receipt' => $transaction['mpesa_receipt'] ?? ''
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'status' => 'pending',
            'message' => 'Processing...'
        ]);
    }
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
?>