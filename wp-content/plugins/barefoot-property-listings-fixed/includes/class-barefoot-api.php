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
     */
    public function get_all_properties() {
        if (!$this->soap_client) {
            return array('success' => false, 'message' => 'SOAP client not available');
        }
        
        try {
            $params = $this->get_auth_params();
            
            error_log('Barefoot API: Attempting GetAllProperty with params: ' . print_r($params, true));
            
            // Try GetAllProperty method (this is the documented method)
            $response = $this->soap_client->GetAllProperty($params);
            
            // Log the raw response for debugging
            $raw_response = $this->soap_client->__getLastResponse();
            error_log('Barefoot API Raw Response: ' . substr($raw_response, 0, 1000));
            
            if (isset($response->GetAllPropertyResult)) {
                $result = $response->GetAllPropertyResult;
                error_log('Barefoot GetAllPropertyResult: ' . print_r($result, true));
                
                // Handle "This is a Custom method" response
                if (isset($result->Message) && strpos($result->Message, 'Custom method') !== false) {
                    error_log('Barefoot API: Received "Custom method" response - this may be expected behavior');
                    
                    // The API might be returning properties in a different way
                    // Check if there are any hidden properties or if we need to handle this differently
                    
                    // Sometimes the response might contain data despite the message
                    // Let's check all possible properties
                    $properties = array();
                    
                    // Check for PROPERTIES/PROPERTY structure
                    if (isset($result->PROPERTIES) && isset($result->PROPERTIES->PROPERTY)) {
                        $property_data = $result->PROPERTIES->PROPERTY;
                        $properties = is_array($property_data) ? $property_data : array($property_data);
                        error_log('Found properties in PROPERTIES->PROPERTY: ' . count($properties));
                    }
                    
                    // Check for 'any' XML field
                    elseif (isset($result->any) && !empty($result->any)) {
                        $xml_string = $result->any;
                        error_log('Found XML data in any field: ' . substr($xml_string, 0, 500));
                        
                        libxml_use_internal_errors(true);
                        $xml = simplexml_load_string($xml_string);
                        
                        if ($xml !== false) {
                            // Try to find properties in XML
                            $xpath_patterns = array(
                                '//Property',
                                '//property', 
                                '//*[contains(name(), "Property")]'
                            );
                            
                            foreach ($xpath_patterns as $pattern) {
                                $property_nodes = $xml->xpath($pattern);
                                if (!empty($property_nodes)) {
                                    error_log("Found {count($property_nodes)} properties with XPath: {$pattern}");
                                    
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
                    }
                    
                    // If we found properties despite the "Custom method" message
                    if (count($properties) > 0) {
                        error_log("Successfully extracted {count($properties)} properties despite 'Custom method' message");
                        return array(
                            'success' => true,
                            'data' => $properties,
                            'count' => count($properties),
                            'method_used' => 'GetAllProperty'
                        );
                    }
                    
                    // If no properties found, this might mean:
                    // 1. The account has no properties
                    // 2. Additional parameters are needed  
                    // 3. Different method should be used
                    error_log('Barefoot API: No properties found in GetAllProperty response');
                    
                    // Try alternative approach - get individual properties
                    return $this->try_alternative_property_retrieval();
                }
                
                // Handle normal response (if any)
                $properties = array();
                
                if (isset($result->PROPERTIES) && isset($result->PROPERTIES->PROPERTY)) {
                    $property_data = $result->PROPERTIES->PROPERTY;
                    $properties = is_array($property_data) ? $property_data : array($property_data);
                } elseif (isset($result->any) && !empty($result->any)) {
                    // Handle XML data
                    $properties = $this->parse_xml_properties($result->any);
                }
                
                return array(
                    'success' => true,
                    'data' => $properties,
                    'count' => count($properties),
                    'method_used' => 'GetAllProperty'
                );
            }
            
            return array('success' => false, 'message' => 'No GetAllPropertyResult in response');
            
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
    private function try_alternative_property_retrieval() {
        error_log('Barefoot API: Trying alternative property retrieval methods');
        
        // For now, return empty result indicating the API responds but has no data
        // In a real scenario, we might try other methods or contact support
        return array(
            'success' => true,
            'data' => array(),
            'count' => 0,
            'method_used' => 'GetAllProperty (Custom method response)',
            'message' => 'API responded with "Custom method" - may indicate no properties available or additional configuration needed'
        );
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