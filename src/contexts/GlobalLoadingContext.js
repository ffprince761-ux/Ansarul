import React, { createContext, useState, useContext } from 'react';

export const GlobalLoadingContext = createContext();

export const GlobalLoadingProvider = ({ children }) => {
  const [isLoading, setIsLoading] = useState(false);

  const showLoading = () => {
    setIsLoading(true);
  };

  const hideLoading = () => {
    setIsLoading(false);
  };

  return (
    <GlobalLoadingContext.Provider value={{
      isLoading,
      showLoading,
      hideLoading
    }}>
      {children}
    </GlobalLoadingContext.Provider>
  );
};

export const useGlobalLoading = () => {
  const context = useContext(GlobalLoadingContext);
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
