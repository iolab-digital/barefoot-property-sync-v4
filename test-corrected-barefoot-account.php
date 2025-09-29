<?php
/**
 * Test the corrected Barefoot Account parameter usage
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';
$barefootAccount = 'v3chfa0604'; // This should be the actual account identifier, not empty string

echo "<h1>Fixed Barefoot API Test - Using Correct barefootAccount Parameter</h1>\n";
echo "<p>Testing with barefootAccount = 'v3chfa0604' instead of empty string...</p>\n";

try {
    $soap_client = new SoapClient($endpoint . '?WSDL', array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ));
    
    echo "✅ SOAP client created successfully\n<br>";
    
    // Test with correct barefootAccount parameter
    $params = array(
        'username' => $username,
        'password' => $password,
        'barefootAccount' => $barefootAccount
    );
    
    echo "<h2>Testing GetAllProperty with Correct Parameters:</h2>\n";
    echo "<strong>Parameters:</strong>\n<br>";
    echo "• username: " . htmlspecialchars($username) . "\n<br>";
    echo "• password: [HIDDEN]\n<br>";
    echo "• barefootAccount: " . htmlspecialchars($barefootAccount) . "\n<br><br>";
    
    $response = $soap_client->GetAllProperty($params);
    
    echo "✅ GetAllProperty call successful!\n<br>";
    
    // Log the raw response
    $raw_response = $soap_client->__getLastResponse();
    echo "<h3>Raw SOAP Response Analysis:</h3>\n";
    
    // Check if we still get the "Custom method" message
    if (isset($response->GetAllPropertyResult)) {
        $result = $response->GetAllPropertyResult;
        
        echo "<strong>Response Structure:</strong>\n<br>";
        if (is_object($result)) {
            $result_vars = get_object_vars($result);
            echo "Available properties: " . implode(', ', array_keys($result_vars)) . "\n<br><br>";
            
            if (isset($result->Message)) {
                if (strpos($result->Message, 'Custom method') !== false) {
                    echo "⚠️ Still getting 'Custom method' message: " . htmlspecialchars($result->Message) . "\n<br>";
                } else {
                    echo "✅ Different message received: " . htmlspecialchars($result->Message) . "\n<br>";
                }
            }
            
            // Check for actual data fields
            if (isset($result->any)) {
                echo "✅ Found 'any' field with XML data!\n<br>";
                $xml_content = $result->any;
                echo "<strong>XML Data (first 1000 chars):</strong>\n<br>";
                echo "<pre>" . htmlspecialchars(substr($xml_content, 0, 1000)) . "</pre>\n<br>";
                
                // Try to parse the XML for properties
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($xml_content);
                if ($xml !== false) {
                    $properties = $xml->xpath('//Property | //property');
                    if (!empty($properties)) {
                        echo "🎉 SUCCESS! Found " . count($properties) . " properties in the response!\n<br>";
                        
                        // Show first property details
                        $first_property = $properties[0];
                        echo "<h3>Sample Property Information:</h3>\n";
                        echo "<ul>\n";
                        
                        // Show attributes
                        foreach ($first_property->attributes() as $key => $value) {
                            echo "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</li>\n";
                        }
                        
                        // Show child elements
                        foreach ($first_property->children() as $key => $value) {
                            echo "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '') . "</li>\n";
                        }
                        echo "</ul>\n";
                    }
                }
            }
            
            if (isset($result->schema)) {
                echo "✅ Found schema field\n<br>";
            }
            
            if (isset($result->PROPERTIES)) {
                echo "✅ Found PROPERTIES container!\n<br>";
                if (isset($result->PROPERTIES->PROPERTY)) {
                    $properties = is_array($result->PROPERTIES->PROPERTY) ? $result->PROPERTIES->PROPERTY : array($result->PROPERTIES->PROPERTY);
                    echo "🎉 SUCCESS! Found " . count($properties) . " properties in PROPERTIES container!\n<br>";
                    
                    // Show first property
                    if (!empty($properties)) {
                        $first = $properties[0];
                        echo "<h3>Sample Property from PROPERTIES container:</h3>\n";
                        echo "<pre>" . htmlspecialchars(print_r($first, true)) . "</pre>\n";
                    }
                }
            }
        }
        
        echo "<h3>Full Response Object:</h3>\n";
        echo "<pre>" . htmlspecialchars(print_r($response, true)) . "</pre>\n";
        
    } else {
        echo "❌ No GetAllPropertyResult found in response\n<br>";
    }
    
    echo "<h3>Raw XML Response (first 2000 characters):</h3>\n";
    echo "<pre>" . htmlspecialchars(substr($raw_response, 0, 2000)) . "</pre>\n";
    
} catch (SoapFault $e) {
    echo "❌ SOAP Fault: " . htmlspecialchars($e->getMessage()) . "\n<br>";
    echo "<strong>Fault Code:</strong> " . htmlspecialchars($e->faultcode) . "\n<br>";
    echo "<strong>Fault String:</strong> " . htmlspecialchars($e->faultstring) . "\n<br>";
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "\n<br>";
}

echo "<hr>";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";

?>