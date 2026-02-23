<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class SupabaseDB {
    private $supabaseUrl;
    private $anonKey;
    private $serviceKey;
    private $client;
    private static $instance = null;
    
    private function __construct() {
        $this->supabaseUrl = $_ENV['SUPABASE_URL'] ?? getenv('SUPABASE_URL');
        $this->anonKey = $_ENV['SUPABASE_ANON_KEY'] ?? getenv('SUPABASE_ANON_KEY');
        $this->serviceKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? getenv('SUPABASE_SERVICE_KEY');
        
        $this->client = new Client([
            'base_uri' => $this->supabaseUrl . '/rest/v1/',
            'timeout' => 30,
        ]);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Get headers for anon requests (public data)
    private function getAnonHeaders() {
        return [
            'apikey' => $this->anonKey,
            'Authorization' => 'Bearer ' . $this->anonKey,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ];
    }
    
    // Get headers for service role requests (admin operations)
    private function getServiceHeaders() {
        return [
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ];
    }
    
    // Select records
    public function select($table, $conditions = [], $orderBy = null, $limit = null, $useServiceKey = false) {
        $headers = $useServiceKey ? $this->getServiceHeaders() : $this->getAnonHeaders();
        
        $queryParams = [];
        
        // Build filter conditions
        foreach ($conditions as $column => $value) {
            $queryParams[$column] = 'eq.' . $value;
        }
        
        if ($orderBy) {
            $queryParams['order'] = $orderBy['column'] . '.' . ($orderBy['direction'] ?? 'asc');
        }
        
        if ($limit) {
            $queryParams['limit'] = $limit;
        }
        
        try {
            $response = $this->client->get($table, [
                'headers' => $headers,
                'query' => $queryParams
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            error_log('SupabaseDB select error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    // Insert record
    public function insert($table, $data, $useServiceKey = true) {
        $headers = $useServiceKey ? $this->getServiceHeaders() : $this->getAnonHeaders();
        
        try {
            $response = $this->client->post($table, [
                'headers' => $headers,
                'json' => $data
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            error_log('SupabaseDB insert error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    // Insert multiple records
    public function insertBatch($table, $data, $useServiceKey = true) {
        $headers = $useServiceKey ? $this->getServiceHeaders() : $this->getAnonHeaders();
        
        try {
            $response = $this->client->post($table, [
                'headers' => $headers,
                'json' => $data
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            error_log('SupabaseDB insertBatch error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    // Update record
    public function update($table, $data, $conditions, $useServiceKey = true) {
        $headers = $useServiceKey ? $this->getServiceHeaders() : $this->getAnonHeaders();
        
        // Build filter string
        $filters = [];
        foreach ($conditions as $column => $value) {
            $filters[] = $column . '.eq.' . $value;
        }
        $filterStr = implode(',', $filters);
        
        try {
            // Supabase uses PATCH for updates with filters in query string
            $response = $this->client->patch($table . '?or=(' . $filterStr . ')', [
                'headers' => $headers,
                'json' => $data
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            error_log('SupabaseDB update error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    // Delete record
    public function delete($table, $conditions, $useServiceKey = true) {
        $headers = $useServiceKey ? $this->getServiceHeaders() : $this->getAnonHeaders();
        
        // Build filter string
        $filters = [];
        foreach ($conditions as $column => $value) {
            $filters[] = $column . '.eq.' . $value;
        }
        $filterStr = implode(',', $filters);
        
        try {
            $response = $this->client->delete($table . '?or=(' . $filterStr . ')', [
                'headers' => $headers
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            error_log('SupabaseDB delete error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    // Execute raw SQL via PostgREST (for complex queries)
    public function rpc($functionName, $params = [], $useServiceKey = true) {
        $headers = $useServiceKey ? $this->getServiceHeaders() : $this->getAnonHeaders();
        
        try {
            $response = $this->client->post('rpc/' . $functionName, [
                'headers' => $headers,
                'json' => $params
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            error_log('SupabaseDB rpc error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    // Check if a record exists
    public function exists($table, $conditions, $useServiceKey = false) {
        $result = $this->select($table, $conditions, null, 1, $useServiceKey);
        return !empty($result) && !isset($result['error']);
    }
    
    // Get single record
    public function get($table, $conditions, $useServiceKey = false) {
        $result = $this->select($table, $conditions, null, 1, $useServiceKey);
        if (isset($result[0])) {
            return $result[0];
        }
        return null;
    }
    
    // Count records
    public function count($table, $conditions = [], $useServiceKey = false) {
        $headers = $useServiceKey ? $this->getServiceHeaders() : $this->getAnonHeaders();
        $headers['Prefer'] = 'return=minimal';
        
        $queryParams = ['count' => 'exact'];
        
        foreach ($conditions as $column => $value) {
            $queryParams[$column] = 'eq.' . $value;
        }
        
        try {
            $response = $this->client->get($table, [
                'headers' => $headers,
                'query' => $queryParams
            ]);
            
            // Get count from response headers
            $contentRange = $response->getHeaderLine('Content-Range');
            if ($contentRange) {
                preg_match('/\/(.*)/', $contentRange, $matches);
                if (isset($matches[1])) {
                    return (int) $matches[1];
                }
            }
            
            return 0;
        } catch (Exception $e) {
            error_log('SupabaseDB count error: ' . $e->getMessage());
            return 0;
        }
    }
}
