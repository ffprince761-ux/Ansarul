import React, { createContext, useState, useContext } from 'react';

export const SimpleLoadingContext = createContext();

export const SimpleLoadingProvider = ({ children }) => {
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState('Loading...');

  const showLoading = (msg = 'Loading...') => {
    setMessage(msg);
    setIsLoading(true);
  };

  const hideLoading = () => {
    setIsLoading(false);
  };

  return (
    <SimpleLoadingContext.Provider value={{
      isLoading,
      message,
      showLoading,
      hideLoading
    }}>
      {children}
    </SimpleLoadingContext.Provider>
  );
};

export const useSimpleLoading = () => {
  const context = useContext(SimpleLoadingContext);
  if (!context) {
    // Return fallback functions instead of throwing error
    return {
      isLoading: false,
      message: 'Loading...',
      showLoading: () => {},
      hideLoading: () => {}
    };
  }
  return context;
};
