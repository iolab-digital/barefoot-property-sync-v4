<?php
/**
 * Test alternative property retrieval methods for Barefoot API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';

echo "<h1>Testing Alternative Property Methods</h1>\n";

try {
    $soap_client = new SoapClient($endpoint . '?WSDL', array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ));
    
    echo "✅ SOAP client created successfully\n<br><br>";
    
    // Test alternative methods that might work better
    $alternative_methods = array(
        array(
            'name' => 'GetProperty',
            'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '')
        ),
        array(
            'name' => 'GetPropertyExt', 
            'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '')
        ),
        array(
            'name' => 'GetPropertyDetails',
            'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '')
        ),
        array(
            'name' => 'GetLastUpdatedProperty',
            'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '')
        ),
        array(
            'name' => 'GetPropertyActiveIdx',
            'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '')
        ),
        // Try with propertyId parameter (might be required)
        array(
            'name' => 'GetProperty',
            'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '', 'propertyId' => '1')
        ),
        array(
            'name' => 'GetPropertyExt',
            'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '', 'propertyId' => '1')
        ),
        // Try GetAllPropertyOwnerId which has the same structure as GetAllProperty
        array(
            'name' => 'GetAllPropertyOwnerId',
            'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '')
        ),
    );
    
    foreach ($alternative_methods as $test) {
        echo "<h2>Testing: {$test['name']}</h2>\n";
        echo "<strong>Parameters:</strong> " . htmlspecialchars(print_r($test['params'], true)) . "\n<br>";
        
        try {
            $method = $test['name'];
            $response = $soap_client->$method($test['params']);
            
            echo "✅ Method call successful\n<br>";
            
            // Get the result key
            $result_key = $method . 'Result';
            if (isset($response->$result_key)) {
                $result = $response->$result_key;
                
                echo "<strong>Response structure:</strong>\n<br>";
                if (is_object($result)) {
                    $vars = get_object_vars($result);
                    echo "Available properties: " . implode(', ', array_keys($vars)) . "\n<br>";
                }
                
                // Look for actual data rather than "Custom method" message
                if (isset($result->Message) && strpos($result->Message, 'Custom method') !== false) {
                    echo "⚠️ Custom method message\n<br>";
                } elseif (isset($result->any) && !empty($result->any)) {
                    echo "✅ Found 'any' field with actual data!\n<br>";
                    $xml_string = $result->any;
                    echo "<strong>Data (first 500 chars):</strong>\n<br>";
                    echo "<pre>" . htmlspecialchars(substr($xml_string, 0, 500)) . "</pre>\n<br>";
                    
                    // Try to parse as XML
                    libxml_use_internal_errors(true);
                    $xml = simplexml_load_string($xml_string);
                    if ($xml !== false) {
                        echo "✅ XML parsed successfully\n<br>";
                        
                        // Count properties
                        $properties = $xml->xpath('//Property');
                        if (empty($properties)) {
                            $properties = $xml->xpath('//*[contains(name(), "Property")]');
                        }
                        
                        if (!empty($properties)) {
                            echo "🎉 Found " . count($properties) . " properties!\n<br>";
                            
                            // Show sample property
                            $sample = $properties[0];
                            echo "<strong>Sample property fields:</strong>\n<br>";
                            
                            // Show attributes
                            foreach ($sample->attributes() as $key => $value) {
                                echo "• $key: " . htmlspecialchars(substr($value, 0, 50)) . "\n<br>";
                            }
                            
                            // This looks promising, let's stop here
                            echo "<h3>🎉 SUCCESS! This method returns property data!</h3>\n";
                            break;
                        }
                    }
                } elseif (isset($result->schema)) {
                    echo "✅ Found schema field\n<br>";
                } else {
                    echo "<strong>Full response:</strong>\n<br>";
                    echo "<pre>" . htmlspecialchars(print_r($response, true)) . "</pre>\n<br>";
                }
            } else {
                echo "❌ No {$result_key} in response\n<br>";
                echo "<strong>Full response:</strong>\n<br>";
                echo "<pre>" . htmlspecialchars(print_r($response, true)) . "</pre>\n<br>";
            }
            
        } catch (SoapFault $e) {
            echo "❌ SOAP Fault: " . htmlspecialchars($e->getMessage()) . "\n<br>";
        } catch (Exception $e) {
            echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "\n<br>";
        }
        
        echo "<hr>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating SOAP client: " . htmlspecialchars($e->getMessage()) . "\n<br>";
}

echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";

?>