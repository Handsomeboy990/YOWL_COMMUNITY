import { test, expect } from '@playwright/test';
import fs from 'node:fs';

/**
 * Contrôle d'accessibilité automatisé.
 *
 * L'audit ne remplace pas un test au clavier ni un lecteur d'écran, mais il
 * attrape les régressions les plus courantes : un bouton sans nom, un texte
 * sous le contraste minimum, un champ sans étiquette. Il tourne sur les
 * parcours qu'une personne traverse réellement.
 *
 * Vue DevTools est retiré du document avant l'analyse : il n'existe que sur
 * le serveur de développement et pose lui-même un aria-label interdit.
 */
const axe = fs.readFileSync('./node_modules/axe-core/axe.min.js', 'utf8');

// En série : lancés en parallèle, plusieurs onglets sollicitent le serveur de
// développement en même temps et une page peut ne pas être prête à temps, ce
// qui fait échouer un contrôle sans qu'il y ait de régression.
test.describe.configure({ mode: 'serial' });

const parcours = [
  ['/feed', 'le fil'],
  ['/login', 'la connexion'],
  ['/signup', "l'inscription"],
  ['/suggestion', 'les suggestions'],
];

for (const [chemin, nom] of parcours) {
  test(`aucune violation grave sur ${nom}`, async ({ page }) => {
    // Mouvement réduit : les animations d'entrée partent d'une opacité nulle,
    // et un audit lancé pendant leur déroulement mesure des couleurs
    // intermédiaires qui n'existent jamais à l'écran. Ce réglage les neutralise
    // et vérifie du même coup que l'application le respecte.
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto(chemin);
    // On attend que l'application ait rendu quelque chose, plutôt qu'un délai
    // arbitraire. Un titre ne convient pas : l'inscription n'en porte pas au
    // premier écran.
    await expect(page.locator('form, main, header').first()).toBeVisible();
    await page.addScriptTag({ content: axe });

    const resultat = await page.evaluate(async () =>
      window.axe.run(
        // Le panneau Vue DevTools n'existe que sur le serveur de
        // développement et n'est pas dans le build de production : il est
        // exclu par son nom plutôt que retiré du document.
        { exclude: [['[class*="vue-devtools"]'], ['#vue-devtools-anchor']] },
        { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa'] } }
      )
    );

    const graves = resultat.violations.filter((v) => ['serious', 'critical'].includes(v.impact));
    const resume = graves
      .map((v) => `${v.id} (${v.nodes.length}) : ${v.nodes[0]?.html?.slice(0, 90)}`)
      .join(' | ');

    expect(graves, resume).toEqual([]);
  });
}

test('le lien d\'évitement est la première cible du clavier', async ({ page }) => {
  await page.goto('/feed');
  await expect(page.locator('header').first()).toBeVisible();
  // Aucun clic avant la tabulation : cliquer déplace le point de départ du
  // parcours et le lien d'évitement n'est alors plus le premier atteint.
  await page.keyboard.press('Tab');

  const focus = await page.evaluate(() => document.activeElement?.textContent?.trim());
  expect(focus).toBe('Aller au contenu');
});
