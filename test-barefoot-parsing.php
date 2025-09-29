<?php
/**
 * Test Barefoot API response parsing logic - standalone version
 * This will help us understand and fix the parsing issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// API credentials
$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';

echo "<h1>Barefoot API Response Parsing Test</h1>\n";

try {
    $soap_client = new SoapClient($endpoint . '?WSDL', array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ));
    
    echo "✅ SOAP client created successfully\n<br>";
    
    // Test the API call
    $params = array(
        'username' => $username,
        'password' => $password,
        'barefootAccount' => '' // Empty string as determined from previous tests
    );
    
    echo "<h2>Testing GetAllProperty API Call</h2>\n";
    
    try {
        $response = $soap_client->GetAllProperty($params);
        
        echo "✅ GetAllProperty call successful\n<br>";
        echo "<strong>Full Response Structure:</strong>\n<br>";
        echo "<pre>" . htmlspecialchars(print_r($response, true)) . "</pre>\n<br>";
        
        // Check if we have the expected result structure
        if (isset($response->GetAllPropertyResult)) {
            $result = $response->GetAllPropertyResult;
            echo "<h3>Analyzing GetAllPropertyResult:</h3>\n";
            
            // Check what properties exist in the result
            if (is_object($result)) {
                $result_vars = get_object_vars($result);
                echo "<strong>Available properties in result:</strong> " . implode(', ', array_keys($result_vars)) . "\n<br><br>";
                
                // Check for PROPERTIES container
                if (isset($result->PROPERTIES)) {
                    echo "✅ Found PROPERTIES container\n<br>";
                    
                    if (isset($result->PROPERTIES->PROPERTY)) {
                        echo "✅ Found PROPERTY array/object inside PROPERTIES\n<br>";
                        
                        $property_data = $result->PROPERTIES->PROPERTY;
                        
                        if (is_array($property_data)) {
                            echo "📊 Found " . count($property_data) . " properties (array format)\n<br>";
                            $sample_property = $property_data[0];
                        } else {
                            echo "📊 Found 1 property (single object format)\n<br>";
                            $sample_property = $property_data;
                        }
                        
                        // Analyze the first property structure
                        echo "<h3>Sample Property Structure:</h3>\n";
                        echo "<pre>" . htmlspecialchars(print_r($sample_property, true)) . "</pre>\n";
                        
                        // List all available fields in the property
                        if (is_object($sample_property)) {
                            $property_fields = array_keys(get_object_vars($sample_property));
                            echo "<h3>Available Property Fields:</h3>\n";
                            echo "<ul>\n";
                            foreach ($property_fields as $field) {
                                $value = $sample_property->$field;
                                echo "<li><strong>" . htmlspecialchars($field) . ":</strong> " . 
                                     htmlspecialchars(is_string($value) ? substr($value, 0, 100) : $value) . 
                                     (is_string($value) && strlen($value) > 100 ? '...' : '') . "</li>\n";
                            }
                            echo "</ul>\n";
                            
                            // Test field extraction function
                            echo "<h3>Testing Field Extraction:</h3>\n";
                            $test_fields = array(
                                'PropertyID', 'PropertyId', 'ID', 'id',
                                'Name', 'PropertyName', 'PropertyTitle', 'Title',
                                'Description', 'City', 'State', 'Zip'
                            );
                            
                            foreach ($test_fields as $field) {
                                $value = isset($sample_property->$field) ? $sample_property->$field : null;
                                if ($value !== null && $value !== '') {
                                    echo "✅ <strong>$field:</strong> " . htmlspecialchars($value) . "\n<br>";
                                }
                            }
                        }
                        
                    } else {
                        echo "❌ No PROPERTY found inside PROPERTIES\n<br>";
                        echo "PROPERTIES content: <pre>" . htmlspecialchars(print_r($result->PROPERTIES, true)) . "</pre>\n";
                    }
                } else {
                    echo "❌ No PROPERTIES container found\n<br>";
                    
                    // Check for 'any' field (XML format)
                    if (isset($result->any)) {
                        echo "✅ Found 'any' field with XML data\n<br>";
                        $xml_string = $result->any;
                        echo "<strong>XML Data (first 1000 chars):</strong>\n<br>";
                        echo "<pre>" . htmlspecialchars(substr($xml_string, 0, 1000)) . "</pre>\n<br>";
                        
                        // Try to parse XML
                        libxml_use_internal_errors(true);
                        $xml = simplexml_load_string($xml_string);
                        
                        if ($xml !== false) {
                            echo "✅ XML parsed successfully\n<br>";
                            
                            // Try different XPath patterns to find properties
                            $xpath_patterns = array(
                                '//Property',
                                '//property', 
                                '//*[contains(name(), "Property")]',
                                '//*[contains(name(), "property")]'
                            );
                            
                            foreach ($xpath_patterns as $pattern) {
                                $nodes = $xml->xpath($pattern);
                                if (!empty($nodes)) {
                                    echo "✅ Found " . count($nodes) . " nodes with pattern: $pattern\n<br>";
                                    
                                    $first_node = $nodes[0];
                                    echo "<strong>Sample node structure:</strong>\n<br>";
                                    echo "<pre>" . htmlspecialchars(print_r($first_node, true)) . "</pre>\n<br>";
                                    
                                    // Show attributes
                                    $attributes = $first_node->attributes();
                                    if ($attributes) {
                                        echo "<strong>Node attributes:</strong>\n<br>";
                                        foreach ($attributes as $key => $value) {
                                            echo "• $key: " . htmlspecialchars($value) . "\n<br>";
                                        }
                                    }
                                    
                                    break;
                                }
                            }
                        } else {
                            echo "❌ Failed to parse XML\n<br>";
                            $errors = libxml_get_errors();
                            foreach ($errors as $error) {
                                echo "XML Error: " . htmlspecialchars($error->message) . "\n<br>";
                            }
                        }
                    }
                }
            }
        } else {
            echo "❌ No GetAllPropertyResult found in response\n<br>";
        }
        
        // Show raw SOAP request and response for debugging
        echo "<h3>Debug Information:</h3>\n";
        echo "<strong>Last SOAP Request:</strong>\n<br>";
        echo "<pre>" . htmlspecialchars($soap_client->__getLastRequest()) . "</pre>\n<br>";
        
        echo "<strong>Last SOAP Response (first 2000 chars):</strong>\n<br>";
        echo "<pre>" . htmlspecialchars(substr($soap_client->__getLastResponse(), 0, 2000)) . "</pre>\n<br>";
        
    } catch (SoapFault $e) {
        echo "❌ SOAP Fault: " . htmlspecialchars($e->getMessage()) . "\n<br>";
        echo "<strong>Fault Code:</strong> " . htmlspecialchars($e->faultcode) . "\n<br>";
        echo "<strong>Fault String:</strong> " . htmlspecialchars($e->faultstring) . "\n<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "\n<br>";
}

echo "<hr>";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";

?>