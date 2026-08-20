import { test, expect } from '@playwright/test';

/**
 * Attend que l'application soit montee.
 *
 * Le serveur de developpement compile a la premiere requete : la premiere
 * page d'une session peut arriver plusieurs secondes apres le chargement du
 * document, ce qui faisait echouer la premiere assertion venue selon l'ordre
 * d'execution. Attendre le montage plutot que d'allonger chaque delai vise la
 * vraie condition prealable.
 */
async function attendreLApplication(page) {
  await expect(page.locator('#app')).not.toBeEmpty({ timeout: 30000 });
}

test('la landing page affiche le titre principal', async ({ page }) => {
  await page.goto('/');
  await attendreLApplication(page);
  await expect(page.locator('h1')).toContainText('Ton avis sur le web');
});

test('la landing page mène vers l\'inscription', async ({ page }) => {
  await page.goto('/');
  await attendreLApplication(page);
  await page.getByRole('link', { name: 'Créer mon compte' }).click();
  await expect(page).toHaveURL(/\/signup$/);
  await expect(page.locator('h1')).toContainText("Rejoins l'aventure");
});

test('le fil est accessible aux visiteurs', async ({ page }) => {
  await page.goto('/feed');
  await attendreLApplication(page);
  await expect(page.getByRole('banner')).toBeVisible();
});
