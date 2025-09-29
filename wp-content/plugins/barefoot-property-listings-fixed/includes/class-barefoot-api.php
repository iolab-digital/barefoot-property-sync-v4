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
            'barefootAccount' => '' // Empty string required by API
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
            // First, let's log available methods
            $functions = $this->soap_client->__getFunctions();
            error_log('Available SOAP methods: ' . print_r($functions, true));
            
            $params = $this->get_auth_params();
            
            // Try different method names that might work
            $methods_to_try = array(
                'GetAllProperty',
                'GetAllProperties', 
                'GetProperties',
                'GetPropertyList',
                'GetAllPropertyList',
                'GetPropertyData'
            );
            
            foreach ($methods_to_try as $method) {
                try {
                    error_log("Trying method: {$method}");
                    $response = $this->soap_client->$method($params);
            
                    // Debug: Log the entire response structure
                    error_log("Barefoot API Response for {$method}: " . print_r($response, true));
                    
                    // Check for different result property names
                    $result_property = $method . 'Result';
                    if (isset($response->$result_property)) {
                        $result = $response->$result_property;
                
                        // Debug: Log the result structure
                        error_log("Barefoot {$method}Result: " . print_r($result, true));
                        
                        // Skip if this is just a "custom method" message
                        if (isset($result->Message) && strpos($result->Message, 'Custom method') !== false) {
                            error_log("Method {$method} returned custom method message, trying next...");
                            continue;
                        }
                        
                        // Handle different response formats
                        $properties = array();
                        
                        // Check for the PROPERTIES container first (according to WSDL)
                        if (isset($result->PROPERTIES) && isset($result->PROPERTIES->PROPERTY)) {
                            error_log('Found PROPERTIES->PROPERTY structure');
                            
                            $property_data = $result->PROPERTIES->PROPERTY;
                            
                            // Handle single property vs multiple properties
                            if (is_array($property_data)) {
                                $properties = $property_data;
                            } else {
                                $properties = array($property_data);
                            }
                            
                            error_log('Extracted ' . count($properties) . ' properties from PROPERTIES structure');
                            
                        } elseif (isset($result->any) && is_string($result->any)) {
                            // XML string format
                            $xml_string = $result->any;
                            error_log('Barefoot XML String: ' . substr($xml_string, 0, 1000) . '...');
                            
                            libxml_use_internal_errors(true);
                            $xml = simplexml_load_string($xml_string);
                            
                            if ($xml !== false) {
                                // Try different XPath patterns
                                $property_nodes = $xml->xpath('//Property');
                                if (empty($property_nodes)) {
                                    $property_nodes = $xml->xpath('//property');
                                }
                                if (empty($property_nodes)) {
                                    $property_nodes = $xml->xpath('//*[contains(name(), "Property")]');
                                }
                                if (empty($property_nodes)) {
                                    $property_nodes = $xml->xpath('//*');
                                }
                                
                                error_log('Found property nodes: ' . count($property_nodes));
                                
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
                                    
                                    // Debug: Log first property structure
                                    if (count($properties) === 0) {
                                        error_log('First property object: ' . print_r($property, true));
                                    }
                                    
                                    $properties[] = $property;
                                }
                                
                            } else {
                                // XML parsing failed, log errors
                                $xml_errors = libxml_get_errors();
                                error_log('Barefoot XML Parse Error: ' . print_r($xml_errors, true));
                            }
                            
                        } elseif (isset($result->schema) && isset($result->any)) {
                            // DataSet format - try to extract from 'any' field
                            error_log('Barefoot DataSet format detected');
                            
                        } elseif (is_object($result)) {
                            // Direct object format
                            error_log('Barefoot Direct object format detected');
                            
                            // Check if result itself contains property data
                            if (isset($result->PropertyInfo)) {
                                $prop_info = $result->PropertyInfo;
                                if (is_array($prop_info)) {
                                    $properties = $prop_info;
                                } else {
                                    $properties = array($prop_info);
                                }
                            }
                        } elseif (is_array($result)) {
                            // Array format
                            error_log('Barefoot Array format detected');
                            $properties = $result;
                        }
                        
                        error_log("Barefoot Final properties count for {$method}: " . count($properties));
                        
                        // If we got properties, return them
                        if (count($properties) > 0) {
                            return array(
                                'success' => true,
                                'data' => $properties,
                                'count' => count($properties),
                                'method_used' => $method
                            );
                        }
                    }
                } catch (SoapFault $e) {
                    error_log("Method {$method} failed with SOAP Fault: " . $e->getMessage());
                    continue;
                } catch (Exception $e) {
                    error_log("Method {$method} failed with error: " . $e->getMessage());
                    continue;
                }
            }
            
            return array('success' => false, 'message' => 'No valid property methods found or all methods returned empty results');
            
        } catch (SoapFault $e) {
            error_log('Barefoot GetAllProperty SOAP Fault: ' . $e->getMessage());
            return array('success' => false, 'message' => 'SOAP Fault: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('Barefoot GetAllProperty Error: ' . $e->getMessage());
            return array('success' => false, 'message' => 'API Error: ' . $e->getMessage());
        }
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