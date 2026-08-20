import { appel, adressePropre, jeton, reglages } from './config.js';

const $ = (id) => document.getElementById(id);

const vues = {
  chargement: $('vue-chargement'),
  connexion: $('vue-connexion'),
  impossible: $('vue-impossible'),
  page: $('vue-page'),
  publie: $('vue-publie'),
};

let contexte = { url: null, titre: '', hote: '' };
let tagsChoisis = [];
let dernierAvisId = null;
let minuteurTags = null;

function montrer(nom) {
  Object.entries(vues).forEach(([cle, element]) => element.classList.toggle('cachee', cle !== nom));
}

function erreur(texte) {
  const boite = $('message');
  boite.textContent = texte;
  boite.classList.remove('cachee');
}

// ---------------------------------------------------------------------------
// Démarrage
// ---------------------------------------------------------------------------

async function demarrer() {
  const [onglet] = await chrome.tabs.query({ active: true, currentWindow: true });
  const propre = adressePropre(onglet?.url || '');

  if (!propre) {
    montrer('impossible');
    return;
  }

  contexte = {
    url: propre,
    titre: onglet.title || '',
    hote: new URL(propre).hostname.replace(/^www\./, ''),
  };

  if (!(await jeton())) {
    montrer('connexion');
    return;
  }

  $('page-titre').textContent = contexte.titre || contexte.hote;
  $('page-hote').textContent = contexte.hote;
  $('favicon').src = onglet.favIconUrl || 'icons/icon16.png';

  // Un brouillon préparé par un menu contextuel, le cas échéant.
  const { prefill } = await chrome.storage.local.get('prefill');
  if (prefill?.draft && prefill.url === contexte.url) {
    $('avis').value = prefill.draft;
    await chrome.storage.local.remove('prefill');
  }

  montrer('page');
  majCompteur();
  chargerDiscussions();
}

/**
 * Ce que la communauté a déjà dit de cette page.
 *
 * C'est la première chose affichée, avant le champ de saisie : arriver sur
 * une conversation ouverte vaut mieux que d'en ouvrir une deuxième.
 */
async function chargerDiscussions() {
  try {
    const reponse = await appel('/liens/existant?link=' + encodeURIComponent(contexte.url));

    if (reponse === null) {
      montrer('connexion');
      return;
    }

    const avis = reponse.data || [];
    if (!avis.length) {
      $('aucune').classList.remove('cachee');
      return;
    }

    $('discussions-nombre').textContent = avis.length > 1
      ? avis.length + ' personnes en parlent déjà'
      : 'Quelqu\'un en parle déjà';

    const { siteUrl } = await reglages();
    const liste = $('discussions-liste');
    liste.textContent = '';

    avis.forEach((element) => {
      const li = document.createElement('li');
      const lien = document.createElement('a');
      lien.href = siteUrl + '/reviews/' + element.id;
      lien.target = '_blank';
      lien.rel = 'noopener';

      const image = document.createElement('img');
      image.src = element.user?.picture
        ? new URL(element.user.picture, siteUrl).toString()
        : 'icons/icon16.png';
      image.alt = '';

      const bloc = document.createElement('div');
      const qui = document.createElement('p');
      qui.className = 'qui';
      qui.textContent = element.user?.fullname || element.user?.username || 'Un membre';

      const quoi = document.createElement('p');
      quoi.className = 'quoi';
      quoi.textContent = element.content || '';

      const combien = document.createElement('p');
      combien.className = 'combien';
      const n = element.comments_count ?? 0;
      combien.textContent = n === 0 ? 'aucune réponse' : n === 1 ? '1 réponse' : n + ' réponses';

      bloc.append(qui, quoi, combien);
      lien.append(image, bloc);
      li.append(lien);
      liste.append(li);
    });

    $('discussions').classList.remove('cachee');
  } catch {
    // Une proposition manquante n'empêche pas d'écrire.
    $('aucune').classList.remove('cachee');
  }
}

// ---------------------------------------------------------------------------
// Saisie
// ---------------------------------------------------------------------------

function majCompteur() {
  const restant = 2000 - $('avis').value.length;
  const compteur = $('compteur');
  compteur.textContent = restant + ' caractères restants';
  compteur.classList.toggle('proche', restant < 120);
  $('publier').disabled = $('avis').value.trim().length < 3;
}

