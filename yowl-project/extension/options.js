import { nav } from './browser.js';
import { PAR_DEFAUT, membre, oublierSession, reglages } from './config.js';

const $ = (id) => document.getElementById(id);

async function peindre() {
  const { siteUrl, apiUrl } = await reglages();
  $('siteUrl').value = siteUrl;
  $('apiUrl').value = apiUrl;

  const qui = await membre();
  const connectee = Boolean(qui);

  $('pastille').className = 'pastille-etat ' + (connectee ? 'on' : 'off');
  $('etat-texte').textContent = connectee
    ? 'Connectée en tant que ' + (qui.username ?? 'membre')
    : 'Non connectée';

  $('ouvrir-panneau').classList.toggle('cachee', connectee);
  $('deconnecter').classList.toggle('cachee', !connectee);

  $('version').textContent = nav.runtime.getManifest().version;
}

function annoncer(texte) {
  $('retour').textContent = texte;
  setTimeout(() => ($('retour').textContent = ''), 2400);
}

$('enregistrer').addEventListener('click', async () => {
  const site = $('siteUrl').value.trim().replace(/\/$/, '') || PAR_DEFAUT.siteUrl;
  const api = $('apiUrl').value.trim().replace(/\/$/, '') || PAR_DEFAUT.apiUrl;

  await nav.sync.ecrire({ siteUrl: site, apiUrl: api });
  // Changer d'installation périme ce qui vient de l'ancienne.
  nav.diffuser({ type: 'yowl-invalider-cache' });
  annoncer('Enregistré.');
  peindre();
});

$('defaut').addEventListener('click', async () => {
  await nav.sync.ecrire({ siteUrl: PAR_DEFAUT.siteUrl, apiUrl: PAR_DEFAUT.apiUrl });
  nav.diffuser({ type: 'yowl-invalider-cache' });
  annoncer('Valeurs par défaut rétablies.');
  peindre();
});

$('ouvrir-panneau').addEventListener('click', () => {
  // On ne peut pas ouvrir le panneau depuis une page : on explique où cliquer.
  annoncer("Clique l'icône YOWL dans la barre d'outils du navigateur.");
});

$('deconnecter').addEventListener('click', async () => {
  await oublierSession();
  peindre();
});

// La page reste ouverte pendant qu'on se connecte dans le panneau : elle doit
// se mettre à jour sans que personne ait à la recharger.
nav.runtime.onMessage.addListener((message) => {
  if (message?.type === 'yowl-session-changee') peindre();
});

peindre();
