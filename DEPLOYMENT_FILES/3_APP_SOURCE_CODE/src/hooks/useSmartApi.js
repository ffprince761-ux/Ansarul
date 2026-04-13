import { useSmartLoading } from '../contexts/SmartLoadingContext';
import { API_URL } from '../services/api';

export const useSmartApi = () => {
  const { startLoading, stopLoading } = useSmartLoading();

  const apiCall = async (key, endpoint, options = {}) => {
    const loadingMessage = options.loadingMessage || 'Loading data...';
    startLoading(key, loadingMessage);
    
    try {
      const response = await fetch(`${API_URL}${endpoint}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
        ...options
      });
      
      const data = await response.json();
      return data;
    } catch (error) {
      // silent
      throw error;
    } finally {
      stopLoading(key);
    }
  };

  const postCall = async (key, endpoint, body, options = {}) => {
    const loadingMessage = options.loadingMessage || 'Saving data...';
    startLoading(key, loadingMessage);
    
    try {
      const response = await fetch(`${API_URL}${endpoint}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
        ...options
      });
      
      const data = await response.json();
      return data;
    } catch (error) {
      // silent
      throw error;
    } finally {
      stopLoading(key);
    }
  };

  return { apiCall, postCall };
};
