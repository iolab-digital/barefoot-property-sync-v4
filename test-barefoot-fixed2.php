<?php
/**
 * Fixed test for Barefoot API with correct parameters
 */

$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';
$version = 'v3chfa0604';

echo "<h2>Barefoot API Test - Fixed Version</h2>\n\n";

try {
    $soap_client = new SoapClient($endpoint . '?WSDL', array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ));
    
    echo "✅ SOAP client created successfully\n\n";
    
    // Try different variations for barefootAccount parameter
    $account_variations = array(
        '', // empty string
        null,
        $username, // same as username
        'hfa', // prefix of username
        $version, // version string
        'default'
    );
    
    foreach ($account_variations as $account) {
        echo "🧪 Testing with barefootAccount: '" . ($account ?? 'null') . "'\n";
        
        try {
            $params = array(
                'username' => $username,
                'password' => $password,
                'barefootAccount' => $account
            );
            
            $response = $soap_client->GetAllProperty($params);
            
            if (isset($response->GetAllPropertyResult)) {
                echo "✅ SUCCESS! Found properties with account: '" . ($account ?? 'null') . "'\n";
                
                $result = $response->GetAllPropertyResult;
                if (isset($result->schema)) {
                    echo "   Schema available: Yes\n";
                }
                if (isset($result->any)) {
                    echo "   Data available: Yes\n";
                    
                    // Try to extract property count
                    $xml_string = $result->any;
                    if (is_string($xml_string)) {
                        libxml_use_internal_errors(true);
                        $xml = simplexml_load_string($xml_string);
                        if ($xml !== false) {
                            $properties = $xml->xpath('//Property');
                            if ($properties) {
                                echo "   Properties found: " . count($properties) . "\n";
                                
                                // Show first property info
                                if (!empty($properties)) {
                                    $first = $properties[0];
                                    echo "   Sample Property ID: " . (string)$first['PropertyId'] . "\n";
                                    echo "   Sample Property Name: " . (string)$first['PropertyName'] . "\n";
                                }
                            }
                        }
                    }
                }
                
                echo "\n   🎉 FOUND WORKING CONFIGURATION! 🎉\n";
                echo "   Username: $username\n";
                echo "   Password: [hidden]\n";
                echo "   BarefootAccount: '" . ($account ?? 'null') . "'\n\n";
                break; // Stop on first success
                
            } else {
                echo "   ⚠️  No GetAllPropertyResult in response\n";
            }
            
        } catch (SoapFault $e) {
            echo "   ❌ SOAP Fault: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Failed to create SOAP client: " . $e->getMessage() . "\n";
}

echo "\n================================================================================\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
?>