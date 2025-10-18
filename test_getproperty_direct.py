#!/usr/bin/env python3
"""
Direct test of Barefoot GetProperty method
"""
import sys
sys.path.insert(0, '/app/backend')

from barefoot_api import BarefootAPI
import json

def main():
    print("=" * 80)
    print("BAREFOOT API DIRECT TEST - GetProperty Method")
    print("=" * 80)
    
    # Initialize API
    api = BarefootAPI()
    
    print(f"\nUsing credentials:")
    print(f"  Username: {api.username}")
    print(f"  Password: {api.password[:5]}..." )
    print(f"  Account: {api.barefoot_account}")
    print(f"  Endpoint: {api.endpoint}")
    
    # Test connection first
    print("\n" + "-" * 80)
    print("STEP 1: Testing Connection...")
    print("-" * 80)
    conn_result = api.test_connection()
    print(f"Connection Success: {conn_result.get('success')}")
    print(f"Message: {conn_result.get('message')}")
    if 'total_operations' in conn_result:
        print(f"Available Operations: {conn_result.get('total_operations')}")
    
    # Test property retrieval
    print("\n" + "-" * 80)
    print("STEP 2: Retrieving Properties...")
    print("-" * 80)
    result = api.get_all_properties()
    
    print(f"\nSuccess: {result.get('success')}")
    print(f"Message: {result.get('message')}")
    print(f"Property Count: {result.get('count', 0)}")
    print(f"Methods Tried: {result.get('methods_tried', [])}")
    
    if result.get('errors'):
        print(f"\nErrors encountered:")
        for error in result['errors']:
            print(f"  - {error}")
    
    # Display first few properties
    properties = result.get('properties', [])
    if properties:
        print(f"\n" + "=" * 80)
        print(f"FIRST 3 PROPERTIES (of {len(properties)}):")
        print("=" * 80)
        for i, prop in enumerate(properties[:3], 1):
            print(f"\nProperty #{i}:")
            print(f"  PropertyID: {prop.get('PropertyID', 'N/A')}")
            print(f"  Name: {prop.get('name', 'N/A')}")
            print(f"  Address: {prop.get('street', 'N/A')}, {prop.get('city', 'N/A')}")
            print(f"  Status: {prop.get('status', 'N/A')}")
            print(f"  Occupancy: {prop.get('occupancy', 'N/A')}")
            # Show total fields
            print(f"  Total Fields: {len(prop)}")
    else:
        print("\nNo properties returned!")
    
    print("\n" + "=" * 80)
    print("TEST COMPLETE")
    print("=" * 80)

if __name__ == '__main__':
    main()
