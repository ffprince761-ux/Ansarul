import { useContext } from 'react';
import { AppContext } from '../context/AppContext';
import translations from './translations';

const useTranslation = () => {
  const { language } = useContext(AppContext);
  const lang = language === 'hi' ? 'hi' : 'en';

  const t = (key) => {
    return translations[lang]?.[key] || translations['en']?.[key] || key;
  };

  return { t, lang };
};

export default useTranslation;
