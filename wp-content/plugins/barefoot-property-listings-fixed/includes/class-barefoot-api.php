<?php
/**
 * Barefoot API Integration Class
 * Handles SOAP communication with Barefoot Property Management System
 */

class Barefoot_API {
    
    private $soap_client;
    private $endpoint;
    private $username;
    private $password;
    private $version;
    
    public function __construct() {
        $this->endpoint = BAREFOOT_API_ENDPOINT;
        $this->username = BAREFOOT_API_USERNAME;
        $this->password = BAREFOOT_API_PASSWORD;
        $this->version = BAREFOOT_API_VERSION;
        
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
            // Try to get WSDL functions to test connection
            $functions = $this->soap_client->__getFunctions();
            
            if (empty($functions)) {
                return array(
                    'success' => false,
                    'message' => 'No API functions available'
                );
            }
            
            return array(
                'success' => true,
                'message' => 'Successfully connected. Found ' . count($functions) . ' available API methods.',
                'functions_count' => count($functions)
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
            'barefootAccount' => $this->version // Use the API version as the Barefoot account identifier
        );
    }
    
    /**
     * Get all properties from Barefoot API
     * Since GetAllProperty returns "Custom method", we'll use alternative approaches
     */
    public function get_all_properties() {
        if (!$this->soap_client) {
            return array('success' => false, 'message' => 'SOAP client not available');
        }
        
        try {
            $params = $this->get_auth_params();
            
            error_log('Barefoot API: Using corrected barefootAccount parameter: ' . $this->version);
            
            // First, try GetAllProperty method (documented but returns custom method message)
            $response = $this->soap_client->GetAllProperty($params);
            
            if (isset($response->GetAllPropertyResult)) {
                $result = $response->GetAllPropertyResult;
                
                // Check if we have actual property data despite "Custom method" message
                $properties = array();
                
                if (isset($result->PROPERTIES) && isset($result->PROPERTIES->PROPERTY)) {
                    $property_data = $result->PROPERTIES->PROPERTY;
                    $properties = is_array($property_data) ? $property_data : array($property_data);
                    error_log('Barefoot API: Found properties in PROPERTIES container: ' . count($properties));
                } elseif (isset($result->any) && !empty($result->any)) {
                    $properties = $this->parse_xml_properties($result->any);
                    error_log('Barefoot API: Found properties in XML any field: ' . count($properties));
                }
                
                if (count($properties) > 0) {
                    return array(
                        'success' => true,
                        'data' => $properties,
                        'count' => count($properties),
                        'method_used' => 'GetAllProperty'
                    );
                }
                
                // If GetAllProperty doesn't return data, try alternative methods
                error_log('Barefoot API: GetAllProperty returned "Custom method" - trying alternative approaches');
            }
            
            // Try alternative methods that are known to work
            $alternative_results = $this->try_alternative_property_methods();
            if ($alternative_results['success']) {
                return $alternative_results;
            }
            
            // If no properties found, return successful but empty result
            return array(
                'success' => true,
                'data' => array(),
                'count' => 0,
                'method_used' => 'GetAllProperty',
                'message' => 'API connection successful with corrected barefootAccount parameter, but no properties returned. This may indicate: 1) No properties are configured in this account, 2) Properties exist but require different API methods, or 3) Additional setup may be needed.'
            );
            
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
        error_log('Barefoot API: Trying alternative property retrieval methods');
        
        $params = $this->get_auth_params();
        $properties = array();
        
        // Try methods that are known to work with this account
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
                
            } catch (SoapFault $e) {
                error_log("Barefoot API: Method {$method} failed with SOAP Fault: " . $e->getMessage());
                continue;
            } catch (Exception $e) {
                error_log("Barefoot API: Method {$method} failed with error: " . $e->getMessage());
                continue;
            }
        }
        
        // Try to get properties by ID range (if we know some IDs exist)
        if (empty($properties)) {
            $properties = $this->try_property_id_range();
        }
        
        if (count($properties) > 0) {
            // Remove duplicates based on property ID
            $properties = $this->remove_duplicate_properties($properties);
            
            return array(
                'success' => true,
                'data' => $properties,
                'count' => count($properties),
                'method_used' => 'Alternative methods'
            );
        }
        
