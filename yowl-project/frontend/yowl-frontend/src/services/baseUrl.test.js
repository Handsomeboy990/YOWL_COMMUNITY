import { describe, expect, it, vi } from 'vitest';
import { normaliserBaseUrl } from './baseUrl';

/**
 * Une adresse d'API sans le suffixe /api envoie chaque appel hors du périmètre
 * CORS du serveur. Le navigateur ne parle alors pas de 404 mais de
 * « CORS Missing Allow Origin », ce qui envoie chercher le problème du mauvais
 * côté. Ce défaut est passé en production.
 */
describe('normaliserBaseUrl', () => {
  it('laisse une adresse déjà correcte', () => {
    expect(normaliserBaseUrl('https://api.exemple.fr/api')).toBe('https://api.exemple.fr/api');
  });

  it('ajoute le suffixe quand il manque', () => {
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    expect(normaliserBaseUrl('https://api.exemple.fr')).toBe('https://api.exemple.fr/api');
  });

  it('retire la barre finale', () => {
    expect(normaliserBaseUrl('https://api.exemple.fr/api/')).toBe('https://api.exemple.fr/api');
  });

  it('répare la barre finale sans suffixe, le cas rencontré en production', () => {
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    expect(normaliserBaseUrl('https://my-yowl.onrender.com/')).toBe('https://my-yowl.onrender.com/api');
  });

  it('prévient à la console plutôt que de réparer en silence', () => {
    const averti = vi.spyOn(console, 'warn').mockImplementation(() => {});
    normaliserBaseUrl('https://api.exemple.fr');
    expect(averti).toHaveBeenCalledOnce();
    expect(averti.mock.calls[0][0]).toContain('/api');
  });

  it('retombe sur le développement quand la variable est absente', () => {
    expect(normaliserBaseUrl(undefined)).toBe('http://localhost:8000/api');
    expect(normaliserBaseUrl('   ')).toBe('http://localhost:8000/api');
  });

  it('accepte un suffixe en majuscules', () => {
    expect(normaliserBaseUrl('https://api.exemple.fr/API')).toBe('https://api.exemple.fr/API');
  });
});
