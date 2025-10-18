"""
Barefoot SOAP API Integration
Provides direct access to Barefoot Property Management API
"""
import logging
from zeep import Client
from zeep.exceptions import Fault
from zeep.transports import Transport
from requests import Session
import xml.etree.ElementTree as ET

logger = logging.getLogger(__name__)

class BarefootAPI:
    """Wrapper for Barefoot Property Management SOAP API"""
    
    def __init__(self):
        self.endpoint = 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx'
        self.wsdl = f'{self.endpoint}?WSDL'
        self.username = 'hfa20250814'
        self.password = '#20250825@xcfvgrt!54687'
        self.barefoot_account = 'v3chfa0604'
        self.client = None
        
    def _init_client(self):
        """Initialize SOAP client with proper configuration"""
        try:
            session = Session()
            session.verify = True
            transport = Transport(session=session, timeout=30)
            
            self.client = Client(
                wsdl=self.wsdl,
                transport=transport
            )
            logger.info("Barefoot SOAP client initialized successfully")
            return True
        except Exception as e:
            logger.error(f"Failed to initialize SOAP client: {str(e)}")
            return False
    
    def test_connection(self):
        """Test basic API connectivity"""
        try:
            if not self.client:
                if not self._init_client():
                    return {
                        'success': False,
                        'message': 'Failed to initialize SOAP client'
                    }
            
            # Get available operations first
            try:
                operations = [op.name for op in self.client.wsdl.services[0].ports[0].binding._operations.values()]
                logger.info(f"Found {len(operations)} operations in WSDL")
            except Exception as e:
                logger.error(f"Error getting operations: {str(e)}")
                operations = []
            
            # Try a simple test method
            try:
                response = self.client.service.GetUrlTest()
                logger.info(f"GetUrlTest response: {response}")
                
                return {
                    'success': True,
                    'message': 'Successfully connected to Barefoot API',
                    'endpoint': str(response) if response else 'Connected',
                    'total_operations': len(operations),
                    'operations': operations[:20]  # Show first 20 operations
                }
            except Exception as test_error:
                logger.error(f"GetUrlTest failed: {str(test_error)}, trying alternative verification")
                
                # If GetUrlTest fails, but we have operations, we're still connected
                if len(operations) > 0:
                    return {
                        'success': True,
                        'message': f'Connected to Barefoot API (WSDL loaded successfully)',
                        'endpoint': self.endpoint,
                        'total_operations': len(operations),
                        'operations': operations[:20],
                        'note': 'GetUrlTest method not available, but WSDL loaded successfully'
                    }
                else:
                    raise test_error
            
        except Fault as fault:
            error_msg = f"SOAP Fault: {str(fault)}"
            logger.error(f"SOAP Fault in test_connection: {fault}")
            return {
                'success': False,
                'message': error_msg
            }
        except Exception as e:
            error_msg = f"Connection Error: {str(e)}"
            logger.error(f"Error in test_connection: {str(e)}", exc_info=True)
            return {
                'success': False,
                'message': error_msg
            }
    
    def get_all_properties(self):
        """Attempt to retrieve all properties using various methods"""
        try:
            if not self.client:
                if not self._init_client():
                    return {
                        'success': False,
                        'message': 'Failed to initialize SOAP client'
                    }
            
            auth_params = {
                'username': self.username,
                'password': self.password,
                'barefootAccount': self.barefoot_account
            }
            
            results = {
                'success': False,
                'properties': [],
                'methods_tried': [],
                'errors': []
            }
            
            # Method 1: Try GetAllProperty
            try:
                logger.info("Attempting GetAllProperty...")
                response = self.client.service.GetAllProperty(**auth_params)
                results['methods_tried'].append('GetAllProperty')
                
                if response:
                    logger.info(f"GetAllProperty response type: {type(response)}")
                    logger.info(f"GetAllProperty response: {response}")
                    
                    # Try to parse the response
                    if hasattr(response, 'any'):
                        properties = self._parse_xml_response(response.any)
                        if properties:
                            results['properties'].extend(properties)
                            results['success'] = True
                            results['method_used'] = 'GetAllProperty'
                    else:
                        results['errors'].append(f"GetAllProperty returned: {str(response)}")
                        
            except Fault as fault:
                error_msg = f"GetAllProperty SOAP Fault: {str(fault)}"
                logger.error(error_msg)
                results['errors'].append(error_msg)
            except Exception as e:
                error_msg = f"GetAllProperty error: {str(e)}"
                logger.error(error_msg)
                results['errors'].append(error_msg)
            
            # Method 2: Try GetProperty without parameters
            if not results['success']:
                try:
                    logger.info("Attempting GetProperty without ID...")
                    response = self.client.service.GetProperty(**auth_params)
                    results['methods_tried'].append('GetProperty (no ID)')
                    
                    if response and hasattr(response, 'any'):
                        properties = self._parse_xml_response(response.any)
                        if properties:
                            results['properties'].extend(properties)
                            results['success'] = True
                            results['method_used'] = 'GetProperty (no ID)'
                            
                except Exception as e:
                    error_msg = f"GetProperty error: {str(e)}"
                    logger.error(error_msg)
                    results['errors'].append(error_msg)
            
            # Method 3: Try individual property IDs
            if not results['success']:
                logger.info("Attempting individual property retrieval by ID...")
                properties_found = self._get_properties_by_id(auth_params)
                if properties_found:
                    results['properties'] = properties_found
                    results['success'] = True
                    results['method_used'] = 'GetProperty by ID range'
                    results['methods_tried'].append('GetProperty by ID (1-20)')
            
            # Add summary
            results['count'] = len(results['properties'])
            if results['success']:
                results['message'] = f"Successfully retrieved {results['count']} properties"
            else:
                results['message'] = 'Failed to retrieve properties using all available methods'
            
            return results
            
        except Exception as e:
            logger.error(f"Critical error in get_all_properties: {str(e)}")
            return {
                'success': False,
                'message': f'Critical Error: {str(e)}',
                'properties': [],
                'count': 0
            }
    
    def _get_properties_by_id(self, auth_params, max_id=20):
        """Try to retrieve properties by testing individual IDs"""
        properties = []
        
        for prop_id in range(1, max_id + 1):
            try:
                # Try GetPropertyInfoById
                params = {**auth_params, 'addressid': str(prop_id)}
                response = self.client.service.GetPropertyInfoById(**params)
                
                if response and hasattr(response, 'any'):
                    xml_str = response.any
                    
                    # Check if property exists
                    if '<Success>true</Success>' in xml_str:
                        logger.info(f"Property ID {prop_id} exists")
                        
                        # Try to get detailed info
                        property_data = self._get_property_details(prop_id, auth_params)
                        if property_data:
                            properties.append(property_data)
                        else:
                            # At least add basic info
                            properties.append({
                                'PropertyID': prop_id,
                                'Name': f'Property {prop_id}',
                                'Status': 'Found but details unavailable'
                            })
                
                # Limit to prevent too many API calls
                if len(properties) >= 10:
                    logger.info(f"Found {len(properties)} properties, stopping search")
                    break
                    
            except Exception as e:
                logger.debug(f"Property ID {prop_id}: {str(e)}")
                continue
        
        return properties
    
    def _get_property_details(self, prop_id, auth_params):
        """Get detailed information for a specific property"""
        try:
            # Try different methods to get details
            methods = [
                ('GetProperty', 'propertyId'),
                ('GetPropertyExt', 'propertyId'),
            ]
            
            for method_name, param_name in methods:
                try:
                    params = {**auth_params, param_name: str(prop_id)}
                    method = getattr(self.client.service, method_name)
                    response = method(**params)
                    
                    if response and hasattr(response, 'any'):
                        property_data = self._parse_property_xml(response.any, prop_id)
                        if property_data:
                            logger.info(f"Got details for property {prop_id} using {method_name}")
                            return property_data
                            
                except Exception as e:
                    logger.debug(f"{method_name} failed for property {prop_id}: {str(e)}")
                    continue
            
        except Exception as e:
            logger.error(f"Error getting property details for {prop_id}: {str(e)}")
        
        return None
    
    def _parse_property_xml(self, xml_str, prop_id):
        """Parse property XML data"""
        try:
            root = ET.fromstring(xml_str)
            
            property_data = {
                'PropertyID': str(prop_id)
            }
            
            # Extract all child elements
            for child in root:
                tag = child.tag.split('}')[-1]  # Remove namespace
                text = child.text
                if text and text.strip():
                    property_data[tag] = text.strip()
            
            # Extract attributes
            for attr_name, attr_value in root.attrib.items():
                if attr_value and attr_value.strip():
                    property_data[attr_name] = attr_value.strip()
            
            # Ensure we have at least a name
            if 'Name' not in property_data and 'PropertyName' not in property_data:
                property_data['Name'] = f'Property {prop_id}'
            
            return property_data
            
        except Exception as e:
            logger.error(f"Error parsing property XML: {str(e)}")
            return None
    
    def _parse_xml_response(self, xml_str):
        """Parse XML response to extract property list"""
        try:
            root = ET.fromstring(xml_str)
            properties = []
            
            # Try different XPath patterns to find properties
            property_elements = root.findall('.//*[contains(local-name(), "Property")]')
            
            if not property_elements:
                property_elements = [root]  # Maybe the root itself is the property
            
            for prop_elem in property_elements:
                prop_data = {}
                
                # Get attributes
                for attr_name, attr_value in prop_elem.attrib.items():
                    prop_data[attr_name] = attr_value
                
                # Get child elements
                for child in prop_elem:
                    tag = child.tag.split('}')[-1]  # Remove namespace
                    prop_data[tag] = child.text if child.text else ''
                
                if prop_data:
                    properties.append(prop_data)
            
            return properties
            
        except Exception as e:
            logger.error(f"Error parsing XML response: {str(e)}")
            return []

# Create a singleton instance
barefoot_api = BarefootAPI()
