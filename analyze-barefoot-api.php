<?php
/**
 * Analyze Barefoot API structure to understand correct parameters
 */

$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';

echo "🔍 Analyzing Barefoot API Structure...\n\n";

try {
    $soap_client = new SoapClient($endpoint . '?WSDL', array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ));
    
    // Get function signatures
    $functions = $soap_client->__getFunctions();
    
    echo "🔎 Looking for GetAllProperty function signature:\n";
    foreach ($functions as $function) {
        if (stripos($function, 'GetAllProperty') !== false) {
            echo "   • " . $function . "\n";
        }
    }
    
    echo "\n🔎 Looking for other property-related functions:\n";
    $property_functions = array();
    foreach ($functions as $function) {
        if (stripos($function, 'property') !== false || stripos($function, 'Property') !== false) {
            $property_functions[] = $function;
        }
    }
    
    // Show first 20 property functions
    foreach (array_slice($property_functions, 0, 20) as $function) {
        echo "   • " . $function . "\n";
    }
    
    if (count($property_functions) > 20) {
        echo "   ... and " . (count($property_functions) - 20) . " more property functions\n";
    }
    
    echo "\n🔎 Looking for authentication/test functions:\n";
    foreach ($functions as $function) {
        if (stripos($function, 'test') !== false || stripos($function, 'Test') !== false || 
            stripos($function, 'auth') !== false || stripos($function, 'Auth') !== false) {
            echo "   • " . $function . "\n";
        }
    }
    
    // Get types to understand data structures
    echo "\n🔎 Analyzing data types:\n";
    $types = $soap_client->__getTypes();
    
    foreach ($types as $type) {
        if (stripos($type, 'GetAllProperty') !== false) {
            echo "   • " . $type . "\n";
        }
    }
    
    echo "\n🧪 Trying simpler test calls...\n";
    
    // Try GetUrlTest if available
    foreach ($functions as $function) {
        if (stripos($function, 'GetUrlTest') !== false) {
            echo "Testing GetUrlTest function...\n";
            try {
                $result = $soap_client->GetUrlTest();
                echo "✅ GetUrlTest successful: " . print_r($result, true) . "\n";
            } catch (Exception $e) {
                echo "❌ GetUrlTest failed: " . $e->getMessage() . "\n";
            }
            break;
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n================================================================================\n";
?>