<?php
// M-Pesa Daraja API Configuration
// Load from environment variables - support both $_ENV and getenv()

$mpesaEnvironment = $_ENV['MPESA_ENVIRONMENT'] ?? getenv('MPESA_ENVIRONMENT') ?? 'sandbox';
$mpesaConsumerKey = $_ENV['MPESA_CONSUMER_KEY'] ?? getenv('MPESA_CONSUMER_KEY') ?? '';
$mpesaConsumerSecret = $_ENV['MPESA_CONSUMER_SECRET'] ?? getenv('MPESA_CONSUMER_SECRET') ?? '';
$mpesaShortcode = $_ENV['MPESA_SHORTCODE'] ?? getenv('MPESA_SHORTCODE') ?? '174379';
$mpesaPasskey = $_ENV['MPESA_PASSKEY'] ?? getenv('MPESA_PASSKEY') ?? '';
$mpesaCallbackUrl = $_ENV['MPESA_CALLBACK_URL'] ?? getenv('MPESA_CALLBACK_URL') ?? 'http://localhost/fuliza+/fuliza/payments/callback.php';
$mpesaTimeoutUrl = $_ENV['MPESA_TIMEOUT_URL'] ?? getenv('MPESA_TIMEOUT_URL') ?? 'http://localhost/fuliza+/fuliza/payments/timeout.php';

return [
    // Environment: sandbox or production
    'environment' => $mpesaEnvironment,
    
    // Consumer credentials from developer portal
    'consumer_key' => $mpesaConsumerKey,
    'consumer_secret' => $mpesaConsumerSecret,
    
    // Business details
    'shortcode' => $mpesaShortcode,
    'passkey' => $mpesaPasskey,
    
    // Callback URLs
    'callback_url' => $mpesaCallbackUrl,
    'timeout_url' => $mpesaTimeoutUrl,
    
    // Transaction details
    'transaction_desc' => 'FulizaBoost Limit Increase',
    'account_reference' => 'FULIZABOOST'
];
?>
