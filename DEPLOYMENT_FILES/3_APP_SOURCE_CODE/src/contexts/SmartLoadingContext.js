import React, { createContext, useState, useContext } from 'react';

export const SmartLoadingContext = createContext();

export const SmartLoadingProvider = ({ children }) => {
  const [loadingStates, setLoadingStates] = useState({});
  const [globalLoading, setGlobalLoading] = useState(false);

  const startLoading = (key, message = 'Loading...') => {
    setLoadingStates(prev => ({
      ...prev,
      [key]: { isLoading: true, message }
    }));
    setGlobalLoading(true);
  };

  const stopLoading = (key) => {
    setLoadingStates(prev => {
      const newStates = { ...prev };
      delete newStates[key];
      return newStates;
    });
    
    // Check if any loading is still active
    setTimeout(() => {
      setLoadingStates(current => {
        if (Object.keys(current).length === 0) {
          setGlobalLoading(false);
        }
        return current;
      });
    }, 100);
  };

  const isLoading = (key) => {
    return loadingStates[key]?.isLoading || false;
  };

  const getLoadingMessage = (key) => {
    return loadingStates[key]?.message || 'Loading...';
  };

  const anyLoading = Object.keys(loadingStates).length > 0;

  return (
    <SmartLoadingContext.Provider value={{
      startLoading,
      stopLoading,
      isLoading,
      getLoadingMessage,
      anyLoading,
      globalLoading,
      loadingStates
    }}>
      {children}
    </SmartLoadingContext.Provider>
  );
};

export const useSmartLoading = () => {
  const context = useContext(SmartLoadingContext);
  if (!context) {
    throw new Error('useSmartLoading must be used within SmartLoadingProvider');
  }
  return context;
};
