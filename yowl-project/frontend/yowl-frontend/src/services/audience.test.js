import { beforeEach, describe, expect, it, vi } from 'vitest';

const post = vi.fn(() => Promise.resolve({ data: {} }));
vi.mock('@/services/apiService', () => ({ default: { post: (...args) => post(...args) } }));

describe('signalement de visite', () => {
  beforeEach(() => {
    post.mockClear();
    vi.resetModules();
  });

  it("n'envoie la provenance que pour la page d'entrée", async () => {
    // document.referrer ne bouge pas d'une navigation à l'autre dans une
    // application d'une seule page : l'envoyer à chaque fois attribuerait
    // toutes les pages vues à la même source et multiplierait son score par
    // le nombre de pages parcourues.
    Object.defineProperty(document, 'referrer', {
      configurable: true,
      get: () => 'https://www.google.com/search?q=quelque+chose',
    });

    const { signalerVisite } = await import('@/services/audience');

    await signalerVisite('/feed');
    await signalerVisite('/reviews/1');
    await signalerVisite('/sujets');

    expect(post).toHaveBeenCalledTimes(3);
    expect(post.mock.calls[0][1]).toEqual({
      path: '/feed',
      referrer: 'https://www.google.com/search?q=quelque+chose',
    });
    expect(post.mock.calls[1][1]).toEqual({ path: '/reviews/1' });
    expect(post.mock.calls[2][1]).toEqual({ path: '/sujets' });
  });

  it('ne remonte jamais une erreur de mesure à la navigation', async () => {
    post.mockRejectedValueOnce(new Error('réseau coupé'));

    const { signalerVisite } = await import('@/services/audience');

    // Une mesure d'audience qui casse une navigation coûte plus qu'elle ne
    // rapporte, et la personne devant l'écran ne peut rien en faire.
    await expect(signalerVisite('/feed')).resolves.toBeUndefined();
  });
});
