import React, { createContext, useState, useContext } from 'react';
import LoadingScreen from './LoadingScreen';

export const LoadingContext = createContext();

export const LoadingProvider = ({ children }) => {
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('Loading...');

  const showLoading = (msg = 'Loading...') => {
    setMessage(msg);
    setLoading(true);
  };

  const hideLoading = () => {
    setLoading(false);
  };

  return (
    <LoadingContext.Provider value={{ showLoading, hideLoading }}>
      {children}
      {loading && <LoadingScreen message={message} />}
    </LoadingContext.Provider>
  );
};

export const useLoading = () => {
  return useContext(LoadingContext);
};
