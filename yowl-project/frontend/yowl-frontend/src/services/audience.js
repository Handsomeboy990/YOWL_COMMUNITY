import api from '@/services/apiService';

/**
 * Signale une page consultée, sans rien qui désigne la personne.
 *
 * Le navigateur n'envoie que deux choses, le chemin et la provenance, et le
 * serveur retraite les deux : le chemin est ramené à un motif de route connu,
 * la provenance à son seul hôte. L'appareil et la qualité de membre sont
 * déduits côté serveur, jamais déclarés ici, où ils n'auraient aucune raison
 * d'être crus.
 *
 * Sur « Do Not Track » : l'en-tête n'est pas consulté, et c'est un choix.
 * Il demande de ne pas suivre une personne d'un site à l'autre, or il n'y a
 * ici ni cookie, ni adresse IP, ni identifiant, donc personne à suivre. Deux
 * visites de la même personne sont indistinguables de deux visites de deux
 * personnes, y compris pour nous.
 */

/**
 * document.referrer ne bouge pas d'une navigation à l'autre.
 *
 * Dans une application à page unique, il garde la valeur qu'il avait au
 * chargement initial pendant toute la durée de la visite. L'envoyer à chaque
 * changement de route attribuerait toutes les pages vues à la même source et
 * multiplierait son score par le nombre de pages parcourues. Il n'est donc
 * transmis qu'une fois, pour la page d'entrée, qui est la seule à laquelle il
 * se rapporte réellement.
 */
let provenanceEnvoyee = false;

/**
 * @param {string} chemin le chemin de la route atteinte
 * @returns {Promise<void>} toujours tenue, jamais rejetée
 */
export async function signalerVisite(chemin) {
  const charge = { path: chemin };

  if (!provenanceEnvoyee) {
    provenanceEnvoyee = true;
    if (typeof document !== 'undefined' && document.referrer) {
      charge.referrer = document.referrer;
    }
  }

  try {
    await api.post('/visite', charge);
  } catch {
    // Silence volontaire. Une mesure d'audience qui casse une navigation ou
    // remplit la console coûte plus qu'elle ne rapporte, et il n'y a rien
    // que la personne devant l'écran puisse faire de cette erreur.
  }
}
