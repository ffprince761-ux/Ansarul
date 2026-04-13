import { useLoading } from '../contexts/LoadingContext';
import { API_URL } from './api';

export const useApiService = () => {
  const { showLoading, hideLoading } = useLoading();

  const fetchWithLoading = async (endpoint, options = {}) => {
    showLoading('Loading data...');
    try {
      const response = await fetch(`${API_URL}${endpoint}`, options);
      const data = await response.json();
      return data;
    } finally {
      hideLoading();
    }
  };

  return {
    get: (endpoint) => fetchWithLoading(endpoint),
    post: (endpoint, body) => fetchWithLoading(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    })
  };
};
