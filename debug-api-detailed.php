<?php
/**
 * Advanced Barefoot API Debugging Script
 * This will help identify the exact issue with the API calls
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Advanced Barefoot API Debugging</h1>\n";

$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';
$barefootAccount = 'v3chfa0604';

try {
    // Create SOAP client with detailed debugging
    $soap_client = new SoapClient($endpoint . '?WSDL', array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'connection_timeout' => 30
    ));
    
    echo "<h2>1. SOAP Client Created Successfully</h2>\n";
    
    // Get WSDL types to understand the exact structure
    echo "<h2>2. Analyzing WSDL Types for GetAllProperty</h2>\n";
    $types = $soap_client->__getTypes();
    
    foreach ($types as $type) {
        if (stripos($type, 'GetAllProperty') !== false) {
            echo "<h3>GetAllProperty Type Definition:</h3>\n";
            echo "<pre>" . htmlspecialchars($type) . "</pre>\n";
        }
    }
    
    // Get functions to see exact method signatures
    echo "<h2>3. Method Signatures</h2>\n";
    $functions = $soap_client->__getFunctions();
    
    foreach ($functions as $function) {
        if (stripos($function, 'GetAllProperty') !== false) {
            echo "<h3>GetAllProperty Method Signature:</h3>\n";
            echo "<pre>" . htmlspecialchars($function) . "</pre>\n";
        }
    }
    
    // Test different parameter combinations
    echo "<h2>4. Testing Different Parameter Combinations</h2>\n";
    
    $test_combinations = array(
        // Test 1: Current approach
        array(
            'name' => 'Current approach (username, password, barefootAccount)',
            'params' => array(
                'username' => $username,
                'password' => $password,
                'barefootAccount' => $barefootAccount
            )
        ),
        
        // Test 2: Without barefootAccount
        array(
            'name' => 'Without barefootAccount',
            'params' => array(
                'username' => $username,
                'password' => $password
            )
        ),
        
        // Test 3: With empty barefootAccount
        array(
            'name' => 'With empty barefootAccount',
            'params' => array(
                'username' => $username,
                'password' => $password,
                'barefootAccount' => ''
            )
        ),
        
        // Test 4: Different parameter names
        array(
            'name' => 'Different parameter names (userName, passWord)',
            'params' => array(
                'userName' => $username,
                'passWord' => $password,
                'barefootAccount' => $barefootAccount
            )
        ),
        
        // Test 5: Check if method expects different structure
        array(
            'name' => 'Structured parameters',
            'params' => array(
                'credentials' => array(
                    'username' => $username,
                    'password' => $password,
                    'barefootAccount' => $barefootAccount
                )
            )
        )
    );
    
    foreach ($test_combinations as $test) {
        echo "<h3>Testing: {$test['name']}</h3>\n";
        echo "<strong>Parameters:</strong> " . htmlspecialchars(json_encode($test['params'], JSON_PRETTY_PRINT)) . "<br>\n";
        
        try {
            $response = $soap_client->GetAllProperty($test['params']);
            
            echo "✅ <strong>SUCCESS!</strong> Method call completed<br>\n";
            
            // Analyze the response
            if (isset($response->GetAllPropertyResult)) {
                $result = $response->GetAllPropertyResult;
                
                echo "<strong>Response Type:</strong> " . gettype($result) . "<br>\n";
                
                if (is_string($result)) {
                    if (strpos($result, 'Custom method') !== false) {
                        echo "⚠️ <strong>Custom method response:</strong> " . htmlspecialchars($result) . "<br>\n";
                    } else {
                        echo "<strong>String Response (first 200 chars):</strong> " . htmlspecialchars(substr($result, 0, 200)) . "<br>\n";
                    }
                } elseif (is_object($result)) {
                    $result_vars = get_object_vars($result);
                    echo "<strong>Object Properties:</strong> " . implode(', ', array_keys($result_vars)) . "<br>\n";
                    
                    // Check for any XML data
                    if (isset($result->any) && !empty($result->any)) {
                        echo "✅ <strong>Found 'any' field with data!</strong><br>\n";
                        echo "<strong>Data preview:</strong> " . htmlspecialchars(substr($result->any, 0, 300)) . "...<br>\n";
                    }
                    
                    // Check for properties structure
                    if (isset($result->PROPERTIES)) {
                        echo "✅ <strong>Found PROPERTIES container!</strong><br>\n";
                        if (isset($result->PROPERTIES->PROPERTY)) {
                            $props = is_array($result->PROPERTIES->PROPERTY) ? $result->PROPERTIES->PROPERTY : array($result->PROPERTIES->PROPERTY);
                            echo "🎉 <strong>Found " . count($props) . " properties!</strong><br>\n";
                        }
                    }
                } else {
                    echo "<strong>Response data:</strong> " . htmlspecialchars(print_r($result, true)) . "<br>\n";
                }
            } else {
                echo "❌ <strong>No GetAllPropertyResult found in response</strong><br>\n";
                echo "<strong>Full response:</strong> " . htmlspecialchars(print_r($response, true)) . "<br>\n";
            }
            
            echo "<br><strong>Last SOAP Request:</strong><br>\n";
            echo "<pre>" . htmlspecialchars($soap_client->__getLastRequest()) . "</pre><br>\n";
            
            echo "<strong>Last SOAP Response (first 1000 chars):</strong><br>\n";
            echo "<pre>" . htmlspecialchars(substr($soap_client->__getLastResponse(), 0, 1000)) . "</pre><br>\n";
            
        } catch (SoapFault $e) {
            echo "❌ <strong>SOAP Fault:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
            echo "<strong>Fault Code:</strong> " . htmlspecialchars($e->faultcode) . "<br>\n";
            echo "<strong>Fault String:</strong> " . htmlspecialchars($e->faultstring) . "<br>\n";
            
            // Show the request that caused the error
            echo "<strong>Failed SOAP Request:</strong><br>\n";
            echo "<pre>" . htmlspecialchars($soap_client->__getLastRequest()) . "</pre><br>\n";
            
        } catch (Exception $e) {
            echo "❌ <strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
        }
        
        echo "<hr>\n";
    }
    
    // Test if there are other property methods that work
    echo "<h2>5. Testing Alternative Property Methods</h2>\n";
    
    $alt_methods = array('GetProperty', 'GetPropertyExt', 'GetLastUpdatedProperty');
    
    foreach ($alt_methods as $method) {
        echo "<h3>Testing: {$method}</h3>\n";
        
        try {
            $params = array(
                'username' => $username,
                'password' => $password,
                'barefootAccount' => $barefootAccount
            );
            
            $response = $soap_client->$method($params);
            echo "✅ <strong>{$method} succeeded!</strong><br>\n";
            
            $result_key = $method . 'Result';
            if (isset($response->$result_key)) {
                $result = $response->$result_key;
                if (isset($result->any) && !empty($result->any)) {
                    echo "✅ Found data in 'any' field<br>\n";
                    echo "<strong>Data preview:</strong> " . htmlspecialchars(substr($result->any, 0, 200)) . "...<br>\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ <strong>{$method} failed:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
        }
        
        echo "<br>\n";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>\n";
    echo "<h2>Fatal Error</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>\n";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>\n";
    echo "</div>\n";
}

echo "<h2>6. Recommendations</h2>\n";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa;'>\n";
echo "<p>Based on the test results above, we can identify:</p>\n";
echo "<ul>\n";
echo "<li>The correct parameter structure for GetAllProperty</li>\n";
echo "<li>Which parameter combination returns actual data</li>\n";
echo "<li>Whether the method name is correct</li>\n";
echo "<li>The exact format of successful responses</li>\n";
echo "</ul>\n";
echo "<p>Look for the test that shows <strong>SUCCESS</strong> and actual property data.</p>\n";
echo "</div>\n";

echo "<hr>\n";
echo "<p><small>Debug completed at: " . date('Y-m-d H:i:s') . "</small></p>\n";

?>