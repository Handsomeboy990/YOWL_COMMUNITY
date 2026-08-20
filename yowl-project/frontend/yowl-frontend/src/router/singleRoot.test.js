import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { parse } from '@vue/compiler-sfc';
import { globSync } from 'glob';

/**
 * Chaque composant de route doit avoir une racine unique.
 *
 * App.vue enveloppe la router-view dans <Transition mode="out-in">. Un
 * composant a racines multiples produit un fragment, que Vue ne sait pas
 * animer : la transition de sortie ne se termine jamais, la vue suivante
 * n'est jamais montee, et l'ecran reste blanc jusqu'a un rechargement complet.
 * Vue le signale par un avertissement, pas par une erreur, donc rien ne casse
 * bruyamment. D'ou ce test.
 */
describe('composants de route', () => {
  const fichiers = globSync('src/{views,components/pages}/**/*.vue');

  it('en trouve un nombre plausible', () => {
    expect(fichiers.length).toBeGreaterThan(10);
  });

  it.each(fichiers)('%s a une racine unique', (fichier) => {
    const { descriptor } = parse(readFileSync(fichier, 'utf8'));
    if (!descriptor.template) return;

    const racines = descriptor.template.ast.children.filter(
      (noeud) => noeud.type === 1 || (noeud.type === 2 && noeud.content.trim())
    );

    expect(racines.length, `${fichier} expose ${racines.length} racines`).toBe(1);
  });
});
