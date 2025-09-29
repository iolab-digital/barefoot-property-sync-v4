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
        echo "🧪 Testing with barefootAccount: '" . ($account ?? 'null') . "'\\n";
        
        try {\n            $params = array(\n                'username' => $username,\n                'password' => $password,\n                'barefootAccount' => $account\n            );\n            \n            $response = $soap_client->GetAllProperty($params);\n            \n            if (isset($response->GetAllPropertyResult)) {\n                echo "✅ SUCCESS! Found properties with account: '" . ($account ?? 'null') . "'\\n";\n                \n                $result = $response->GetAllPropertyResult;\n                if (isset($result->schema)) {\n                    echo "   Schema available: Yes\\n";\n                }\n                if (isset($result->any)) {\n                    echo "   Data available: Yes\\n";\n                    \n                    // Try to extract property count\n                    $xml_string = $result->any;\n                    if (is_string($xml_string)) {\n                        libxml_use_internal_errors(true);\n                        $xml = simplexml_load_string($xml_string);\n                        if ($xml !== false) {\n                            $properties = $xml->xpath('//Property');\n                            if ($properties) {\n                                echo "   Properties found: " . count($properties) . "\\n";\n                                \n                                // Show first property info\n                                if (!empty($properties)) {\n                                    $first = $properties[0];\n                                    echo "   Sample Property ID: " . (string)$first['PropertyId'] . "\\n";\n                                    echo "   Sample Property Name: " . (string)$first['PropertyName'] . "\\n";\n                                }\n                            }\n                        }\n                    }\n                }\n                \n                echo "\\n   🎉 FOUND WORKING CONFIGURATION! 🎉\\n";\n                echo "   Username: $username\\n";\n                echo "   Password: [hidden]\\n";\n                echo "   BarefootAccount: '" . ($account ?? 'null') . "'\\n\\n";\n                break; // Stop on first success\n                \n            } else {\n                echo "   ⚠️  No GetAllPropertyResult in response\\n";\n            }\n            \n        } catch (SoapFault $e) {\n            echo "   ❌ SOAP Fault: " . $e->getMessage() . "\\n";\n        } catch (Exception $e) {\n            echo "   ❌ Error: " . $e->getMessage() . "\\n";\n        }\n        \n        echo "\\n";\n    }\n    \n} catch (Exception $e) {\n    echo "❌ Failed to create SOAP client: " . $e->getMessage() . "\\n";\n}\n\necho "\\n================================================================================\\n";\necho "Test completed at: " . date('Y-m-d H:i:s') . "\\n";\n?>