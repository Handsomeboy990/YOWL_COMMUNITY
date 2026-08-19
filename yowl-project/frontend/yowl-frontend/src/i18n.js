import { createI18n } from 'vue-i18n';
import fr from '@/locales/fr.json';
import en from '@/locales/en.json';

const SUPPORTED = ['fr', 'en'];
const STORAGE_KEY = 'yowl.locale';

/**
 * Choisit la langue : celle que la personne a demandée, sinon celle du
 * navigateur, sinon le français.
 */
function resolveLocale() {
  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored && SUPPORTED.includes(stored)) return stored;

  const browser = (navigator.language || 'fr').slice(0, 2).toLowerCase();
  return SUPPORTED.includes(browser) ? browser : 'fr';
}

export const i18n = createI18n({
  legacy: false,
  locale: resolveLocale(),
  fallbackLocale: 'fr',
  messages: { fr, en },
  // Une clé manquante dans une langue retombe sur le français plutôt que
  // d'afficher la clé brute à la personne.
  missingWarn: import.meta.env.DEV,
  fallbackWarn: import.meta.env.DEV,
});

export const supportedLocales = SUPPORTED;

export function setLocale(locale) {
  if (!SUPPORTED.includes(locale)) return;
  i18n.global.locale.value = locale;
  localStorage.setItem(STORAGE_KEY, locale);
  document.documentElement.setAttribute('lang', locale);
}

// La langue initiale est aussi posée sur l'élément racine, pour les lecteurs
// d'écran et pour la césure typographique.
document.documentElement.setAttribute('lang', i18n.global.locale.value);
