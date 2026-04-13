import { useCallback } from 'react';
import { useLoading } from './LoadingProvider';

const API_BASE_URL = 'https://yourdomain.com/api'; // Change this to your API URL

export const useApi = () => {
  const { showLoading, hideLoading } = useLoading();

  const fetchData = useCallback(async (endpoint, options = {}) => {
    const { 
      method = 'GET', 
      body = null, 
      headers = {},
      showLoader = true,
      loaderText = 'Loading...'
    } = options;

    // Show loading if enabled
    if (showLoader) {
      showLoading(loaderText);
    }

    try {
      const response = await fetch(`${API_BASE_URL}/${endpoint}`, {
        method,
        headers: {
          'Content-Type': 'application/json',
          ...headers,
        },
        body: body ? JSON.stringify(body) : null,
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Something went wrong');
      }

      return { success: true, data };
    } catch (error) {
      console.error('API Error:', error);
      return { success: false, error: error.message };
    } finally {
      // Always hide loading
      if (showLoader) {
        hideLoading();
      }
    }
  }, [showLoading, hideLoading]);

  // Convenience methods
  const get = useCallback((endpoint, options = {}) => {
    return fetchData(endpoint, { ...options, method: 'GET' });
  }, [fetchData]);

  const post = useCallback((endpoint, body, options = {}) => {
    return fetchData(endpoint, { ...options, method: 'POST', body });
  }, [fetchData]);

  const put = useCallback((endpoint, body, options = {}) => {
    return fetchData(endpoint, { ...options, method: 'PUT', body });
  }, [fetchData]);

  const del = useCallback((endpoint, options = {}) => {
    return fetchData(endpoint, { ...options, method: 'DELETE' });
  }, [fetchData]);

  return { get, post, put, delete: del, fetchData };
};

// Hook for initial app loading with data fetching
export const useAppLoader = () => {
  const { showLoading, hideLoading } = useLoading();

  const loadInitialData = useCallback(async () => {
    showLoading('Loading app...');
    
    try {
      // Fetch all initial data here
      const [userData, settings, dashboard] = await Promise.all([
        fetchUserData(),
        fetchSettings(),
        fetchDashboard(),
      ]);

      return {
        success: true,
        data: { userData, settings, dashboard }
      };
    } catch (error) {
      return { success: false, error: error.message };
    } finally {
      hideLoading();
    }
  }, [showLoading, hideLoading]);

  return { loadInitialData };
};

// Helper functions (implement these based on your API)
async function fetchUserData() {
  // const response = await fetch(`${API_BASE_URL}/auth/me`);
  // return response.json();
  return {}; // Placeholder
}

async function fetchSettings() {
  return {}; // Placeholder
}

async function fetchDashboard() {
  return {}; // Placeholder
}
