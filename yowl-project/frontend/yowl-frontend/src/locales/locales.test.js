import { describe, it, expect } from 'vitest';
import fr from './fr.json';
import en from './en.json';

/**
 * Les deux catalogues doivent porter exactement les mêmes clés.
 *
 * Une clé présente d'un seul côté produit, à l'écran, une phrase française au
 * milieu d'une page anglaise, sans que rien ne le signale. Ce contrôle attrape
 * la divergence au moment où elle est introduite.
 */
function flatten(objet, prefixe = '') {
  return Object.entries(objet).flatMap(([cle, valeur]) => {
    const chemin = prefixe ? `${prefixe}.${cle}` : cle;
    return typeof valeur === 'object' && valeur !== null ? flatten(valeur, chemin) : [chemin];
  });
}

describe('catalogues de traduction', () => {
  const clesFr = flatten(fr).sort();
  const clesEn = flatten(en).sort();

  it('portent les mêmes clés', () => {
    expect(clesEn).toEqual(clesFr);
  });

  it("n'ont aucune valeur vide", () => {
    for (const [langue, catalogue] of [['fr', fr], ['en', en]]) {
      for (const chemin of flatten(catalogue)) {
        const valeur = chemin.split('.').reduce((o, k) => o[k], catalogue);
        expect(String(valeur).trim(), `${langue}.${chemin} est vide`).not.toBe('');
      }
    }
  });

  it('gardent les mêmes variables dans chaque phrase', () => {
    // Ensemble, pas liste : une forme plurielle « {count} avis | {count} avis »
    // repete legitimement la meme variable dans chaque branche.
    const variables = (texte) =>
      [...new Set(String(texte).match(/\{(\w+)\}/g) ?? [])].sort();

    for (const chemin of clesFr) {
      const valeurFr = chemin.split('.').reduce((o, k) => o[k], fr);
      const valeurEn = chemin.split('.').reduce((o, k) => o[k], en);
      // Une variable oubliée dans la traduction affiche une phrase trouée.
      expect(variables(valeurEn), chemin).toEqual(variables(valeurFr));
    }
  });
});
