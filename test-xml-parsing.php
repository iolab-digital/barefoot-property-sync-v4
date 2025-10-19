<?php
/**
 * Test XML parsing with actual Barefoot API response
 */

// Test data from your actual API response
$xml_string = '<string xmlns="http://www.barefoot.com/Services/"><Property> <PropertyImg> <propertyId>9068</propertyId> <imageNo>1</imageNo> <imagepath>https://photos.barefoot.com/v3chfa0604/images/properties/9068/Screenshot+2024-11-23+at+13-36-31+4700+Atlantic+Ave+Unit+104+Wildwood+NJ+08260+realtor.com%c2%ae.jpg?v=1355</imagepath> <imageDesc /> </PropertyImg> <PropertyImg> <propertyId>9068</propertyId> <imageNo>2</imageNo> <imagepath>https://photos.barefoot.com/v3chfa0604/images/properties/9068/Screenshot+2024-11-23+at+13-36-42+4700+Atlantic+Ave+Unit+104+Wildwood+NJ+08260+realtor.com%c2%ae.jpg?v=1362</imagepath> <imageDesc /> </PropertyImg> </Property></string>';

echo "Testing XML Parsing with Actual API Response\n";
echo "=============================================\n\n";

// Parse the XML
function parse_property_images_xml($xml_string) {
    $images = array();
    
    libxml_use_internal_errors(true);
    
    // Remove outer <string> wrapper if present
    if (strpos($xml_string, '<string') !== false && strpos($xml_string, '</string>') !== false) {
        $start = strpos($xml_string, '<Property>');
        $end = strpos($xml_string, '</Property>') + strlen('</Property>');
        if ($start !== false && $end > $start) {
            $xml_string = substr($xml_string, $start, $end - $start);
        }
    }
    
    echo "Extracted XML (after removing wrapper):\n";
    echo substr($xml_string, 0, 200) . "...\n\n";
    
    $xml = simplexml_load_string($xml_string);
    
    if ($xml !== false) {
        $property_img_nodes = $xml->xpath('//PropertyImg');
        
        echo "Found " . count($property_img_nodes) . " PropertyImg nodes\n\n";
        
        if (!empty($property_img_nodes)) {
            foreach ($property_img_nodes as $img_node) {
                $image = array(
                    'property_id' => (string)$img_node->propertyId,
                    'image_no' => (int)$img_node->imageNo,
                    'image_url' => (string)$img_node->imagepath,
                    'description' => (string)$img_node->imageDesc
                );
                
                if (!empty($image['image_url'])) {
                    $images[] = $image;
                }
            }
        }
    } else {
        $errors = libxml_get_errors();
        echo "XML parsing failed:\n";
        print_r($errors);
        libxml_clear_errors();
    }
    
    // Sort by image_no
    usort($images, function($a, $b) {
        return $a['image_no'] - $b['image_no'];
    });
    
    return $images;
}

$images = parse_property_images_xml($xml_string);

echo "Parsed Images:\n";
echo "==============\n";
if (!empty($images)) {
    echo "✅ SUCCESS! Parsed " . count($images) . " images\n\n";
    
    foreach ($images as $img) {
        echo "Image #{$img['image_no']}:\n";
        echo "  Property ID: {$img['property_id']}\n";
        echo "  URL: {$img['image_url']}\n";
        echo "  Description: " . ($img['description'] ?: '(empty)') . "\n\n";
    }
    
    // Test array access (what sync_property_images does)
    echo "Testing Array Access (like in sync code):\n";
    echo "=========================================\n";
    $test_image = $images[0];
    $url = isset($test_image['image_url']) ? $test_image['image_url'] : '';
    $desc = isset($test_image['description']) ? $test_image['description'] : 'Property Image';
    
    echo "✅ URL extracted: " . (!empty($url) ? "YES" : "NO") . "\n";
    echo "   Value: {$url}\n";
    echo "✅ Description: {$desc}\n";
    
} else {
    echo "❌ FAILED: No images parsed\n";
}

echo "\n";
