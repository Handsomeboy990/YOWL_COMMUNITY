import { describe, it, expect, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import ConfirmDialog from './ConfirmDialog.vue';
import { useConfirm } from '@/composables/useConfirm';

/**
 * Le dialogue remplace les confirmations SweetAlert sur une quarantaine
 * d'emplacements, dont des suppressions definitives. Ce qui doit tenir : la
 * promesse se resout, avec la bonne reponse, et jamais en suspens.
 */
describe('ConfirmDialog', () => {
  let wrapper;

  afterEach(() => {
    wrapper?.unmount();
    document.body.innerHTML = '';
  });

  function mountDialog() {
    wrapper = mount(ConfirmDialog, { attachTo: document.body });
    return useConfirm();
  }

  it('reste ferme tant que personne ne demande de confirmation', () => {
    mountDialog();
    expect(document.body.querySelector('[role="dialog"]')).toBeNull();
  });

  it('affiche le titre et le message demandes', async () => {
    const confirm = mountDialog();
    confirm({ title: 'Supprimer ce commentaire ?', message: 'Action definitive.' });
    await nextTick();

    const dialog = document.body.querySelector('[role="dialog"]');
    expect(dialog).not.toBeNull();
    expect(dialog.textContent).toContain('Supprimer ce commentaire ?');
    expect(dialog.textContent).toContain('Action definitive.');
  });

  it('resout a true quand on confirme', async () => {
    const confirm = mountDialog();
    const answer = confirm({ title: 'Confirmer ?', confirmLabel: 'Oui' });
    await nextTick();

    const buttons = [...document.body.querySelectorAll('button')];
    buttons.find((b) => b.textContent.trim() === 'Oui').click();

    await expect(answer).resolves.toBe(true);
  });

  it('resout a false quand on annule', async () => {
    const confirm = mountDialog();
    const answer = confirm({ title: 'Confirmer ?', cancelLabel: 'Non' });
    await nextTick();

    const buttons = [...document.body.querySelectorAll('button')];
    buttons.find((b) => b.textContent.trim() === 'Non').click();

    await expect(answer).resolves.toBe(false);
  });

  it('resout a false la demande precedente si une autre la remplace', async () => {
    const confirm = mountDialog();
    const first = confirm({ title: 'Premiere' });
    const second = confirm({ title: 'Seconde' });
    await nextTick();

    // La premiere ne doit pas rester en suspens.
    await expect(first).resolves.toBe(false);

    const buttons = [...document.body.querySelectorAll('button')];
    buttons.find((b) => b.textContent.trim() === 'Confirmer').click();
    await expect(second).resolves.toBe(true);
  });
});
