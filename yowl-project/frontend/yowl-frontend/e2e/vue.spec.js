import { test, expect } from '@playwright/test';

test('la landing page affiche le titre principal', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('h1')).toContainText('Ton avis sur le web');
});

test('la landing page mène vers l\'inscription', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('link', { name: 'Créer mon compte' }).click();
  await expect(page).toHaveURL(/\/signup$/);
  await expect(page.locator('h1')).toContainText("Rejoins l'aventure");
});

test('le fil est accessible aux visiteurs', async ({ page }) => {
  await page.goto('/feed');
  await expect(page.getByRole('banner')).toBeVisible();
});
