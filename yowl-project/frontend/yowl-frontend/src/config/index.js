/**
 * Application configuration
 */

export const config = {
  apiBaseUrl: import.meta.env.VITE_BASE_URL || 'http://localhost:8000/api',
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
