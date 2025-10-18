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
     * Get all properties from Barefoot API using GetProperty method
     * The GetProperty method (with only auth params) returns all properties in PropertyList XML format
     */
    public function get_all_properties() {
        if (!$this->soap_client) {
            return array('success' => false, 'message' => 'SOAP client not available');
        }
        
        try {
            $params = $this->get_auth_params();
            
            error_log('Barefoot API: Calling GetProperty method to retrieve all properties');
            
            // Call GetProperty with only authentication parameters (returns all properties)
            try {
                $response = $this->soap_client->GetProperty($params);
                
                if (isset($response->GetPropertyResult)) {
                    $result = $response->GetPropertyResult;
                    
                    // Parse the PropertyList XML response
                    if (is_string($result)) {
                        // Response is directly a string
                        $properties = $this->parse_property_list_xml($result);
                        if (!empty($properties)) {
                            error_log('Barefoot API: GetProperty successfully returned ' . count($properties) . ' properties');
                            return array(
                                'success' => true,
                                'data' => $properties,
                                'count' => count($properties),
                                'method_used' => 'GetProperty'
                            );
                        }
                    } elseif (isset($result->any) && !empty($result->any)) {
                        // Response has 'any' property containing XML string
                        $properties = $this->parse_property_list_xml($result->any);
                        if (!empty($properties)) {
                            error_log('Barefoot API: GetProperty successfully returned ' . count($properties) . ' properties');
                            return array(
                                'success' => true,
                                'data' => $properties,
                                'count' => count($properties),
                                'method_used' => 'GetProperty'
                            );
                        }
                    }
                    
                    // If we got a response but no properties parsed, log it
                    error_log('Barefoot API: GetProperty returned response but no properties were parsed');
                }
            } catch (Exception $e) {
                error_log('Barefoot API: GetProperty method failed: ' . $e->getMessage());
            }
            
            // Strategy 2: Get properties individually by ID
            $properties = $this->get_properties_by_id_range();
            
            if (!empty($properties)) {
                return array(
                    'success' => true,
                    'data' => $properties,
                    'count' => count($properties),
                    'method_used' => 'GetProperty by ID range'
                );
            }
            
            // Strategy 3: Try alternative working methods
            $alt_properties = $this->try_alternative_working_methods();
            
            if ($alt_properties['success']) {
                return $alt_properties;
            }
            
            // If no properties found, return successful but empty result
            return array(
                'success' => true,
                'data' => array(),
                'count' => 0,
                'method_used' => 'GetProperty (various attempts)',
                'message' => 'API connection successful using GetProperty method, but no properties returned. Account may not have properties configured or may require different parameters.'
            );
            
        } catch (SoapFault $e) {
            error_log('Barefoot GetProperty SOAP Fault: ' . $e->getMessage());
            return array('success' => false, 'message' => 'SOAP Fault: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('Barefoot GetProperty Error: ' . $e->getMessage());
            return array('success' => false, 'message' => 'API Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get properties by testing individual property IDs
     */
    private function get_properties_by_id_range() {
        $params = $this->get_auth_params();
        $properties = array();
        
        error_log('Barefoot API: Attempting to retrieve properties by individual IDs');
        
        // Test first 20 property IDs (we know 1-10 exist from debugging)
        for ($id = 1; $id <= 20; $id++) {
            try {
                $property_params = array_merge($params, array('addressid' => (string)$id));
                $response = $this->soap_client->GetPropertyInfoById($property_params);
                
                if (isset($response->GetPropertyInfoByIdResult->any)) {
                    $xml_data = $response->GetPropertyInfoByIdResult->any;
                    
                    // Check if it's a successful response
                    if (strpos($xml_data, '<Success>true</Success>') !== false) {
                        error_log("Barefoot API: Property ID {$id} exists");
                        
                        // Get detailed property information using GetProperty with ID
                        $detailed_property = $this->get_detailed_property_by_id($id);
                        if ($detailed_property) {
                            $properties[] = $detailed_property;
                        }
                    }
                }
                
            } catch (Exception $e) {
                // Skip this ID and continue
                continue;
            }
            
            // Limit to prevent too many API calls (max 10 properties for now)
            if (count($properties) >= 10) {
                break;
            }
        }
        
        error_log('Barefoot API: Retrieved ' . count($properties) . ' properties by ID range');
        return $properties;
    }
    
    /**
     * Get detailed property information for a specific property ID
     */
    private function get_detailed_property_by_id($property_id) {
        $params = $this->get_auth_params();
        
        try {
            // Try different methods to get detailed property info
            $methods_to_try = array(
                array('method' => 'GetProperty', 'param' => 'propertyId', 'value' => (string)$property_id),
                array('method' => 'GetPropertyExt', 'param' => 'propertyId', 'value' => (string)$property_id),
                array('method' => 'GetPropertyInfoById', 'param' => 'addressid', 'value' => (string)$property_id),
                array('method' => 'GetProperty', 'param' => 'PropertyID', 'value' => (string)$property_id),
            );
            
            foreach ($methods_to_try as $method_config) {
                try {
                    $method_params = array_merge($params, array($method_config['param'] => $method_config['value']));
                    $response = $this->soap_client->{$method_config['method']}($method_params);
                    
                    $result_key = $method_config['method'] . 'Result';
                    if (isset($response->$result_key->any)) {
                        $xml_data = $response->$result_key->any;
                        
                        // Parse the XML to create a property object
                        $property = $this->parse_single_property_from_xml($xml_data, $property_id);
                        if ($property && $this->validate_property_data($property)) {
                            error_log("Barefoot API: Successfully retrieved detailed data for property {$property_id} using {$method_config['method']}");
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
     * Parse single property XML and create property object with proper field mapping
     */
    private function parse_single_property_from_xml($xml_data, $property_id) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_data);
        
        if ($xml !== false) {
            $property = new stdClass();
            
            // Set the property ID
            $property->PropertyID = (string)$property_id;
            
            // Map common property fields from XML
            $field_mappings = array(
                'PropertyName' => array('PropertyName', 'Name', 'Title', 'PropertyTitle'),
                'Description' => array('Description', 'PropertyDescription', 'Desc'),
                'City' => array('City', 'PropertyCity'),
                'State' => array('State', 'PropertyState'),
                'Zip' => array('Zip', 'ZipCode', 'PostalCode'),
                'Address' => array('Address', 'PropertyAddress', 'FullAddress'),
                'Occupancy' => array('Occupancy', 'MaxOccupancy', 'Sleeps'),
                'Bedrooms' => array('Bedrooms', 'BedroomCount', 'Beds'),
                'Bathrooms' => array('Bathrooms', 'BathroomCount', 'Baths'),
                'MinPrice' => array('MinPrice', 'MinimumRate', 'LowRate'),
                'MaxPrice' => array('MaxPrice', 'MaximumRate', 'HighRate'),
                'PropertyType' => array('PropertyType', 'Type', 'UnitType'),
            );
            
            // Extract data from XML
            foreach ($xml->children() as $key => $value) {
                $field_value = trim((string)$value);
                if (!empty($field_value) && $field_value !== 'false' && $field_value !== '0') {
                    $property->$key = $field_value;
                }
            }
            
            // Extract from XML attributes
            foreach ($xml->attributes() as $key => $value) {
                $field_value = trim((string)$value);
                if (!empty($field_value)) {
                    $property->$key = $field_value;
                }
            }
            
            // Apply field mappings to standardize property names
            foreach ($field_mappings as $standard_field => $possible_fields) {
                foreach ($possible_fields as $field) {
                    if (isset($property->$field) && !empty($property->$field)) {
                        $property->$standard_field = $property->$field;
                        break;
                    }
                }
            }
            
            // Set default name if not found
            if (empty($property->Name) && empty($property->PropertyName)) {
                $property->Name = 'Property ' . $property_id;
                $property->PropertyName = 'Property ' . $property_id;
            }
            
            return $property;
        }
        
        return null;
    }
    
    /**
     * Validate property data to ensure it has minimum required fields
     */
    private function validate_property_data($property) {
        // Property must have at least an ID and name
        $has_id = !empty($property->PropertyID);
        $has_name = !empty($property->Name) || !empty($property->PropertyName) || !empty($property->Title);
        
        return $has_id && $has_name;
    }
    
    /**
     * Try alternative working methods discovered during debugging
     */
    private function try_alternative_working_methods() {
        $params = $this->get_auth_params();
        $properties = array();
        
        // Try methods that were successful in debugging
        $working_methods = array(
            'GetPropertyExt',
            'GetLastUpdatedProperty'
        );
        
        foreach ($working_methods as $method) {
            try {
                error_log("Barefoot API: Trying alternative method: {$method}");
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
        
        if (count($properties) > 0) {
            // Remove duplicates
            $properties = $this->remove_duplicate_properties($properties);
            
            return array(
                'success' => true,
                'data' => $properties,
                'count' => count($properties),
                'method_used' => 'Alternative methods (' . implode(', ', $working_methods) . ')'
            );
        }
        
        return array('success' => false, 'message' => 'No properties found using alternative methods');
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