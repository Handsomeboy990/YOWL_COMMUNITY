import { normaliserBaseUrl } from '@/services/baseUrl';

/**
 * Application configuration
 */

export const config = {
  apiBaseUrl: normaliserBaseUrl(import.meta.env.VITE_BASE_URL),
  storageBaseUrl: import.meta.env.VITE_STORAGE_URL || 'http://localhost:8000/storage',
  appName: import.meta.env.VITE_APP_NAME || 'YOWL Community',
};

/**
 * Helper to get full storage URL for a path
 * @param {string} path - Storage path (e.g., "profile/image.jpg")
 * @returns {string} Full URL
 */
export function getStorageUrl(path) {
  if (!path) return '';

  // Une adresse deja absolue est rendue telle quelle. Sans cela, un media
  // servi par un stockage objet ou par une source externe se retrouvait
  // prefixe par l'URL locale, donnant une adresse impossible et une image
  // cassee sans rien dans la console.
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) {
    return path;
  }

  // Remove leading slash if present
  const cleanPath = path.startsWith('/') ? path.substring(1) : path;
  return `${config.storageBaseUrl}/${cleanPath}`;
}

/**
 * Helper to get asset URL
 * @param {string} path - Asset path
 * @returns {string} Full URL
 */
export function getAssetUrl(path) {
  if (!path) return '';
  return new URL(path, import.meta.url).href;
}
