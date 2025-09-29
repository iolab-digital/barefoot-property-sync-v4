<?php
/**
 * Barefoot API Integration Class
 * Handles SOAP communication with Barefoot Property Management System
 * 
 * @package BarefootPropertyListings
 * @since 1.1.0
 */

class Barefoot_API {
    
    private $soap_client;
    private $endpoint;
    private $username;
    private $password;
    private $account;
    
    public function __construct() {
        $this->endpoint = BAREFOOT_API_ENDPOINT;
        $this->username = BAREFOOT_API_USERNAME;
        $this->password = BAREFOOT_API_PASSWORD;
        $this->account = BAREFOOT_API_ACCOUNT; // Corrected parameter
        
        $this->init_soap_client();
    }
    
    /**
     * Initialize SOAP client
     */
    private function init_soap_client() {
        try {
            if (!extension_loaded('soap')) {
                throw new Exception('PHP SOAP extension is not enabled');
            }
            
            $options = array(
                'soap_version' => SOAP_1_2,
                'exceptions' => true,
                'trace' => 1,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => 30,
                'user_agent' => 'WordPress Barefoot Plugin/' . BAREFOOT_VERSION
            );
            
            $this->soap_client = new SoapClient($this->endpoint . '?WSDL', $options);
            
        } catch (Exception $e) {
            error_log('Barefoot SOAP Client Error: ' . $e->getMessage());
            $this->soap_client = null;
        }
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (!$this->soap_client) {
            return array(
                'success' => false,
                'message' => 'SOAP client not initialized. Check if PHP SOAP extension is enabled.'
            );
        }
        
        try {
            // Test with GetUrlTest method
            $response = $this->soap_client->GetUrlTest();
            
            if (isset($response->GetUrlTestResult)) {
                // Get available functions count
                $functions = $this->soap_client->__getFunctions();
                
                return array(
                    'success' => true,
                    'message' => 'Successfully connected to Barefoot API. Found ' . count($functions) . ' available API methods.',
                    'functions_count' => count($functions),
                    'endpoint' => $response->GetUrlTestResult
                );
            }
            
            return array(
                'success' => false,
                'message' => 'Connection test failed - no response from GetUrlTest'
            );
            
        } catch (SoapFault $e) {
            return array(
                'success' => false,
                'message' => 'SOAP Fault: ' . $e->getMessage()
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Connection Error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get authentication parameters for API calls
     */
    private function get_auth_params() {
        return array(
            'username' => $this->username,
            'password' => $this->password,
            'barefootAccount' => $this->account // Using corrected account parameter
        );
    }
    
    /**
     * Get all properties from Barefoot API
     * Uses multiple methods due to GetAllProperty returning "Custom method" response
     */
    public function get_all_properties() {
        if (!$this->soap_client) {
            return array('success' => false, 'message' => 'SOAP client not available');
        }
        
        try {
            $params = $this->get_auth_params();
            
            error_log('Barefoot API: Attempting property retrieval with account: ' . $this->account);
            
            // First, try GetAllProperty method
            $response = $this->soap_client->GetAllProperty($params);
            
            if (isset($response->GetAllPropertyResult)) {
                $result = $response->GetAllPropertyResult;
                
                // Check if we have actual property data
                $properties = array();
                
                if (isset($result->PROPERTIES) && isset($result->PROPERTIES->PROPERTY)) {
                    $property_data = $result->PROPERTIES->PROPERTY;
                    $properties = is_array($property_data) ? $property_data : array($property_data);
                    error_log('Barefoot API: Found ' . count($properties) . ' properties in PROPERTIES container');
                } elseif (isset($result->any) && !empty($result->any)) {
                    $properties = $this->parse_xml_properties($result->any);
                    error_log('Barefoot API: Found ' . count($properties) . ' properties in XML data');
                }
                
                if (count($properties) > 0) {
                    return array(
                        'success' => true,
                        'data' => $properties,
                        'count' => count($properties),
                        'method_used' => 'GetAllProperty'
                    );
                }
            }
            
            // If GetAllProperty doesn't return data, try alternative methods
            error_log('Barefoot API: GetAllProperty returned no data - trying alternative methods');
            return $this->try_alternative_property_methods();
            
        } catch (SoapFault $e) {
            error_log('Barefoot GetAllProperty SOAP Fault: ' . $e->getMessage());
            return array('success' => false, 'message' => 'SOAP Fault: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('Barefoot GetAllProperty Error: ' . $e->getMessage());
            return array('success' => false, 'message' => 'API Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Try alternative methods to retrieve properties
     */
    private function try_alternative_property_methods() {
        $params = $this->get_auth_params();
        $properties = array();
        
        // Try methods that are known to work
        $working_methods = array(
            'GetProperty',
            'GetPropertyExt',
            'GetLastUpdatedProperty'
        );
        
        foreach ($working_methods as $method) {
            try {
                error_log("Barefoot API: Trying method: {$method}");
                $response = $this->soap_client->$method($params);
                
                $result_key = $method . 'Result';
                if (isset($response->$result_key)) {
                    $result = $response->$result_key;
                    
                    if (isset($result->any) && !empty($result->any)) {
                        $method_properties = $this->parse_xml_properties($result->any);
                        if (!empty($method_properties)) {
                            $properties = array_merge($properties, $method_properties);
                            error_log("Barefoot API: Found " . count($method_properties) . " properties using {$method}");
                        }
                    }
                }
                
            } catch (Exception $e) {
                error_log("Barefoot API: Method {$method} failed: " . $e->getMessage());
                continue;
            }
        }
        
        // Try to get properties by ID range if no bulk methods work
        if (empty($properties)) {
            $properties = $this->discover_properties_by_id();
        }
        
        if (count($properties) > 0) {
            // Remove duplicates
            $properties = $this->remove_duplicate_properties($properties);
            
            return array(
                'success' => true,
                'data' => $properties,
                'count' => count($properties),
                'method_used' => 'Alternative methods'
            );
        }
        
        // Return successful but empty result
        return array(
            'success' => true,
            'data' => array(),
            'count' => 0,
            'method_used' => 'Multiple methods attempted',
            'message' => 'API connection successful but no properties found. Account may not have properties configured or may require additional permissions.'
        );
    }
    
    /**
     * Discover properties by testing property IDs
     */
    private function discover_properties_by_id() {
        $params = $this->get_auth_params();
        $properties = array();
        
        // Test first 20 property IDs
        for ($id = 1; $id <= 20; $id++) {
            try {
                $property_params = array_merge($params, array('addressid' => $id));
                $response = $this->soap_client->GetPropertyInfoById($property_params);
                
                if (isset($response->GetPropertyInfoByIdResult->any)) {
                    $xml_data = $response->GetPropertyInfoByIdResult->any;
                    
                    // Check if it's a successful response
                    if (strpos($xml_data, '<Success>true</Success>') !== false) {
                        error_log("Barefoot API: Found existing property with ID: {$id}");
                        
                        $property = $this->create_property_from_id($id, $xml_data);
                        if ($property) {
                            $properties[] = $property;
                        }
                    }
                }
                
            } catch (Exception $e) {
                continue;
            }
            
            // Limit to prevent too many API calls
            if (count($properties) >= 10) {
                break;
            }
        }
        
        return $properties;
    }
    
    /**
     * Create property object from ID and XML data
     */
    private function create_property_from_id($id, $xml_data) {
        $property = new stdClass();
        $property->PropertyID = $id;
        $property->Name = 'Property ' . $id;
        $property->Description = 'Property retrieved by ID ' . $id;
        
        // Try to extract additional info from XML if available
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_data);
        
        if ($xml !== false) {
            foreach ($xml->children() as $key => $value) {
                if ($key !== 'Success' && $key !== 'Msg') {
                    $property->$key = (string)$value;
                }
            }
        }
        
        return $property;
    }
    
    /**
     * Parse XML properties from API response
     */
    private function parse_xml_properties($xml_string) {
        $properties = array();
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_string);
        
        if ($xml !== false) {
            $xpath_patterns = array(
                '//Property',
                '//property',
                '//*[contains(name(), "Property")]'
            );
            
            foreach ($xpath_patterns as $pattern) {
                $property_nodes = $xml->xpath($pattern);
                if (!empty($property_nodes)) {
                    foreach ($property_nodes as $property_xml) {
                        $property = new stdClass();
                        
                        // Convert XML attributes to object properties
                        foreach ($property_xml->attributes() as $key => $value) {
                            $property->$key = (string)$value;
                        }
                        
                        // Convert XML child elements to object properties
                        foreach ($property_xml->children() as $key => $value) {
                            $property->$key = (string)$value;
                        }
                        
                        $properties[] = $property;
                    }
                    break;
                }
            }
        }
        
        return $properties;
    }
    
    /**
     * Remove duplicate properties based on PropertyID
     */
    private function remove_duplicate_properties($properties) {
        $unique_properties = array();
        $seen_ids = array();
        
        foreach ($properties as $property) {
            $id = isset($property->PropertyID) ? $property->PropertyID : 
                  (isset($property->PropertyId) ? $property->PropertyId : 
                   (isset($property->ID) ? $property->ID : null));
                   
            if ($id && !in_array($id, $seen_ids)) {
                $seen_ids[] = $id;
                $unique_properties[] = $property;
            }
        }
        
        return $unique_properties;
    }
    
    /**
     * Get property images
     */
    public function get_property_images($property_id) {
        if (!$this->soap_client) {
            return array('success' => false, 'message' => 'SOAP client not available');
        }
        
        try {
            $params = array_merge($this->get_auth_params(), array(
                'propertyId' => $property_id
            ));
            
            $response = $this->soap_client->GetPropertyAllImgs($params);
            
            if (isset($response->GetPropertyAllImgsResult)) {
                $images = $response->GetPropertyAllImgsResult;
                
                // Handle different response formats
                if (is_object($images)) {
                    if (isset($images->ImageInfo)) {
                        $images = $images->ImageInfo;
                    }
                }
                
                // Ensure we have an array
                if (!is_array($images)) {
                    $images = array($images);
                }
                
                return array(
                    'success' => true,
                    'data' => $images,
                    'count' => count($images)
                );
            }
            
            return array('success' => true, 'data' => array(), 'count' => 0);
            
        } catch (Exception $e) {
            error_log('Barefoot GetPropertyAllImgs Error: ' . $e->getMessage());
            return array('success' => false, 'message' => 'API Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get property rates
     */
    public function get_property_rates($property_id, $start_date = null, $end_date = null) {
        if (!$this->soap_client) {
            return array('success' => false, 'message' => 'SOAP client not available');
        }
        
        try {
            $params = array_merge($this->get_auth_params(), array(
                'propertyId' => $property_id
            ));
            
            if ($start_date) {
                $params['startDate'] = $start_date;
            }
            if ($end_date) {
                $params['endDate'] = $end_date;
            }
            
            $response = $this->soap_client->GetPropertyRates($params);
            
            if (isset($response->GetPropertyRatesResult)) {
                return array(
                    'success' => true,
                    'data' => $response->GetPropertyRatesResult
                );
            }
            
            return array('success' => true, 'data' => array());
            
        } catch (Exception $e) {
            error_log('Barefoot GetPropertyRates Error: ' . $e->getMessage());
            return array('success' => false, 'message' => 'API Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get available SOAP functions for debugging
     */
    public function get_available_functions() {
        if (!$this->soap_client) {
            return array();
        }
        
        try {
            return $this->soap_client->__getFunctions();
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Get last SOAP request for debugging
     */
    public function get_last_request() {
        if (!$this->soap_client) {
            return '';
        }
        
        try {
            return $this->soap_client->__getLastRequest();
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Get last SOAP response for debugging
     */
    public function get_last_response() {
        if (!$this->soap_client) {
            return '';
        }
        
        try {
            return $this->soap_client->__getLastResponse();
        } catch (Exception $e) {
            return '';
        }
    }
}