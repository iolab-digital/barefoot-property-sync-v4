#!/usr/bin/env python3
"""
Test XML parsing with actual Barefoot API response
"""
import xml.etree.ElementTree as ET

# Your actual API response
xml_string = '''<string xmlns="http://www.barefoot.com/Services/"><Property> <PropertyImg> <propertyId>9068</propertyId> <imageNo>1</imageNo> <imagepath>https://photos.barefoot.com/v3chfa0604/images/properties/9068/Screenshot+2024-11-23+at+13-36-31+4700+Atlantic+Ave+Unit+104+Wildwood+NJ+08260+realtor.com%c2%ae.jpg?v=1355</imagepath> <imageDesc /> </PropertyImg> <PropertyImg> <propertyId>9068</propertyId> <imageNo>2</imageNo> <imagepath>https://photos.barefoot.com/v3chfa0604/images/properties/9068/Screenshot+2024-11-23+at+13-36-42+4700+Atlantic+Ave+Unit+104+Wildwood+NJ+08260+realtor.com%c2%ae.jpg?v=1362</imagepath> <imageDesc /> </PropertyImg> </Property></string>'''

print("Testing XML Parsing with Actual API Response")
print("=" * 50)
print()

# Remove outer <string> wrapper
if '<Property>' in xml_string:
    start = xml_string.find('<Property>')
    end = xml_string.find('</Property>') + len('</Property>')
    xml_string = xml_string[start:end]
    print("✅ Removed outer <string> wrapper")
    print(f"   Extracted: {xml_string[:100]}...")
    print()

try:
    root = ET.fromstring(xml_string)
    print(f"✅ XML parsed successfully")
    print(f"   Root tag: {root.tag}")
    print()
    
    # Find all PropertyImg elements
    property_imgs = root.findall('.//PropertyImg')
    print(f"✅ Found {len(property_imgs)} PropertyImg elements")
    print()
    
    images = []
    for img in property_imgs:
        image = {
            'property_id': img.find('propertyId').text if img.find('propertyId') is not None else '',
            'image_no': int(img.find('imageNo').text) if img.find('imageNo') is not None else 0,
            'image_url': img.find('imagepath').text if img.find('imagepath') is not None else '',
            'description': img.find('imageDesc').text if img.find('imageDesc') is not None else ''
        }
        
        if image['image_url']:
            images.append(image)
    
    # Sort by image_no
    images.sort(key=lambda x: x['image_no'])
    
    print(f"✅ Parsed {len(images)} images with URLs")
    print()
    
    # Display first 3
    for i, img in enumerate(images[:3], 1):
        print(f"Image #{img['image_no']}:")
        print(f"  Property ID: {img['property_id']}")
        print(f"  URL: {img['image_url'][:80]}...")
        print(f"  Description: {img['description'] or '(empty)'}")
        print()
    
    # Test array access
    print("Testing Array Access (like WordPress sync code):")
    print("=" * 50)
    test_image = images[0]
    url = test_image.get('image_url', '')
    desc = test_image.get('description', 'Property Image')
    
    print(f"✅ URL extracted: {'YES' if url else 'NO'}")
    print(f"   Value: {url}")
    print(f"✅ Description: {desc}")
    print()
    
    print("=" * 50)
    print("✅ XML PARSING WORKS CORRECTLY!")
    print("   The parse_property_images_xml() method should work.")
    
except Exception as e:
    print(f"❌ FAILED: {e}")
