<?php
/**
 * Comprehensive Barefoot API WSDL Analysis
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';

echo "<h1>Barefoot WSDL Analysis</h1>\n";

try {
    $soap_client = new SoapClient($endpoint . '?WSDL', array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ));
    
    echo "✅ SOAP client created successfully\n<br><br>";
    
    // Get and analyze all functions
    echo "<h2>1. Available SOAP Functions</h2>\n";
    $functions = $soap_client->__getFunctions();
    
    $property_functions = array();
    foreach ($functions as $function) {
        if (stripos($function, 'property') !== false || stripos($function, 'Property') !== false) {
            $property_functions[] = $function;
        }
    }
    
    echo "<h3>Property-related functions:</h3>\n";
    foreach ($property_functions as $func) {
        echo "• " . htmlspecialchars($func) . "\n<br>";
    }
    
    // Get and analyze all types
    echo "<h2>2. Available Data Types</h2>\n";
    $types = $soap_client->__getTypes();
    
    foreach ($types as $type) {
        if (stripos($type, 'GetAllProperty') !== false) {
            echo "<h3>GetAllProperty Related Types:</h3>\n";
            echo "<pre>" . htmlspecialchars($type) . "</pre>\n<br>";
        }
    }
    
    // Test different property retrieval methods
    echo "<h2>3. Testing Property Retrieval Methods</h2>\n";
    
    $test_methods = array(
        array('name' => 'GetAllProperty', 'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '')),
        array('name' => 'GetAllProperty', 'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => null)),
        array('name' => 'GetAllProperty', 'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => $username)),
        array('name' => 'GetAllProperty', 'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => 'v3chfa0604')),
        array('name' => 'GetAllProperty', 'params' => array('username' => $username, 'password' => $password)),
        array('name' => 'GetAllProperties', 'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '')),
        array('name' => 'GetProperties', 'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '')),
        array('name' => 'GetPropertyList', 'params' => array('username' => $username, 'password' => $password, 'barefootAccount' => '')),
    );
    
    foreach ($test_methods as $test) {
        echo "<h3>Testing: {$test['name']}</h3>\n";
        echo "Parameters: " . htmlspecialchars(print_r($test['params'], true)) . "\n<br>";
        
        try {
            $method = $test['name'];
            $response = $soap_client->$method($test['params']);
            
            echo "✅ Method call successful\n<br>";
            echo "<strong>Response:</strong>\n<br>";
            echo "<pre>" . htmlspecialchars(print_r($response, true)) . "</pre>\n<br>";
            
            // Check response structure
            $result_key = $method . 'Result';
            if (isset($response->$result_key)) {
                $result = $response->$result_key;
                
                // Look for different response patterns
                if (isset($result->Message)) {
                    if (strpos($result->Message, 'Custom method') !== false) {
                        echo "⚠️ Custom method message - trying to handle...\n<br>";
                    } else {
                        echo "📝 Message: " . htmlspecialchars($result->Message) . "\n<br>";
                    }
                }
                
                if (isset($result->any)) {
                    echo "✅ Found 'any' field with XML data\n<br>";
                    $xml_string = $result->any;
                    echo "<strong>XML snippet:</strong> " . htmlspecialchars(substr($xml_string, 0, 200)) . "...\n<br>";
                }
                
                if (isset($result->PROPERTIES)) {
                    echo "✅ Found PROPERTIES container!\n<br>";
                    if (isset($result->PROPERTIES->PROPERTY)) {
                        echo "🎉 Found PROPERTY data!\n<br>";
                        break; // Stop on success
                    }
                }
                
                if (isset($result->schema)) {
                    echo "✅ Found schema field\n<br>";
                }
            }
            
        } catch (SoapFault $e) {
            echo "❌ SOAP Fault: " . htmlspecialchars($e->getMessage()) . "\n<br>";
            
            // Check if method doesn't exist
            if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'Method') !== false) {
                echo "   (Method likely doesn't exist)\n<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "\n<br>";
        }
        
        echo "<hr>\n";
    }
    
    // Test authentication separately
    echo "<h2>4. Testing Simple Authentication Methods</h2>\n";
    
    $auth_methods = array('TestAuth', 'GetUrlTest', 'Test', 'Ping', 'Hello');
    
    foreach ($auth_methods as $method) {
        // Check if method exists in functions list
        $method_exists = false;
        foreach ($functions as $func) {
            if (stripos($func, $method) !== false) {
                $method_exists = true;
                break;
            }
        }
        
        if ($method_exists) {
            echo "<h3>Testing: {$method}</h3>\n";
            try {
                $response = $soap_client->$method();
                echo "✅ Success: " . htmlspecialchars(print_r($response, true)) . "\n<br>";
            } catch (Exception $e) {
                echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "\n<br>";
            }
        }
    }
    
    // Show raw WSDL endpoint for manual inspection
    echo "<h2>5. WSDL Analysis URLs</h2>\n";
    echo "WSDL URL: <a href='{$endpoint}?WSDL' target='_blank'>{$endpoint}?WSDL</a>\n<br>";
    echo "Service Description: <a href='{$endpoint}' target='_blank'>{$endpoint}</a>\n<br>";
    
} catch (Exception $e) {
    echo "❌ Error creating SOAP client: " . htmlspecialchars($e->getMessage()) . "\n<br>";
}

echo "<hr>";
echo "Analysis completed at: " . date('Y-m-d H:i:s') . "\n";

?>