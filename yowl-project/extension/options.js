import { PAR_DEFAUT, reglages } from './config.js';

const $ = (id) => document.getElementById(id);

async function peindre() {
  const { siteUrl, apiUrl } = await reglages();
  $('siteUrl').value = siteUrl;
  $('apiUrl').value = apiUrl;

  const { token, user } = await chrome.storage.local.get(['token', 'user']);
  const connectee = Boolean(token);

  $('pastille').className = 'pastille ' + (connectee ? 'on' : 'off');
  $('etat-texte').textContent = connectee
    ? 'Connectée' + (user?.username ? ' en tant que ' + user.username : '')
    : 'Non connectée';
  $('connecter').classList.toggle('cachee', connectee);
  $('deconnecter').classList.toggle('cachee', !connectee);
}

$('enregistrer').addEventListener('click', async () => {
  const site = $('siteUrl').value.trim().replace(/\/$/, '') || PAR_DEFAUT.siteUrl;
  const api = $('apiUrl').value.trim().replace(/\/$/, '') || PAR_DEFAUT.apiUrl;

  await chrome.storage.sync.set({ siteUrl: site, apiUrl: api });
  $('retour').textContent = 'Enregistré.';
  setTimeout(() => ($('retour').textContent = ''), 2200);
  peindre();
});

$('connecter').addEventListener('click', async () => {
  const { siteUrl } = await reglages();
  chrome.tabs.create({ url: siteUrl + '/extension?id=' + chrome.runtime.id });
});

$('deconnecter').addEventListener('click', async () => {
  await chrome.storage.local.remove(['token', 'user']);
  chrome.runtime.sendMessage({ type: 'yowl-invalider-cache' });
  peindre();
});

// La page reste ouverte pendant la connexion : elle doit se remettre à jour
// quand le jeton arrive, sans que personne ait à la recharger.
chrome.storage.onChanged.addListener((changements, zone) => {
  if (zone === 'local' && changements.token) peindre();
});

peindre();
