"""
Comprehensive Barefoot API Method Explorer
Tests various methods to retrieve property data
"""
from zeep import Client
from zeep.transports import Transport
from requests import Session
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Credentials
endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx'
wsdl = f'{endpoint}?WSDL'
username = 'hfa20250814'
password = '#20250825@xcfvgrt!54687'
barefoot_account = 'v3chfa0604'

def test_all_property_methods():
    """Test all available property-related methods"""
    
    try:
        # Initialize client
        session = Session()
        session.verify = True
        transport = Transport(session=session, timeout=30)
        client = Client(wsdl=wsdl, transport=transport)
        
        logger.info("✓ SOAP Client initialized")
        
        # Get all operations
        try:
            operations = list(client.wsdl.services[0].ports[0].binding._operations.keys())
            logger.info(f"✓ Found {len(operations)} total operations")
            
            # Filter property-related operations
            property_ops = [op for op in operations if 'property' in op.lower()]
            logger.info(f"✓ Found {len(property_ops)} property-related operations:")
            for op in property_ops:
                logger.info(f"  - {op}")
            
        except Exception as e:
            logger.error(f"Could not list operations: {str(e)}")
            # Try getting them differently
            operations = dir(client.service)
            property_ops = [op for op in operations if 'property' in op.lower() and not op.startswith('_')]
            logger.info(f"✓ Alternative method found {len(property_ops)} property operations:")
            for op in property_ops:
                logger.info(f"  - {op}")
        
        # Test parameters
        auth_params = {
            'username': username,
            'password': password,
            'barefootAccount': barefoot_account
        }
        
        print("\n" + "="*80)
        print("TESTING PROPERTY RETRIEVAL METHODS")
        print("="*80 + "\n")
        
        # Method 1: GetAllProperty
        print("1. Testing GetAllProperty...")
        try:
            result = client.service.GetAllProperty(**auth_params)
            print(f"   Response type: {type(result)}")
            print(f"   Response: {result}")
            if hasattr(result, 'PROPERTIES') and result.PROPERTIES:
                print(f"   ✓ Properties found: {len(result.PROPERTIES)}")
            else:
                print(f"   ✗ No properties or custom method response")
        except Exception as e:
            print(f"   ✗ Error: {str(e)}")
        
        # Method 2: GetProperty (no ID)
        print("\n2. Testing GetProperty (no ID)...")
        try:
            result = client.service.GetProperty(**auth_params)
            print(f"   Response type: {type(result)}")
            print(f"   Response: {result}")
        except Exception as e:
            print(f"   ✗ Error: {str(e)}")
        
        # Method 3: GetPropertyList
        print("\n3. Testing GetPropertyList...")
        try:
            result = client.service.GetPropertyList(**auth_params)
            print(f"   Response type: {type(result)}")
            print(f"   Response: {result}")
        except Exception as e:
            print(f"   ✗ Error: {str(e)}")
        
        # Method 4: Try with different parameter combinations
        print("\n4. Testing GetAllProperty with different parameters...")
        try:
            # Try without barefootAccount
            result = client.service.GetAllProperty(username=username, password=password)
            print(f"   Without barefootAccount - Response: {result}")
        except Exception as e:
            print(f"   Without barefootAccount - Error: {str(e)}")
        
        # Method 5: Test individual property IDs
        print("\n5. Testing individual property IDs (1-10)...")
        for prop_id in range(1, 11):
            try:
                # Try GetProperty with ID
                result = client.service.GetProperty(
                    username=username,
                    password=password,
                    barefootAccount=barefoot_account,
                    propertyId=str(prop_id)
                )
                if result and hasattr(result, '__dict__'):
                    print(f"   Property ID {prop_id}: Found")
                    print(f"      {result}")
                    break  # Found one, stop searching
            except Exception as e:
                if 'not found' not in str(e).lower():
                    print(f"   Property ID {prop_id}: Error - {str(e)}")
        
        # Method 6: Check what methods exist on the service
        print("\n6. Available methods on service object:")
        service_methods = [m for m in dir(client.service) if not m.startswith('_')]
        for method in service_methods[:30]:  # Show first 30
            print(f"   - {method}")
        
        print("\n" + "="*80)
        print("TESTING COMPLETE")
        print("="*80)
        
    except Exception as e:
        logger.error(f"Critical error: {str(e)}", exc_info=True)

if __name__ == "__main__":
    test_all_property_methods()