        return array('success' => false, 'message' => 'No properties found using alternative methods');
    }
    
    /**
     * Try to get properties by testing common property ID ranges
     */
    private function try_property_id_range() {
        $params = $this->get_auth_params();
        $properties = array();
        
        // Test first 10 property IDs to see if any exist
        for ($id = 1; $id <= 10; $id++) {
            try {
                $property_params = array_merge($params, array('addressid' => $id));
                $response = $this->soap_client->GetPropertyInfoById($property_params);
                
                if (isset($response->GetPropertyInfoByIdResult->any)) {
                    $xml_data = $response->GetPropertyInfoByIdResult->any;
                    
                    // Check if it's a successful response
                    libxml_use_internal_errors(true);
                    $xml = simplexml_load_string($xml_data);
                    if ($xml !== false && isset($xml->Success) && (string)$xml->Success === 'true') {
                        // This means the property ID exists, but we might need more detailed info
                        error_log("Barefoot API: Found existing property with ID: {$id}");
                        
                        // Try to get detailed property info
                        $detailed_property = $this->get_detailed_property_info($id);
                        if ($detailed_property) {
                            $properties[] = $detailed_property;
                        }
                    }
                }
                
            } catch (Exception $e) {
                // Continue to next ID
                continue;
            }
            
            // Limit to prevent too many API calls
            if (count($properties) >= 5) {
                break;
            }
        }
        
        return $properties;
    }
    
    /**
     * Get detailed property information for a specific property ID
     */
    private function get_detailed_property_info($property_id) {
        $params = $this->get_auth_params();
        
        try {
            // Try different methods to get detailed property info
            $methods_to_try = array(
                array('method' => 'GetPropertyInfoById', 'param' => 'addressid'),
                array('method' => 'GetPropertyAndOwnerById', 'param' => 'addressid')
            );
            
            foreach ($methods_to_try as $method_config) {
                try {
                    $method_params = array_merge($params, array($method_config['param'] => $property_id));
                    $response = $this->soap_client->{$method_config['method']}($method_params);
                    
                    $result_key = $method_config['method'] . 'Result';
                    if (isset($response->$result_key->any)) {
                        $xml_data = $response->$result_key->any;
                        
                        // Parse the XML to create a property object
                        $property = $this->parse_single_property_xml($xml_data, $property_id);
                        if ($property) {
                            return $property;
                        }
                    }
                    
                } catch (Exception $e) {
                    continue;
                }
            }
            
        } catch (Exception $e) {
            error_log("Barefoot API: Error getting detailed property info for ID {$property_id}: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Parse single property XML and create property object
     */
    private function parse_single_property_xml($xml_data, $property_id) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_data);
        
        if ($xml !== false) {
            $property = new stdClass();
            $property->PropertyID = $property_id;
            $property->Name = 'Property ' . $property_id; // Default name
            
            // Extract any available property information from the XML
            foreach ($xml->children() as $key => $value) {
                $property->$key = (string)$value;
            }
            
            return $property;
        }
        
        return null;
    }
    
    /**
     * Remove duplicate properties based on PropertyID
     */
    private function remove_duplicate_properties($properties) {
        $unique_properties = array();
        $seen_ids = array();
        
        foreach ($properties as $property) {
            $id = $this->get_property_field($property, 'PropertyID');
            if ($id && !in_array($id, $seen_ids)) {
                $seen_ids[] = $id;
                $unique_properties[] = $property;
            }
        }
        
        return $unique_properties;
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
                        
                        // Convert XML to object
                        foreach ($property_xml->attributes() as $key => $value) {
                            $property->$key = (string)$value;
                        }
                        
                        foreach ($property_xml->children() as $key => $value) {
                            $property->$key = (string)$value;
                        }
                        
                        $properties[] = $property;
                    }
                    break;
                }
            }
        } else {
            $errors = libxml_get_errors();
            error_log('XML parsing errors: ' . print_r($errors, true));
        }
        
        return $properties;
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
            
        } catch (SoapFault $e) {
            error_log('Barefoot GetPropertyAllImgs SOAP Fault: ' . $e->getMessage());
            return array('success' => false, 'message' => 'SOAP Fault: ' . $e->getMessage());
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
                $rates = $response->GetPropertyRatesResult;
                
                return array(
                    'success' => true,
                    'data' => $rates
                );
            }
            
            return array('success' => true, 'data' => array());
            
        } catch (SoapFault $e) {
            error_log('Barefoot GetPropertyRates SOAP Fault: ' . $e->getMessage());
            return array('success' => false, 'message' => 'SOAP Fault: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('Barefoot GetPropertyRates Error: ' . $e->getMessage());
            return array('success' => false, 'message' => 'API Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get property booking dates/availability
     */
    public function get_property_booking_dates($property_id, $start_date = null, $end_date = null) {
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
            
            $response = $this->soap_client->GetPropertyBookingDate($params);
            
            if (isset($response->GetPropertyBookingDateResult)) {
                $booking_dates = $response->GetPropertyBookingDateResult;
                
                return array(
                    'success' => true,
                    'data' => $booking_dates
                );
            }
            
            return array('success' => true, 'data' => array());
            
        } catch (SoapFault $e) {
            error_log('Barefoot GetPropertyBookingDate SOAP Fault: ' . $e->getMessage());
            return array('success' => false, 'message' => 'SOAP Fault: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('Barefoot GetPropertyBookingDate Error: ' . $e->getMessage());
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
     * List all available property-related methods
     */
    public function list_property_methods() {
        $functions = $this->get_available_functions();
        $property_methods = array();
        
        foreach ($functions as $function) {
            if (stripos($function, 'property') !== false || stripos($function, 'Property') !== false) {
                $property_methods[] = $function;
            }
        }
        
        return $property_methods;
    }
    
    /**
     * Get SOAP types for debugging
     */
    public function get_available_types() {
        if (!$this->soap_client) {
            return array();
        }
        
        try {
            return $this->soap_client->__getTypes();
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

?>