/**
 * Normalise l'adresse de l'API.
 *
 * Le suffixe /api est obligatoire côté serveur : la configuration CORS ne
 * couvre que les chemins commençant par api/. Une adresse sans ce suffixe
 * envoie chaque appel sur une route inexistante, qui répond 404 sans le
 * moindre en-tête CORS. Le navigateur ne dit alors pas « 404 » mais
 * « CORS Missing Allow Origin », ce qui envoie chercher le problème à
 * l'endroit exactement opposé.
 *
 * Plutôt que de compter sur une variable d'environnement bien remplie, on la
 * répare : la barre finale saute, et le suffixe est ajouté s'il manque.
 *
 * @param {string} brute la valeur de VITE_BASE_URL
 * @param {string} repli l'adresse de développement
 * @returns {string} une adresse toujours terminée par /api
 */
export function normaliserBaseUrl(brute, repli = 'http://localhost:8000/api') {
  const valeur = (brute ?? '').trim() || repli;
  const sansBarre = valeur.replace(/\/+$/, '');

  if (/\/api$/i.test(sansBarre)) {
    return sansBarre;
  }

  // Un avertissement à la console plutôt qu'un échec silencieux : la personne
  // qui déploie doit pouvoir corriger la variable à la source.
  if (import.meta.env?.DEV || typeof console !== 'undefined') {
    console.warn(
      `[YOWL] VITE_BASE_URL ne se termine pas par /api ("${valeur}"). ` +
      'Le suffixe a été ajouté automatiquement, mais corrige la variable : ' +
      'sans lui, les appels tombent hors du périmètre CORS du serveur.'
    );
  }

  return sansBarre + '/api';
}
