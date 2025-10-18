<?php
/**
 * Test specific property methods that might return actual data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Specific Property ID Methods</h1>\n";

$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';
$barefootAccount = 'v3chfa0604';

try {
    $soap_client = new SoapClient($endpoint . '?WSDL', array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ));
    
    echo "✅ SOAP client created successfully<br><br>\n";
    
    // Test methods that might return property lists or information
    $test_methods = array(
        'GetPropertyActiveIdx',
        'GetPropertyList', 
        'GetPropertiesByOwner',
        'GetAllPropertyOwnerId',
        'GetAvailableProperty',
        'GetPropertyByStatus',
        'GetPropertySearch'
    );
    
    $base_params = array(
        'username' => $username,
        'password' => $password,
        'barefootAccount' => $barefootAccount
    );
    
    foreach ($test_methods as $method) {
        echo "<h2>Testing: {$method}</h2>\n";
        
        try {
            $response = $soap_client->$method($base_params);
            echo "✅ <strong>{$method} succeeded!</strong><br>\n";
            
            $result_key = $method . 'Result';
            if (isset($response->$result_key)) {
                $result = $response->$result_key;
                
                if (is_object($result)) {
                    $vars = get_object_vars($result);
                    echo "<strong>Result properties:</strong> " . implode(', ', array_keys($vars)) . "<br>\n";
                    
                    if (isset($result->any) && !empty($result->any)) {
                        echo "✅ <strong>Found data in 'any' field!</strong><br>\n";
                        echo "<strong>Data preview:</strong> " . htmlspecialchars(substr($result->any, 0, 300)) . "...<br>\n";
                        
                        // Try to parse as XML to count properties
                        libxml_use_internal_errors(true);
                        $xml = simplexml_load_string($result->any);
                        if ($xml !== false) {
                            $properties = $xml->xpath('//Property | //property | //*[contains(name(), "Property")]');
                            if (!empty($properties)) {
                                echo "🎉 <strong>Found " . count($properties) . " properties!</strong><br>\n";
                                
                                // Show first property details
                                $first_prop = $properties[0];
                                echo "<strong>Sample property attributes:</strong><br>\n";
                                foreach ($first_prop->attributes() as $key => $value) {
                                    echo "• $key: " . htmlspecialchars(substr($value, 0, 50)) . "<br>\n";
                                }
                                
                                echo "<br><strong>🎯 THIS METHOD WORKS! Use this method for property retrieval.</strong><br>\n";
                            }
                        }
                    } elseif (isset($result->Message)) {
                        echo "<strong>Message:</strong> " . htmlspecialchars($result->Message) . "<br>\n";
                    } else {
                        echo "<strong>Result content:</strong> " . htmlspecialchars(print_r($result, true)) . "<br>\n";
                    }
                } elseif (is_string($result)) {
                    echo "<strong>String result:</strong> " . htmlspecialchars($result) . "<br>\n";
                } else {
                    echo "<strong>Raw result:</strong> " . htmlspecialchars(print_r($result, true)) . "<br>\n";
                }
            } else {
                echo "❌ No result found<br>\n";
                echo "<strong>Full response:</strong> " . htmlspecialchars(print_r($response, true)) . "<br>\n";
            }
            
        } catch (SoapFault $e) {
            echo "❌ <strong>SOAP Fault:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
            
            if (strpos($e->getMessage(), 'not found') !== false) {
                echo "   (Method doesn't exist)<br>\n";
            } elseif (strpos($e->getMessage(), 'access denied') !== false) {
                echo "   (Access denied - method exists but no permission)<br>\n";
            }
        } catch (Exception $e) {
            echo "❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
        }
        
        echo "<hr>\n";
    }
    
    // Test GetPropertyInfoById with sample IDs
    echo "<h2>Testing GetPropertyInfoById with Sample IDs</h2>\n";
    
    for ($id = 1; $id <= 10; $id++) {
        echo "<h3>Testing Property ID: {$id}</h3>\n";
        
        try {
            $params = array_merge($base_params, array('addressid' => $id));
            $response = $soap_client->GetPropertyInfoById($params);
            
            if (isset($response->GetPropertyInfoByIdResult->any)) {
                $xml_data = $response->GetPropertyInfoByIdResult->any;
                echo "<strong>Response data:</strong> " . htmlspecialchars($xml_data) . "<br>\n";
                
                if (strpos($xml_data, '<Success>true</Success>') !== false) {
                    echo "🎉 <strong>Property ID {$id} exists and has data!</strong><br>\n";
                    
                    // Try to extract property details
                    libxml_use_internal_errors(true);
                    $xml = simplexml_load_string($xml_data);
                    if ($xml !== false) {
                        foreach ($xml->children() as $key => $value) {
                            if ($key !== 'Success' && $key !== 'Msg' && !empty($value)) {
                                echo "• {$key}: " . htmlspecialchars(substr($value, 0, 50)) . "<br>\n";
                            }
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            // Skip this ID
            continue;
        }
        
        echo "<br>\n";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>\n";
    echo "<h2>Fatal Error</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}

echo "<h2>Summary</h2>\n";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa;'>\n";
echo "<p><strong>Based on the debugging results:</strong></p>\n";
echo "<ol>\n";
echo "<li>GetAllProperty returns 'This is a Custom method' - this appears to be expected behavior for this account</li>\n";
echo "<li>This likely means no properties are configured for bulk retrieval, OR</li>\n";
echo "<li>Properties exist but need to be retrieved individually by ID, OR</li>\n";
echo "<li>A different method is needed for this specific account setup</li>\n";
echo "</ol>\n";
echo "<p>If any of the methods above show actual property data, that method should be used instead of GetAllProperty.</p>\n";
echo "</div>\n";

echo "<p><small>Test completed at: " . date('Y-m-d H:i:s') . "</small></p>\n";

?>