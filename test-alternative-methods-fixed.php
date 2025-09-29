<?php
/**
 * Test alternative API methods with correct barefootAccount parameter
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';
$barefootAccount = 'v3chfa0604';

echo "<h1>Testing Alternative API Methods</h1>\n";

try {
    $soap_client = new SoapClient($endpoint . '?WSDL', array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ));
    
    $params = array(
        'username' => $username,
        'password' => $password,
        'barefootAccount' => $barefootAccount
    );
    
    // Test different property-related methods
    $methods_to_test = array(
        'GetProperty',
        'GetPropertyExt',
        'GetPropertyDetails',
        'GetLastUpdatedProperty',
        'GetPropertyIDByTerm',
        'GetAllPropertyOwnerId'
    );
    
    foreach ($methods_to_test as $method) {
        echo "<h2>Testing: {$method}</h2>\n";
        
        try {
            $response = $soap_client->$method($params);
            
            echo "✅ {$method} call successful!\n<br>";
            
            $result_key = $method . 'Result';
            if (isset($response->$result_key)) {
                $result = $response->$result_key;
                
                if (is_object($result)) {
                    $vars = get_object_vars($result);
                    echo "Response properties: " . implode(', ', array_keys($vars)) . "\n<br>";
                    
                    if (isset($result->Message)) {
                        if (strpos($result->Message, 'Custom method') !== false) {
                            echo "⚠️ Custom method message: " . htmlspecialchars($result->Message) . "\n<br>";
                        } else {
                            echo "ℹ️ Message: " . htmlspecialchars($result->Message) . "\n<br>";
                        }
                    }
                    
                    if (isset($result->any) && !empty($result->any)) {
                        echo "✅ Found 'any' field with data!\n<br>";
                        $xml_data = $result->any;
                        echo "Data sample: " . htmlspecialchars(substr($xml_data, 0, 200)) . "...\n<br>";
                        
                        // Try to parse for properties
                        libxml_use_internal_errors(true);
                        $xml = simplexml_load_string($xml_data);
                        if ($xml !== false) {
                            $properties = $xml->xpath('//Property | //property | //*[contains(name(), "Property")]');
                            if (!empty($properties)) {
                                echo "🎉 Found " . count($properties) . " property nodes!\n<br>";
                                echo "<strong>This method returned actual property data!</strong>\n<br>";
                                return; // Exit the script as we found working data
                            }
                        }
                    }
                }
            }
            
        } catch (SoapFault $e) {
            echo "❌ SOAP Fault for {$method}: " . htmlspecialchars($e->getMessage()) . "\n<br>";
            
            // Check if it's an access denied error
            if (strpos($e->getMessage(), 'access denied') !== false) {
                echo "   → Access denied for this method\n<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error for {$method}: " . htmlspecialchars($e->getMessage()) . "\n<br>";
        }
        
        echo "<br>";
    }
    
    // Test a simple method that might work
    echo "<h2>Testing Simple Methods:</h2>\n";
    
    try {
        $url_test = $soap_client->GetUrlTest();
        echo "✅ GetUrlTest successful: " . htmlspecialchars($url_test->GetUrlTestResult) . "\n<br>";
    } catch (Exception $e) {
        echo "❌ GetUrlTest failed: " . htmlspecialchars($e->getMessage()) . "\n<br>";
    }
    
    // Try to get property by specific ID (if we have one)
    echo "<h2>Testing GetPropertyInfoById (with sample ID):</h2>\n";
    try {
        $property_params = array_merge($params, array('addressid' => '1'));
        $property_response = $soap_client->GetPropertyInfoById($property_params);
        
        echo "✅ GetPropertyInfoById successful!\n<br>";
        if (isset($property_response->GetPropertyInfoByIdResult)) {
            $prop_result = $property_response->GetPropertyInfoByIdResult;
            if (isset($prop_result->any)) {
                echo "✅ Found property data!\n<br>";
                echo "<pre>" . htmlspecialchars(substr($prop_result->any, 0, 500)) . "</pre>\n<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ GetPropertyInfoById failed: " . htmlspecialchars($e->getMessage()) . "\n<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating SOAP client: " . htmlspecialchars($e->getMessage()) . "\n<br>";
}

echo "<hr>";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";

?>