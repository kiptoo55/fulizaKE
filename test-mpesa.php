<?php
// Test file to debug M-Pesa connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== FULIZABOOST CONFIG TEST ===\n\n";

// Load aut and dotenv FIRSToload
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['SUPABASE_URL', 'SUPABASE_ANON_KEY', 'SUPABASE_SERVICE_KEY', 
                   'MPESA_CONSUMER_KEY', 'MPESA_CONSUMER_SECRET', 'MPESA_PASSKEY']);

echo "✓ Dotenv loaded\n";

// Supabase Config
$supabaseUrl = $_ENV['SUPABASE_URL'] ?? getenv('SUPABASE_URL');
$supabaseAnonKey = $_ENV['SUPABASE_ANON_KEY'] ?? getenv('SUPABASE_ANON_KEY');
$supabaseServiceKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? getenv('SUPABASE_SERVICE_KEY');

echo "\n--- Supabase Config ---\n";
echo "SUPABASE_URL: " . ($supabaseUrl ?: 'NOT SET') . "\n";
echo "SUPABASE_ANON_KEY: " . ($supabaseAnonKey ? 'SET (' . strlen($supabaseAnonKey) . ' chars)' : 'NOT SET') . "\n";
echo "SUPABASE_SERVICE_KEY: " . ($supabaseServiceKey ? 'SET (' . strlen($supabaseServiceKey) . ' chars)' : 'NOT SET') . "\n";

// Test SupabaseDB
require_once __DIR__ . '/config/supabase.php';

if (class_exists('SupabaseDB')) {
    echo "\n✓ SupabaseDB class loaded\n";
    try {
        $db = SupabaseDB::getInstance();
        echo "✓ SupabaseDB instance created\n";
        
        // Test a simple query to verify connection
        echo "\n--- Testing Supabase Connection ---\n";
        $testResult = $db->select('users', [], null, 1, false);
        if (isset($testResult['error'])) {
            echo "⚠ Supabase query note: " . $testResult['error'] . "\n";
        } else {
            echo "✓ Supabase connection successful!\n";
        }
    } catch (Exception $e) {
        echo "✗ SupabaseDB error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ SupabaseDB class not found\n";
}

echo "\n--- M-Pesa Config ---\n";

// Now load M-Pesa config (env vars are already loaded)
$mpesaConfig = require __DIR__ . '/config/mpesa.php';

echo "Environment: " . ($mpesaConfig['environment'] ?: 'sandbox') . "\n";
echo "Shortcode: " . $mpesaConfig['shortcode'] . "\n";
echo "Consumer Key: " . (strlen($mpesaConfig['consumer_key']) > 0 ? 'SET (' . strlen($mpesaConfig['consumer_key']) . ' chars)' : 'NOT SET') . "\n";
echo "Consumer Secret: " . (strlen($mpesaConfig['consumer_secret']) > 0 ? 'SET (' . strlen($mpesaConfig['consumer_secret']) . ' chars)' : 'NOT SET') . "\n";
echo "Passkey: " . (strlen($mpesaConfig['passkey']) > 0 ? 'SET (' . strlen($mpesaConfig['passkey']) . ' chars)' : 'NOT SET') . "\n";
echo "Callback URL: " . $mpesaConfig['callback_url'] . "\n";

// Test M-Pesa API if credentials are set
if (strlen($mpesaConfig['consumer_key']) > 0 && strlen($mpesaConfig['consumer_secret']) > 0) {
    echo "\n--- Testing M-Pesa OAuth ---\n";
    
    $credentials = base64_encode($mpesaConfig['consumer_key'] . ':' . $mpesaConfig['consumer_secret']);
    
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    
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
    
    echo "HTTP Code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
    
    $result = json_decode($response, true);
    if (isset($result['access_token'])) {
        echo "✓ M-Pesa OAuth successful!\n";
    } else if (isset($result['errorMessage'])) {
        echo "✗ M-Pesa Error: " . $result['errorMessage'] . "\n";
    }
} else {
    echo "\n✗ M-Pesa credentials not configured\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
