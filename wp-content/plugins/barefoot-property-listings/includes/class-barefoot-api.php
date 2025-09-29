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
            $params = array_merge($this->get_auth_params(), array(
                'includeInactive' => false
            ));
            
            $response = $this->soap_client->GetAllProperty($params);
            
            if (isset($response->GetAllPropertyResult)) {
                $properties = $response->GetAllPropertyResult;
                
                // Handle different response formats
                if (is_object($properties)) {
                    if (isset($properties->PropertyInfo)) {
                        $properties = $properties->PropertyInfo;
                    }
                }
                
                // Ensure we have an array
                if (!is_array($properties)) {
                    $properties = array($properties);
                }
                
                return array(
                    'success' => true,
                    'data' => $properties,
                    'count' => count($properties)
                );
            }
            
            return array('success' => false, 'message' => 'No properties returned from API');
            
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