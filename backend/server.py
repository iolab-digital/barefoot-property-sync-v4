from fastapi import FastAPI, APIRouter, HTTPException
from dotenv import load_dotenv
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
import os
import logging
from pathlib import Path
from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any
import uuid
from datetime import datetime
from barefoot_api import barefoot_api


ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / '.env')

# MongoDB connection
mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ['DB_NAME']]

# Create the main app without a prefix
app = FastAPI()

# Create a router with the /api prefix
api_router = APIRouter(prefix="/api")


# Define Models
class StatusCheck(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    client_name: str
    timestamp: datetime = Field(default_factory=datetime.utcnow)

class StatusCheckCreate(BaseModel):
    client_name: str

# Add your routes to the router instead of directly to app
@api_router.get("/")
async def root():
    return {"message": "Hello World"}

@api_router.post("/status", response_model=StatusCheck)
async def create_status_check(input: StatusCheckCreate):
    status_dict = input.dict()
    status_obj = StatusCheck(**status_dict)
    _ = await db.status_checks.insert_one(status_obj.dict())
    return status_obj

@api_router.get("/status", response_model=List[StatusCheck])
async def get_status_checks():
    status_checks = await db.status_checks.find().to_list(1000)
    return [StatusCheck(**status_check) for status_check in status_checks]

# Barefoot API endpoints
@api_router.get("/barefoot/test-connection")
async def test_barefoot_connection():
    """Test connection to Barefoot SOAP API"""
    try:
        result = barefoot_api.test_connection()
        return result
    except Exception as e:
        logger.error(f"Error testing Barefoot connection: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

@api_router.get("/barefoot/properties")
async def get_barefoot_properties():
    """Retrieve properties from Barefoot API"""
    try:
        result = barefoot_api.get_all_properties()
        return result
    except Exception as e:
        logger.error(f"Error getting Barefoot properties: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

@api_router.get("/barefoot/properties/{property_id}")
async def get_barefoot_property(property_id: int):
    """Get details for a specific property"""
    try:
        auth_params = {
            'username': barefoot_api.username,
            'password': barefoot_api.password,
            'barefootAccount': barefoot_api.barefoot_account
        }
        property_data = barefoot_api._get_property_details(property_id, auth_params)
        
        if property_data:
            return {
                'success': True,
                'property': property_data
            }
        else:
            return {
                'success': False,
                'message': f'Property {property_id} not found or unable to retrieve details'
            }
    except Exception as e:
        logger.error(f"Error getting property {property_id}: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

# Include the router in the main app
app.include_router(api_router)

app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=os.environ.get('CORS_ORIGINS', '*').split(','),
    allow_methods=["*"],
    allow_headers=["*"],
)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

@app.on_event("shutdown")
async def shutdown_db_client():
    client.close()
