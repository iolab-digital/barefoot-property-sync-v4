<?php
/**
 * Deep analysis of Barefoot GetAllProperty custom method response
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx';
$username = 'hfa20250814';
$password = '#20250825@xcfvgrt!54687';

echo "<h1>Deep Analysis of GetAllProperty Response</h1>\n";

try {
    $soap_client = new SoapClient($endpoint . '?WSDL', array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ));
    
    echo "✅ SOAP client created successfully\n<br><br>";
    
    // Call GetAllProperty
    $params = array(
        'username' => $username,
        'password' => $password,
        'barefootAccount' => ''
    );
    
    echo "<h2>Calling GetAllProperty with empty barefootAccount</h2>\n";
    
    try {
        $response = $soap_client->GetAllProperty($params);
        
        echo "✅ GetAllProperty call successful\n<br>";
        
        // Analyze the full raw response
        echo "<h3>Raw SOAP Response Analysis:</h3>\n";
        $raw_response = $soap_client->__getLastResponse();
        echo "<strong>Full Raw Response:</strong>\n<br>";
        echo "<pre>" . htmlspecialchars($raw_response) . "</pre>\n<br>";
        
        // Try to parse the raw XML manually
        echo "<h3>Manual XML Parsing:</h3>\n";
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw_response);
        
        if ($xml !== false) {
            echo "✅ Raw XML parsed successfully\n<br>";
            
            // Register namespaces
            $namespaces = $xml->getNamespaces(true);
            echo "<strong>Available namespaces:</strong> " . implode(', ', array_keys($namespaces)) . "\n<br>";
            
            foreach ($namespaces as $prefix => $uri) {
                if ($prefix !== '') {
                    $xml->registerXPathNamespace($prefix, $uri);
                }
            }
            
            // Look for GetAllPropertyResult
            $results = $xml->xpath('//GetAllPropertyResult');
            if (empty($results)) {
                // Try with namespace
                $results = $xml->xpath('//*[local-name()="GetAllPropertyResult"]');
            }
            
            if (!empty($results)) {
                echo "✅ Found GetAllPropertyResult element\n<br>";
                $result_element = $results[0];
                
                // Check what's inside the result element
                echo "<strong>GetAllPropertyResult contents:</strong>\n<br>";
                
                // Look for child elements
                $children = $result_element->children();
                foreach ($children as $child) {
                    echo "• " . $child->getName() . ": " . htmlspecialchars((string)$child) . "\n<br>";
                }
                
                // Check for 'any' element specifically
                $any_elements = $result_element->xpath('.//*[local-name()="any"]');
                if (!empty($any_elements)) {
                    echo "✅ Found 'any' element!\n<br>";
                    $any_content = (string)$any_elements[0];
                    echo "<strong>'any' element content:</strong>\n<br>";
                    echo "<pre>" . htmlspecialchars($any_content) . "</pre>\n<br>";
                    
                    // Try to parse the 'any' content as XML
                    $inner_xml = simplexml_load_string($any_content);
                    if ($inner_xml !== false) {
                        echo "✅ 'any' content parsed as XML\n<br>";
                        echo "<strong>Inner XML structure:</strong>\n<br>";
                        echo "<pre>" . htmlspecialchars($inner_xml->asXML()) . "</pre>\n<br>";
                    }
                }
                
                // Check for 'schema' element
                $schema_elements = $result_element->xpath('.//*[local-name()="schema"]');
                if (!empty($schema_elements)) {
                    echo "✅ Found 'schema' element!\n<br>";
                    echo "<strong>'schema' element content:</strong>\n<br>";
                    echo "<pre>" . htmlspecialchars((string)$schema_elements[0]) . "</pre>\n<br>";
                }
                
            } else {
                echo "❌ No GetAllPropertyResult found in XML\n<br>";
                echo "<strong>Available elements:</strong>\n<br>";
                $all_elements = $xml->xpath('//*');
                foreach ($all_elements as $element) {
                    echo "• " . $element->getName() . "\n<br>";
                }
            }
            
        } else {
            echo "❌ Failed to parse raw XML\n<br>";
            $xml_errors = libxml_get_errors();
            foreach ($xml_errors as $error) {
                echo "XML Error: " . htmlspecialchars($error->message) . "\n<br>";
            }
        }
        
        // Also check the parsed response object
        echo "<h3>Parsed Response Object Analysis:</h3>\n";
        echo "<strong>Response object:</strong>\n<br>";
        echo "<pre>" . htmlspecialchars(print_r($response, true)) . "</pre>\n<br>";
        
        if (isset($response->GetAllPropertyResult)) {
            $result = $response->GetAllPropertyResult;
            
            // Try to access properties that might not be visible in print_r
            echo "<strong>Testing direct property access:</strong>\n<br>";
            
            $test_props = array('any', 'schema', 'Message', 'Properties', 'PROPERTIES', 'Property', 'PROPERTY');
            foreach ($test_props as $prop) {
                if (isset($result->$prop)) {
                    echo "✅ Found property '$prop'\n<br>";
                    $value = $result->$prop;
                    if (is_string($value)) {
                        echo "   Value (first 200 chars): " . htmlspecialchars(substr($value, 0, 200)) . "\n<br>";
                    } else {
                        echo "   Type: " . gettype($value) . "\n<br>";
                    }
                } else {
                    echo "❌ Property '$prop' not found\n<br>";
                }
            }
        }
        
    } catch (SoapFault $e) {
        echo "❌ SOAP Fault: " . htmlspecialchars($e->getMessage()) . "\n<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating SOAP client: " . htmlspecialchars($e->getMessage()) . "\n<br>";
}

echo "<hr>";
echo "Analysis completed at: " . date('Y-m-d H:i:s') . "\n";

?>