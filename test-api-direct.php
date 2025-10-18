<?php
/**
 * Direct API Test - Bypassing WordPress
 * This script directly tests the Barefoot SOAP API connection
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Barefoot API Direct Test</h1>\n";
echo "<pre>\n";

// Check SOAP extension
if (!extension_loaded('soap')) {
    die("ERROR: PHP SOAP extension is not enabled.\n");
}
echo "✓ PHP SOAP extension is enabled\n\n";

// API Configuration
$endpoint = 'https://apps.barefoottech.com/barefoot/wapi.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';
$barefootAccount = 'v3chfa0604';

echo "Configuration:\n";
echo "  Endpoint: {$endpoint}\n";
echo "  Username: {$username}\n";
echo "  Account: {$barefootAccount}\n\n";

try {
    echo "Step 1: Creating SOAP Client...\n";
    
    $options = array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'connection_timeout' => 30
    );
    
    $client = new SoapClient($endpoint . '?WSDL', $options);
    echo "✓ SOAP Client created successfully\n\n";
    
    echo "Step 2: Testing basic connection with GetUrlTest...\n";
    $response = $client->GetUrlTest();
    if (isset($response->GetUrlTestResult)) {
        echo "✓ GetUrlTest successful: {$response->GetUrlTestResult}\n\n";
    } else {
        echo "✗ GetUrlTest failed - no result\n\n";
    }
    
    echo "Step 3: Getting available functions...\n";
    $functions = $client->__getFunctions();
    echo "✓ Found " . count($functions) . " total API methods\n";
    
    // Count property-related functions
    $property_functions = array_filter($functions, function($func) {
        return stripos($func, 'property') !== false;
    });
    echo "✓ Found " . count($property_functions) . " property-related methods\n\n";
    
    echo "Step 4: Testing GetAllProperty...\n";
    $params = array(
        'username' => $username,
        'password' => $password,
        'barefootAccount' => $barefootAccount
    );
    
    try {
        $response = $client->GetAllProperty($params);
        echo "GetAllProperty Response:\n";
        var_dump($response);
        echo "\n";
        
        if (isset($response->GetAllPropertyResult)) {
            $result = $response->GetAllPropertyResult;
            if (isset($result->any)) {
                echo "XML Response:\n";
                echo htmlspecialchars($result->any);
                echo "\n\n";
            } else {
                echo "No 'any' property in result\n";
                echo "Result structure:\n";
                var_dump($result);
                echo "\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ GetAllProperty failed: " . $e->getMessage() . "\n\n";
    }
    
    echo "Step 5: Testing GetProperty (without ID)...\n";
    try {
        $response = $client->GetProperty($params);
        echo "GetProperty Response:\n";
        var_dump($response);
        echo "\n\n";
    } catch (Exception $e) {
        echo "✗ GetProperty failed: " . $e->getMessage() . "\n\n";
    }
    
    echo "Step 6: Testing individual property retrieval (ID 1-5)...\n";
    for ($id = 1; $id <= 5; $id++) {
        try {
            $property_params = array_merge($params, array('addressid' => (string)$id));
            $response = $client->GetPropertyInfoById($property_params);
            
            if (isset($response->GetPropertyInfoByIdResult->any)) {
                $xml_data = $response->GetPropertyInfoByIdResult->any;
                
                if (strpos($xml_data, '<Success>true</Success>') !== false) {
                    echo "✓ Property ID {$id}: EXISTS\n";
                    
                    // Try to get detailed property info
                    try {
                        $detail_params = array_merge($params, array('propertyId' => (string)$id));
                        $detail_response = $client->GetProperty($detail_params);
                        
                        if (isset($detail_response->GetPropertyResult->any)) {
                            echo "  Detailed XML:\n";
                            $xml = $detail_response->GetPropertyResult->any;
                            echo "  " . substr(htmlspecialchars($xml), 0, 200) . "...\n";
                        }
                    } catch (Exception $e) {
                        echo "  Could not get details: " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "  Property ID {$id}: Does not exist\n";
                }
            }
        } catch (Exception $e) {
            echo "  Property ID {$id}: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    echo "Step 7: Testing GetPropertyExt...\n";
    try {
        $response = $client->GetPropertyExt($params);
        echo "GetPropertyExt Response:\n";
        var_dump($response);
        echo "\n\n";
    } catch (Exception $e) {
        echo "✗ GetPropertyExt failed: " . $e->getMessage() . "\n\n";
    }
    
    echo "Step 8: Getting last SOAP request and response...\n";
    echo "Last Request:\n";
    echo htmlspecialchars($client->__getLastRequest());
    echo "\n\nLast Response:\n";
    echo htmlspecialchars($client->__getLastResponse());
    echo "\n";
    
} catch (Exception $e) {
    echo "\n✗ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>\n";