function dessinerTags() {
  const liste = $('tags-choisis');
  liste.textContent = '';

  tagsChoisis.forEach((nom) => {
    const li = document.createElement('li');
    li.append(document.createTextNode('#' + nom));

    const retirer = document.createElement('button');
    retirer.type = 'button';
    retirer.setAttribute('aria-label', 'Retirer ' + nom);
    retirer.textContent = '×';
    retirer.addEventListener('click', () => {
      tagsChoisis = tagsChoisis.filter((t) => t !== nom);
      dessinerTags();
    });

    li.append(retirer);
    liste.append(li);
  });
}

function ajouterTag(nom) {
  const propre = nom.trim().toLowerCase().replace(/^#/, '').slice(0, 30);
  if (!propre || tagsChoisis.includes(propre) || tagsChoisis.length >= 5) return;
  tagsChoisis.push(propre);
  dessinerTags();
  $('tags').value = '';
  $('tags-suggestions').classList.add('cachee');
}

async function chercherTags(terme) {
  if (!terme || terme.length < 2) {
    $('tags-suggestions').classList.add('cachee');
    return;
  }

  try {
    const reponse = await appel('/tags');
    if (!reponse) return;

    const trouves = (reponse.data || [])
      .map((t) => t.name ?? t)
      .filter((nom) => nom.startsWith(terme.toLowerCase()) && !tagsChoisis.includes(nom))
      .slice(0, 6);

    const liste = $('tags-suggestions');
    liste.textContent = '';

    if (!trouves.length) {
      liste.classList.add('cachee');
      return;
    }

    trouves.forEach((nom) => {
      const li = document.createElement('li');
      const bouton = document.createElement('button');
      bouton.type = 'button';
      bouton.textContent = '#' + nom;
      bouton.addEventListener('click', () => ajouterTag(nom));
      li.append(bouton);
      liste.append(li);
    });

    liste.classList.remove('cachee');
  } catch {
    $('tags-suggestions').classList.add('cachee');
  }
}

// ---------------------------------------------------------------------------
// Publication
// ---------------------------------------------------------------------------

async function publier() {
  const bouton = $('publier');
  bouton.disabled = true;
  bouton.textContent = 'Publication...';
  $('message').classList.add('cachee');

  try {
    const reponse = await appel('/reviews', {
      method: 'POST',
      body: JSON.stringify({
        content: $('avis').value.trim(),
        link: contexte.url,
        tags: tagsChoisis,
      }),
    });

    if (reponse === null) {
      montrer('connexion');
      return;
    }

    dernierAvisId = reponse.data?.id ?? null;
    // Le badge de cet onglet compte une discussion de plus.
    chrome.runtime.sendMessage({ type: 'yowl-invalider-cache' });
    montrer('publie');
  } catch (exception) {
    erreur(exception.message);
    bouton.disabled = false;
    bouton.textContent = 'Publier mon avis';
  }
}

// ---------------------------------------------------------------------------
// Branchements
// ---------------------------------------------------------------------------

$('avis').addEventListener('input', majCompteur);

$('tags').addEventListener('input', (evenement) => {
  clearTimeout(minuteurTags);
  minuteurTags = setTimeout(() => chercherTags(evenement.target.value), 220);
});

$('tags').addEventListener('keydown', (evenement) => {
  if (evenement.key === 'Enter' || evenement.key === ',') {
    evenement.preventDefault();
    ajouterTag(evenement.target.value);
  } else if (evenement.key === 'Backspace' && !evenement.target.value && tagsChoisis.length) {
    tagsChoisis.pop();
    dessinerTags();
  }
});

$('publier').addEventListener('click', publier);

$('connecter').addEventListener('click', async () => {
  const { siteUrl } = await reglages();
  chrome.tabs.create({ url: siteUrl + '/extension?id=' + chrome.runtime.id });
  window.close();
});

$('reglages').addEventListener('click', () => chrome.runtime.openOptionsPage());

$('ouvrir-site').addEventListener('click', async () => {
  const { siteUrl } = await reglages();
  chrome.tabs.create({ url: siteUrl + '/feed' });
});

$('voir-avis').addEventListener('click', async () => {
  const { siteUrl } = await reglages();
  chrome.tabs.create({ url: siteUrl + (dernierAvisId ? '/reviews/' + dernierAvisId : '/feed') });
  window.close();
});

$('recommencer').addEventListener('click', () => window.close());

demarrer();
