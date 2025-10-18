import React, { useState } from 'react';
import axios from 'axios';

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const API = `${BACKEND_URL}/api`;

export default function BarefootAPITest() {
  const [connectionStatus, setConnectionStatus] = useState(null);
  const [propertiesData, setPropertiesData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const testConnection = async () => {
    setLoading(true);
    setError(null);
    setConnectionStatus(null);
    
    try {
      const response = await axios.get(`${API}/barefoot/test-connection`);
      setConnectionStatus(response.data);
    } catch (err) {
      setError(`Connection test failed: ${err.response?.data?.detail || err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const getProperties = async () => {
    setLoading(true);
    setError(null);
    setPropertiesData(null);
    
    try {
      const response = await axios.get(`${API}/barefoot/properties`);
      setPropertiesData(response.data);
    } catch (err) {
      setError(`Failed to get properties: ${err.response?.data?.detail || err.message}`);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-100 py-12 px-4">
      <div className="max-w-6xl mx-auto">
        <div className="bg-white rounded-lg shadow-lg p-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">
            Barefoot API Testing Dashboard
          </h1>
          <p className="text-gray-600 mb-8">
            Test direct connection to Barefoot Property Management SOAP API
          </p>

          {/* Action Buttons */}
          <div className="flex gap-4 mb-8">
            <button
              onClick={testConnection}
              disabled={loading}
              className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
            >
              {loading ? 'Testing...' : 'Test Connection'}
            </button>
            
            <button
              onClick={getProperties}
              disabled={loading}
              className="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
            >
              {loading ? 'Loading...' : 'Get Properties'}
            </button>
          </div>

          {/* Error Display */}
          {error && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
              <h3 className="text-red-800 font-semibold mb-2">Error</h3>
              <p className="text-red-700">{error}</p>
            </div>
          )}

          {/* Connection Status */}
          {connectionStatus && (
            <div className={`border rounded-lg p-6 mb-6 ${
              connectionStatus.success 
                ? 'bg-green-50 border-green-200' 
                : 'bg-red-50 border-red-200'
            }`}>
              <h2 className="text-xl font-bold mb-4 flex items-center gap-2">
                {connectionStatus.success ? (
                  <>
                    <span className="text-green-600">✓</span>
                    <span className="text-green-900">Connection Successful</span>
                  </>
                ) : (
                  <>
                    <span className="text-red-600">✗</span>
                    <span className="text-red-900">Connection Failed</span>
                  </>
                )}
              </h2>
              
              <div className="space-y-2 text-sm">
                <p className="text-gray-700">
                  <strong>Message:</strong> {connectionStatus.message}
                </p>
                
                {connectionStatus.endpoint && (
                  <p className="text-gray-700">
                    <strong>Endpoint:</strong> {connectionStatus.endpoint}
                  </p>
                )}
                
                {connectionStatus.total_operations && (
                  <p className="text-gray-700">
                    <strong>Total API Operations:</strong> {connectionStatus.total_operations}
                  </p>
                )}
                
                {connectionStatus.operations && connectionStatus.operations.length > 0 && (
                  <details className="mt-4">
                    <summary className="cursor-pointer font-semibold text-gray-900">
                      Available Operations (first 20)
                    </summary>
                    <ul className="mt-2 ml-4 space-y-1 max-h-60 overflow-y-auto">
                      {connectionStatus.operations.map((op, idx) => (
                        <li key={idx} className="text-xs text-gray-600 font-mono">{op}</li>
                      ))}
                    </ul>
                  </details>
                )}
              </div>
            </div>
          )}

          {/* Properties Data */}
          {propertiesData && (
            <div className="border rounded-lg p-6">
              <h2 className="text-xl font-bold mb-4 flex items-center gap-2">
                {propertiesData.success ? (
                  <>
                    <span className="text-green-600">✓</span>
                    <span className="text-green-900">Properties Retrieved</span>
                  </>
                ) : (
                  <>
                    <span className="text-yellow-600">⚠</span>
                    <span className="text-yellow-900">No Properties Found</span>
                  </>
                )}
              </h2>

              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-gray-50 p-4 rounded">
                    <p className="text-sm text-gray-600">Properties Count</p>
                    <p className="text-2xl font-bold text-gray-900">{propertiesData.count || 0}</p>
                  </div>
                  
                  {propertiesData.method_used && (
                    <div className="bg-gray-50 p-4 rounded">
                      <p className="text-sm text-gray-600">Method Used</p>
                      <p className="text-sm font-semibold text-gray-900">{propertiesData.method_used}</p>
                    </div>
                  )}
                </div>

                {propertiesData.message && (
                  <p className="text-gray-700">
                    <strong>Message:</strong> {propertiesData.message}
                  </p>
                )}

                {propertiesData.methods_tried && propertiesData.methods_tried.length > 0 && (
                  <div>
                    <p className="font-semibold text-gray-900 mb-2">Methods Tried:</p>
                    <ul className="list-disc ml-6 space-y-1">
                      {propertiesData.methods_tried.map((method, idx) => (
                        <li key={idx} className="text-sm text-gray-700">{method}</li>
                      ))}
                    </ul>
                  </div>
                )}

                {propertiesData.errors && propertiesData.errors.length > 0 && (
                  <details className="mt-4">
                    <summary className="cursor-pointer font-semibold text-red-900">
                      Errors Encountered ({propertiesData.errors.length})
                    </summary>
                    <ul className="mt-2 ml-4 space-y-2 max-h-60 overflow-y-auto">
                      {propertiesData.errors.map((err, idx) => (
                        <li key={idx} className="text-sm text-red-700 bg-red-50 p-2 rounded">{err}</li>
                      ))}
                    </ul>
                  </details>
                )}

                {propertiesData.properties && propertiesData.properties.length > 0 && (
                  <div className="mt-6">
                    <h3 className="font-bold text-lg text-gray-900 mb-4">
                      Properties Found ({propertiesData.properties.length})
                    </h3>
                    <div className="grid gap-4">
                      {propertiesData.properties.map((property, idx) => (
                        <div key={idx} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                          <div className="grid grid-cols-2 gap-2">
                            {Object.entries(property).map(([key, value]) => (
                              <div key={key} className="text-sm">
                                <span className="font-semibold text-gray-700">{key}:</span>{' '}
                                <span className="text-gray-600">{value}</span>
                              </div>
                            ))}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Instructions */}
          {!connectionStatus && !propertiesData && !error && !loading && (
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
              <h3 className="font-semibold text-blue-900 mb-3">Instructions</h3>
              <ol className="list-decimal ml-6 space-y-2 text-blue-800">
                <li>Click "Test Connection" to verify the Barefoot API is accessible</li>
                <li>If connection is successful, click "Get Properties" to retrieve property data</li>
                <li>Review the results below to see what data is being returned</li>
                <li>Check error messages if any operations fail</li>
              </ol>
            </div>
          )}
        </div>

        {/* Technical Info */}
        <div className="mt-6 bg-white rounded-lg shadow p-6">
          <h3 className="font-semibold text-gray-900 mb-3">API Configuration</h3>
          <div className="space-y-1 text-sm text-gray-700">
            <p><strong>Endpoint:</strong> https://apps.barefoottech.com/barefoot/wapi.asmx</p>
            <p><strong>Username:</strong> hfa20250814</p>
            <p><strong>Barefoot Account:</strong> v3chfa0604</p>
            <p><strong>Backend API:</strong> {API}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
