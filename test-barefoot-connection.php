<?php
/**
 * Test script for Barefoot API connection
 * This script tests the SOAP connection to Barefoot Property Management System
 */

// Barefoot API Configuration
$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';
$version = 'v3chfa0604';

echo "<h2>Barefoot Property Management API Connection Test</h2>\n\n";

// Check if SOAP extension is available
if (!extension_loaded('soap')) {
    echo "❌ ERROR: PHP SOAP extension is not enabled\n";
    echo "Please enable the SOAP extension in your PHP configuration.\n";
    exit;
}

echo "✅ PHP SOAP extension is available\n\n";

try {
    echo "🔗 Connecting to Barefoot API...\n";
    echo "Endpoint: $endpoint\n";
    echo "Username: $username\n";
    echo "Version: $version\n\n";
    
    // SOAP client options
    $options = array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'connection_timeout' => 30,
        'user_agent' => 'WordPress Barefoot Plugin Test'
    );
    
    // Create SOAP client
    $soap_client = new SoapClient($endpoint . '?WSDL', $options);
    
    echo "✅ SOAP client created successfully\n\n";
    
    // Get available functions
    echo "📋 Available API Functions:\n";
    $functions = $soap_client->__getFunctions();
    
    if (empty($functions)) {
        echo "❌ No functions available\n";
    } else {
        echo "✅ Found " . count($functions) . " available functions:\n";
        foreach (array_slice($functions, 0, 10) as $function) {
            echo "   • " . $function . "\n";
        }
        if (count($functions) > 10) {
            echo "   ... and " . (count($functions) - 10) . " more functions\n";
        }
    }\n    
    echo "\n";
    
    // Test a simple API call
    echo "🧪 Testing GetAllProperty API call...\n";
    
    $params = array(
        'username' => $username,
        'password' => $password,
        'version' => $version,
        'includeInactive' => false
    );
    
    $response = $soap_client->GetAllProperty($params);
    
    if (isset($response->GetAllPropertyResult)) {
        $properties = $response->GetAllPropertyResult;
        
        // Handle different response formats
        if (is_object($properties)) {
            if (isset($properties->PropertyInfo)) {
                $properties = $properties->PropertyInfo;
            }
        }
        
        // Ensure we have an array
        if (!is_array($properties)) {
            $properties = array($properties);
        }
        
        echo "✅ API call successful!\n";
        echo "📊 Retrieved " . count($properties) . " properties\n\n";
        
        if (!empty($properties)) {
            echo "📋 Sample Property Data (first property):\n";
            $sample = $properties[0];
            
            if (is_object($sample)) {
                echo "   • Property ID: " . (isset($sample->PropertyId) ? $sample->PropertyId : 'N/A') . "\n";
                echo "   • Property Name: " . (isset($sample->PropertyName) ? $sample->PropertyName : 'N/A') . "\n";
                echo "   • Property Code: " . (isset($sample->PropertyCode) ? $sample->PropertyCode : 'N/A') . "\n";
                echo "   • Bedrooms: " . (isset($sample->Bedrooms) ? $sample->Bedrooms : 'N/A') . "\n";
                echo "   • Bathrooms: " . (isset($sample->Bathrooms) ? $sample->Bathrooms : 'N/A') . "\n";
                echo "   • Max Guests: " . (isset($sample->MaxGuests) ? $sample->MaxGuests : 'N/A') . "\n";
                echo "   • City: " . (isset($sample->City) ? $sample->City : 'N/A') . "\n";
                echo "   • State: " . (isset($sample->State) ? $sample->State : 'N/A') . "\n";
            } else {
                echo "   Property data format: " . gettype($sample) . "\n";
                if (is_array($sample)) {
                    echo "   Available keys: " . implode(', ', array_keys($sample)) . "\n";
                }
            }
        }
        
        echo "\n🎉 Connection test SUCCESSFUL!\n";
        echo "The plugin should work correctly with your Barefoot API.\n";
        
    } else {
        echo "⚠️  API call completed but no property data returned\n";
        echo "Response structure: " . print_r($response, true) . "\n";
    }
    
} catch (SoapFault $e) {
    echo "❌ SOAP Fault occurred:\n";
    echo "   Code: " . $e->getCode() . "\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Detail: " . $e->getTraceAsString() . "\n";
    
    if (isset($soap_client)) {
        echo "\nLast Request:\n" . $soap_client->__getLastRequest() . "\n";
        echo "\nLast Response:\n" . $soap_client->__getLastResponse() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ General error occurred:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Barefoot Property Management API Test Complete\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
?>