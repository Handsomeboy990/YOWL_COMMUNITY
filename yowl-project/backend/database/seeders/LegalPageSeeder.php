<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    /**
     * Seeds the legal pages with a usable starting text.
     *
     * These are placeholders written to be edited, not to be relied on: they
     * name what each page must cover so nobody publishes an empty page, and
     * they say plainly that a lawyer has not seen them.
     */
    public function run(): void
    {
        foreach ($this->contents() as $slug => [$title, $body]) {
            LegalPage::updateOrCreate(
                ['slug' => $slug],
                ['title' => $title, 'body' => $body, 'draft_body' => $body, 'published_at' => now()]
            );
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function contents(): array
    {
        $avertissement = '<blockquote><p><strong>À relire avant mise en ligne.</strong> '
            .'Ce texte est un point de départ rédigé par l\'équipe technique. '
            .'Il n\'a pas été validé juridiquement.</p></blockquote>';

        return [
            'charte' => ['Charte de la communauté', $avertissement.'
<h2>Ce que YOWL attend de toi</h2>
<p>YOWL est un espace où l\'on partage un avis sur un contenu du web. La discussion
y est vive, et elle reste respectueuse. En publiant ici, tu acceptes ces quelques règles.</p>

<h3>Ce qui est bienvenu</h3>
<ul>
<li>Un avis argumenté, même sévère, sur un contenu, une œuvre ou une idée.</li>
<li>Le désaccord, exprimé sur le fond.</li>
<li>La correction d\'une erreur, la tienne comme celle d\'un autre.</li>
</ul>

<h3>Ce qui ne l\'est pas</h3>
<ul>
<li>L\'attaque personnelle, l\'insulte, le harcèlement.</li>
<li>Les propos haineux visant une personne ou un groupe.</li>
<li>Le contenu sexuel, la violence gratuite, l\'incitation à se faire du mal.</li>
<li>La publicité déguisée et la publication en série du même lien.</li>
<li>La diffusion d\'informations privées sans accord.</li>
</ul>

<h3>Si quelque chose ne va pas</h3>
<p>Le bouton <strong>Signaler</strong>, présent sur chaque avis et chaque commentaire,
prévient la modération. Tu peux aussi bloquer un membre : tu ne verras plus ses
publications, et vos abonnements réciproques sont retirés.</p>

<h3>Ce que la modération peut faire</h3>
<p>Retirer un contenu du fil, le supprimer, suspendre un compte. Un contenu très
signalé peut être retiré automatiquement en attendant une décision humaine. Si tu
penses qu\'il s\'agit d\'une erreur, écris-nous par le formulaire de suggestion.</p>'],

            'confidentialite' => ['Politique de confidentialité', $avertissement.'
<h2>Les données que nous conservons</h2>
<p>Créer un compte demande une adresse email, un pseudo, un nom et une date de
naissance. Nous conservons aussi ce que tu publies, tes réactions, tes abonnements
et tes signalements.</p>

<h3>Pourquoi</h3>
<ul>
<li>L\'adresse email sert à te connecter, à vérifier ton compte et à t\'envoyer le résumé hebdomadaire si tu l\'acceptes.</li>
<li>La date de naissance sert à vérifier que tu as l\'âge requis.</li>
<li>Le reste fait fonctionner le service : ton fil, tes notifications, la modération.</li>
</ul>

<h3>Ce que nous ne faisons pas</h3>
<p>Nous ne vendons aucune donnée. Nous ne les transmettons à personne en dehors des
prestataires techniques nécessaires au fonctionnement du service.</p>

<h3>Tes droits</h3>
<p>Tu peux consulter et modifier tes informations depuis ton profil. La suppression
de ton compte efface tes données personnelles : adresse, pseudo, nom, photo et date
de naissance. Tes contributions restent, rattachées à un compte anonyme, pour ne pas
trouer les discussions des autres membres.</p>

<h3>Combien de temps</h3>
<p>Tant que ton compte existe. Après suppression, les données personnelles sont
effacées immédiatement.</p>

<h3>Nous contacter</h3>
<p>Par le formulaire de suggestion, en indiquant qu\'il s\'agit d\'une demande relative
à tes données.</p>'],

            'conditions' => ["Conditions d'utilisation", $avertissement.'
<h2>L\'essentiel</h2>
<p>YOWL est un service gratuit de partage d\'avis. En l\'utilisant, tu acceptes ces
conditions et la charte de la communauté.</p>

<h3>Ton compte</h3>
<p>Un compte par personne. Tu es responsable de ce que tu publies et de la
confidentialité de ton mot de passe. L\'inscription est ouverte à partir d\'un âge
minimum indiqué au moment de l\'inscription.</p>

<h3>Ce que tu publies</h3>
<p>Tu restes propriétaire de tes textes et de tes images. Tu nous accordes le droit
de les afficher sur la plateforme tant que ton compte existe.</p>

<h3>Ce que nous pouvons faire</h3>
<p>Retirer un contenu qui enfreint la charte, suspendre un compte, et faire évoluer
le service. Une modification importante de ces conditions te sera signalée.</p>

<h3>Interruptions</h3>
<p>Le service est fourni tel quel, sans garantie de disponibilité continue.</p>'],

            'mentions-legales' => ['Mentions légales', $avertissement.'
<h2>Éditeur</h2>
<p>YOWL Community. Les coordonnées complètes de l\'éditeur, sa forme juridique et
son numéro d\'immatriculation sont à compléter avant la mise en ligne.</p>

<h3>Directeur de la publication</h3>
<p>À compléter.</p>

<h3>Hébergement</h3>
<p>Le nom et l\'adresse de l\'hébergeur sont à compléter avant la mise en ligne.</p>

<h3>Contact</h3>
<p>Par le formulaire de suggestion de la plateforme.</p>'],
        ];
    }
}
