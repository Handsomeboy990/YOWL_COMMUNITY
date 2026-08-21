import { test, expect } from '@playwright/test';

/**
 * Garde de rendu sur telephone.
 *
 * La majorite des membres arrivent par un telephone, et les deux defauts qui
 * s'y installent sans bruit sont toujours les memes : une page qui deborde
 * lateralement, et une commande trop petite pour etre visee au pouce. Aucun
 * des deux ne casse un test unitaire ni ne remonte dans la console.
 *
 * Les mesures qui suivent viennent d'un audit reel : la barre du haut donnait
 * un champ de recherche de 71 pixels de large sur un ecran de 360, et la
 * rangee d'actions des cartes portait des pastilles de 32 pixels sur
 * telephone contre 40 sur ordinateur, soit l'inverse du besoin.
 */

// 360 pixels : le format des telephones d'entree de gamme encore tres repandus.
// Ce qui tient ici tient partout ailleurs.
test.use({
  viewport: { width: 360, height: 800 },
  isMobile: true,
  hasTouch: true,
});

// En serie, pour la meme raison que l'audit d'accessibilite : plusieurs
// onglets simultanes saturent le serveur de developpement.
test.describe.configure({ mode: 'serial' });

const parcours = [
  ['/feed', 'le fil'],
  ['/login', 'la connexion'],
  ['/signup', "l'inscription"],
];

/** Le minimum confortable au pouce, en pixels CSS. */
const CIBLE_MINIMALE = 44;

for (const [chemin, nom] of parcours) {
  test(`${nom} ne deborde pas lateralement`, async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto(chemin);
    await expect(page.locator('form, main, header').first()).toBeVisible();

    const { contenu, fenetre, coupables } = await page.evaluate(() => {
      const vp = document.documentElement.clientWidth;
      const coupables = [];
      for (const el of document.querySelectorAll('body *')) {
        const r = el.getBoundingClientRect();
        if (r.width === 0 || r.height === 0) continue;
        if (r.right - vp <= 1) continue;
        // Un enfant d'element deja fautif ne dit rien de plus, et un conteneur
        // qui defile volontairement contient legitimement plus large que lui.
        const p = el.parentElement;
        if (p && p.getBoundingClientRect().right - vp > 1) continue;
        if (p && getComputedStyle(p).overflowX !== 'visible') continue;
        coupables.push(el.tagName.toLowerCase() + '.' + (el.getAttribute('class') || '').split(/\s+/).slice(0, 3).join('.'));
      }
      return { contenu: document.documentElement.scrollWidth, fenetre: vp, coupables };
    });

    expect(coupables, `elements depassant le bord droit : ${coupables.join(', ')}`).toEqual([]);
    expect(contenu, 'la page ne doit pas defiler horizontalement').toBeLessThanOrEqual(fenetre + 1);
  });
}

test('les commandes du cadre applicatif se visent au pouce', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });

  // Les appels d'API sont coupes au niveau reseau, pas laisses repondre.
  //
  // Un jeton factice recolte des 401, et l'intercepteur ferme alors la
  // session, ce qui est le comportement voulu : la cloche disparaissait du
  // document et le controle passait en n'ayant rien mesure. Une erreur
  // reseau, elle, ne declenche pas cette fermeture. On mesure une mise en
  // page, aucune donnee n'est necessaire.
  await page.route('**/api/**', (route) => route.abort());

  // Un compte est pose dans le stockage avant le premier script de la page :
  // sans lui, la cloche de notifications et le menu de profil ne sont pas
  // rendus du tout. Une premiere version de ce test laissait ainsi passer
  // une cloche ramenee a 40 pixels.
  await page.addInitScript(() => {
    localStorage.setItem('yowl.remember', '1');
    localStorage.setItem('user', JSON.stringify({
      token: 'jeton-de-mise-en-page',
      user: { id: 1, username: 'mesure', fullname: 'Compte de mesure', roles: [] },
    }));
  });

  await page.goto('/feed');
  await expect(page.locator('header').first()).toBeVisible();

  // On verifie d'abord que le cadre authentifie est bien la, sinon le
  // controle qui suit ne mesurerait de nouveau rien.
  // Portee limitee a l'en-tete : la zone de toasts de vue-sonner porte elle
  // aussi une etiquette « Notifications », et un selecteur global en attrape
  // deux.
  const enTete = page.locator('header').first();
  await expect(enTete.getByRole('button', { name: 'Notifications' })).toBeVisible();
  await expect(enTete.getByRole('button', { name: 'Menu du profil' })).toBeVisible();

  // La barre du haut et la navigation basse encadrent toutes les pages : une
  // regression a cet endroit touche le parcours entier. Les pastilles de tag
  // en sont exclues volontairement, elles tiennent 32 pixels par choix, au
  // dessus du plancher de 24 exige mais en dessous du confort.
  const trop_petites = await page.evaluate((minimum) => {
    const zones = [document.querySelector('header'), document.querySelector('nav[aria-label="Navigation mobile"]')];
    const fautifs = [];
    for (const zone of zones) {
      if (!zone) continue;
      for (const el of zone.querySelectorAll('a[href], button, input:not([type=hidden])')) {
        const s = getComputedStyle(el);
        if (s.display === 'none' || s.visibility === 'hidden') continue;
        const r = el.getBoundingClientRect();
        if (r.width === 0 || r.height === 0) continue;
        if (r.width >= minimum && r.height >= minimum) continue;
        const nom = (el.getAttribute('aria-label') || el.innerText || el.getAttribute('placeholder') || el.tagName).trim().slice(0, 30);
        fautifs.push(`${nom} (${Math.round(r.width)}x${Math.round(r.height)})`);
      }
    }
    return fautifs;
  }, CIBLE_MINIMALE);

  expect(trop_petites, `commandes sous ${CIBLE_MINIMALE} pixels`).toEqual([]);
});

test("l'ecran de demarrage disparait une fois l'application montee", async ({ page }) => {
  await page.goto('/feed');
  await expect(page.locator('main')).toBeVisible();
  // Il intercepte les touchers tant qu'il est dans le document, meme invisible.
  await expect(page.locator('#yowl-demarrage')).toHaveCount(0);
});
