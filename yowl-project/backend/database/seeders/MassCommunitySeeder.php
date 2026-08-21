<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\HelpfulVote;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewReaction;
use App\Models\Suggestion;
use App\Models\Tag;
use App\Models\User;
use App\Support\FeedScore;
use App\Support\LinkNormaliser;
use App\Support\SeedImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class MassCommunitySeeder extends Seeder
{
    /** Mot de passe commun à tous les comptes de démonstration. */
    private const MOT_DE_PASSE = 'Password@990';

    /**
     * Domaine des adresses posees par ce seed.
     *
     * Il sert a deux choses qui doivent designer le meme ensemble, sous peine
     * d'effacer autre chose que ce qui a ete pose : reconnaitre qu'un jeu de
     * donnees est deja en base, et le retirer.
     */
    private const DOMAINE_SEED = 'yowl.test';

    /** Nombre de membres à créer. */
    private const MEMBRES = 1500;

    /** Bornes du nombre d'avis par domaine. */
    private const AVIS_MIN = 30;
    private const AVIS_MAX = 290;

    /**
     * Images téléchargées une fois puis réutilisées.
     *
     * Une image par avis voudrait dire six mille téléchargements : des heures,
     * et une limitation de débit bien avant la fin. Un fonds partagé donne le
     * même résultat visuel pour un coût fixe.
     */
    /**
     * Illustrations téléchargées par domaine, et non pour tout le site.
     *
     * Un fonds commun tiré au hasard donnait des photos sans rapport avec ce
     * qu'elles illustraient : un avis sur un match sous une image de plage.
     * Chaque domaine porte désormais son mot-clé, et ses avis puisent dans
     * son propre fonds.
     */
    private const FONDS_PAR_DOMAINE = 5;
    private const FONDS_AVATARS = 120;

    private array $catalogue;
    private array $illustrations = [];
    private array $avatars = [];

    public function run(): void
    {
        $this->catalogue = require __DIR__.'/data/domaines.php';

        $this->command?->warn('Ce seed écrit beaucoup de données. Comptez plusieurs minutes.');

        if (! $this->lesImagesIrontAuBonEndroit()) {
            return;
        }

        if (! $this->laBaseEstPrete()) {
            return;
        }

        $this->preparerRoles();
        $this->telechargerLeFonds();

        $tags = $this->creerLesTags();
        $membres = $this->creerLesMembres();
        $avis = $this->creerLesAvis($membres, $tags);

        $this->creerLesCommentaires($membres, $avis);
        $this->creerLesReactions($membres, $avis);
        $this->creerLesAbonnements($membres, $tags);
        $this->creerLesEnregistrements($membres, $avis);
        $this->creerLaModeration($membres, $avis);
        $this->creerLesSuggestions($membres);

        $this->recalculerLesScores();
        $this->resumer();
    }

    // -------------------------------------------------------------------------
    // Préparation
    // -------------------------------------------------------------------------

    /**
     * Refuse d'écrire des lignes distantes et des images locales.
     *
     * Le piège est silencieux et coûteux. Lancé depuis une machine de
     * développement contre la base de production, le seed écrit ses lignes
     * chez l'hébergeur et ses deux cent dix images sur le disque de la
     * machine, parce que MEDIA_DISK n'y est pas renseigné et retombe sur
     * « public ». La base référence alors des chemins que le stockage de
     * production ne contient pas : le site affiche des cadres vides, sans
     * rien dans les journaux ni dans la console du navigateur.
     *
     * Le contrôle vient avant tout le reste, y compris avant le
     * téléchargement du fonds : découvrir le problème après plusieurs minutes
     * d'écriture, et devoir tout purger, est exactement ce qu'il évite.
     */
    private function lesImagesIrontAuBonEndroit(): bool
    {
        $disque = config('filesystems.media');
        $ecritEnLocal = in_array($disque, ['local', 'public'], true);

        $connexion = config('database.default');
        $pilote = config('database.connections.'.$connexion.'.driver');
        $hote = config('database.connections.'.$connexion.'.host');

        // SQLite est un fichier, jamais un hôte : le couple est forcément
        // cohérent, quel que soit ce que traîne la clé host.
        $baseDistante = $pilote !== 'sqlite'
            && $hote
            && ! in_array($hote, ['127.0.0.1', 'localhost', '::1', ''], true);

        if (! $ecritEnLocal || ! $baseDistante) {
            return true;
        }

        $this->command?->newLine();
        $this->command?->error('Les images n\'arriveraient pas au même endroit que les lignes.');
        $this->command?->newLine();
        $this->command?->line('  base de données : '.$hote.' (distante)');
        $this->command?->line('  disque media    : '.$disque.' (cette machine)');
        $this->command?->newLine();
        $this->command?->line('  Les deux cent dix images seraient écrites ici, et la base');
        $this->command?->line('  distante pointerait vers des fichiers que le stockage de');
        $this->command?->line('  production ne contient pas. Le site afficherait des cadres');
        $this->command?->line('  vides, sans erreur nulle part.');
        $this->command?->newLine();
        $this->command?->line('  Renseignez le stockage objet avant de relancer :');
        $this->command?->line('    MEDIA_DISK=s3');
        $this->command?->line('    AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_BUCKET,');
        $this->command?->line('    AWS_ENDPOINT, AWS_URL, AWS_DEFAULT_REGION');
        $this->command?->newLine();
        $this->command?->line('  Si les images sont déjà sur cette machine, elles se poussent');
        $this->command?->line('  sans rejouer le seed :');
        $this->command?->line('    php artisan yowl:media-push --source=public --target=s3');
        $this->command?->newLine();

        return false;
    }

    /**
     * Refuse d'écrire par-dessus un jeu de données déjà posé.
     *
     * Les pseudos sont dérivés du rang de chaque membre, donc une seconde
     * exécution régénère exactement les mêmes adresses et bute sur la
     * contrainte d'unicité, à mi-parcours, après avoir déjà téléchargé le
     * fonds d'images. C'est ce qui arrive quand une première exécution a été
     * interrompue : la base garde ses membres, et rien ne le signale avant
     * l'erreur SQL.
     *
     * Le refus est le comportement par défaut plutôt que la purge : effacer
     * quinze cents comptes et leurs contenus ne se décide pas à la place de
     * la personne qui lance la commande.
     */
    private function laBaseEstPrete(): bool
    {
        $dejaPose = User::where('email', 'like', '%@'.self::DOMAINE_SEED)->count();

        if ($dejaPose === 0) {
            return true;
        }

        if (! self::purgeDemandee()) {
            $this->command?->newLine();
            $this->command?->error('La base contient déjà '.$dejaPose.' comptes de démonstration.');
            $this->command?->newLine();
            $this->command?->line('  Rejouer ce seed écrirait les mêmes adresses et échouerait sur');
            $this->command?->line('  la contrainte d\'unicité, à mi-parcours.');
            $this->command?->newLine();
            $this->command?->line('  Pour repartir d\'un jeu propre, retirer le précédent puis rejouer :');
            $this->command?->line('    YOWL_SEED_FRESH=1 php artisan db:seed --class=MassCommunitySeeder --force');
            $this->command?->newLine();
            $this->command?->line('  Seuls les comptes en @'.self::DOMAINE_SEED.' et leurs contenus sont');
            $this->command?->line('  retirés. Les comptes réels, les réglages et les pages du site');
            $this->command?->line('  ne sont pas touchés.');
            $this->command?->newLine();
            $this->command?->line('  Si une exécution s\'est arrêtée pendant le calcul des scores, il');
            $this->command?->line('  n\'y a rien à reprendre : php artisan yowl:refresh-scores suffit.');
            $this->command?->newLine();

            return false;
        }

        $this->purgerLeSeedPrecedent($dejaPose);

        return true;
    }

    /**
     * La purge se demande par variable d'environnement.
     *
     * db:seed ne transmet pas d'option libre au seeder, et une constante à
     * éditer dans le fichier se retrouve tôt ou tard commitée à vrai.
     */
    private static function purgeDemandee(): bool
    {
        return filter_var(env('YOWL_SEED_FRESH', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Retire le jeu de données précédent, et lui seul.
     *
     * Les avis, commentaires, réactions, abonnements, enregistrements et
     * signalements partent en cascade avec leur auteur. Les suggestions, elles,
     * sont rattachées par une clé nullable : sans suppression explicite elles
     * survivraient à leur auteur, orphelines et impossibles à distinguer
     * ensuite d'une vraie suggestion.
     *
     * Les tags restent : ils sont partagés avec le reste du site.
     */
    private function purgerLeSeedPrecedent(int $total): void
    {
        $this->command?->info('Retrait du jeu de données précédent ('.$total.' comptes)...');
        $barre = $this->command?->getOutput()->createProgressBar($total);

        User::where('email', 'like', '%@'.self::DOMAINE_SEED)
            ->select('id')
            ->chunkById(200, function ($lot) use ($barre) {
                $ids = $lot->pluck('id');
                DB::table('suggestions')->whereIn('user_id', $ids)->delete();
                User::whereIn('id', $ids)->delete();
                $barre?->advance($ids->count());
            });

        $barre?->finish();
        $this->command?->newLine(2);
    }

    private function preparerRoles(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
    }

    /**
     * Le fonds d'images, téléchargé une seule fois.
     */
    private function telechargerLeFonds(): void
    {
        $this->command?->info('Téléchargement du fonds d\'images...');
        $barre = $this->command?->getOutput()->createProgressBar(
            count($this->catalogue['domaines']) * self::FONDS_PAR_DOMAINE + self::FONDS_AVATARS
        );

        foreach ($this->catalogue['domaines'] as $cle => $domaine) {
            $motCle = $domaine['image'] ?? null;

            for ($i = 0; $i < self::FONDS_PAR_DOMAINE; $i++) {
                $chemin = SeedImage::illustration('yowl-illu-'.$cle.'-'.$i, $motCle);
                if ($chemin) {
                    $this->illustrations[$cle][] = $chemin;
                }
                $barre?->advance();
            }
        }

        for ($i = 0; $i < self::FONDS_AVATARS; $i++) {
            $chemin = SeedImage::avatar('yowl-avatar-'.$i);
            if ($chemin) {
                $this->avatars[] = $chemin;
            }
            $barre?->advance();
        }

        $barre?->finish();
        $this->command?->newLine(2);
        $this->command?->line(
            '  '.array_sum(array_map('count', $this->illustrations)).' illustrations sur '
            .count($this->illustrations).' domaines, '
            .count($this->avatars).' avatars'
        );
    }

    /**
     * @return array<string, Tag>
     */
    private function creerLesTags(): array
    {
        $tags = [];

        foreach ($this->catalogue['domaines'] as $domaine) {
            foreach ($domaine['tags'] as $nom) {
                $tags[$nom] ??= Tag::firstOrCreate(['name' => $nom]);
            }
        }

        return $tags;
    }
    // -------------------------------------------------------------------------
    // Les membres
    // -------------------------------------------------------------------------

    /** Prénoms, volontairement variés d'origine comme une vraie communauté. */
    private const PRENOMS = [
        'Camille','Noah','Inès','Lucas','Yasmine','Théo','Awa','Hugo','Léa','Malik',
        'Jade','Nathan','Sofia','Enzo','Chloé','Ayoub','Manon','Rayan','Louise','Ismaël',
        'Emma','Gabriel','Fatou','Adam','Alice','Mehdi','Sarah','Ethan','Lina','Amine',
        'Clara','Liam','Nour','Raphaël','Zoé','Sacha','Maya','Antoine','Elsa','Karim',
        'Julia','Marius','Aya','Victor','Anaïs','Idriss','Romane','Nolan','Salomé','Bilal',
        'Margot','Tom','Naïma','Arthur','Céline','Yanis','Juliette','Maxime','Assia','Robin',
    ];

    /** Noms de famille, même intention. */
    private const NOMS = [
        'Moreau','Traoré','Renard','Bernard','Diallo','Lefèvre','Nguyen','Garcia','Petit','Benali',
        'Dubois','Silva','Marchand','Roux','Kaci','Fontaine','Costa','Leroy','Sissoko','Girard',
        'Mercier','Ferreira','Blanchard','Amrani','Chevalier','Rossi','Barbier','Ndiaye','Perrin','Lambert',
        'Guerin','Fernandez','Muller','Boucher','Cissé','Dumont','Lopez','Carpentier','Bakri','Noël',
    ];

    /**
     * Les membres, chacun rattaché à un domaine qui décide de son pseudo.
     *
     * Un pseudo tiré au hasard ne dit rien. Ici « pixel_camille » ou
     * « bivouac_awa » annonce ce que la personne vient lire, ce qui rend le
     * fil crédible dès la première lecture.
     *
     * @return array<int, User>
     */
    private function creerLesMembres(): array
    {
        $this->command?->info('Création des '.self::MEMBRES.' membres...');
        $barre = $this->command?->getOutput()->createProgressBar(self::MEMBRES);

        $cles = array_keys($this->catalogue['domaines']);
        $hachage = Hash::make(self::MOT_DE_PASSE);
        $membres = [];

        // Les pseudos deja en base entrent dans l'ensemble : un membre reel
        // peut avoir pris l'un de ceux que ce seed genere, et la collision ne
        // se verrait qu'a l'insertion.
        $pris = User::pluck('username')->flip()->all();

        for ($i = 0; $i < self::MEMBRES; $i++) {
            $domaine = $cles[$i % count($cles)];
            $prenom = self::PRENOMS[$i % count(self::PRENOMS)];
            $nom = self::NOMS[intdiv($i, count(self::PRENOMS)) % count(self::NOMS)];
            $fragments = $this->catalogue['domaines'][$domaine]['pseudos'];

            $pseudo = $this->pseudoUnique($fragments[$i % count($fragments)], $prenom, $pris);
            $pris[$pseudo] = true;

            // Une communauté a des anciens et des nouveaux : la date
            // d'inscription s'étale sur deux ans, plus dense récemment.
            $anciennete = (int) round(730 * (mt_rand(0, 100) / 100) ** 1.6);

            $membres[] = [
                'username' => $pseudo,
                'fullname' => $prenom.' '.$nom,
                'email' => Str::lower($pseudo).'@yowl.test',
                'password' => $hachage,
                'birthdate' => now()->subYears(mt_rand(14, 34))->subDays(mt_rand(0, 364))->toDateString(),
                'picture' => $this->avatars ? $this->avatars[$i % count($this->avatars)] : null,
                'email_verified_at' => now()->subDays($anciennete),
                'is_active' => mt_rand(1, 100) > 2,
                'digest_optin' => mt_rand(1, 100) <= 35,
                'digest_token' => Str::random(40),
                'email_token' => Str::random(40),
                'created_at' => now()->subDays($anciennete),
                'updated_at' => now()->subDays(mt_rand(0, max(1, $anciennete))),
                'domaine' => $domaine,
            ];

            $barre?->advance();
        }

        // Insertion par paquets : quinze cents requêtes unitaires prendraient
        // plusieurs minutes sur une base distante.
        foreach (array_chunk($membres, 200) as $paquet) {
            User::insert(array_map(fn ($m) => \Illuminate\Support\Arr::except($m, ['domaine']), $paquet));
        }

        $barre?->finish();
        $this->command?->newLine(2);

        $crees = User::whereIn('username', array_column($membres, 'username'))
            ->get()->keyBy('username');

        $roleClient = Role::where('name', 'client')->first();
        foreach (array_chunk($crees->pluck('id')->all(), 500) as $paquet) {
            DB::table('model_has_roles')->insertOrIgnore(array_map(
                fn ($id) => ['role_id' => $roleClient->id, 'model_type' => User::class, 'model_id' => $id],
                $paquet
            ));
        }

        // Le domaine de chacun sert ensuite à écrire des avis cohérents.
        $resultat = [];
        foreach ($membres as $m) {
            $modele = $crees->get($m['username']);
            if ($modele) {
                $modele->domaine = $m['domaine'];
                $resultat[] = $modele;
            }
        }

        return $resultat;
    }

    private function pseudoUnique(string $fragment, string $prenom, array $pris): string
    {
        // La coupe vient avant la boucle, pas apres : appliquee au retour, elle
        // ramene deux pseudos distincts a la meme valeur et defait le travail
        // de la boucle sans que rien ne le signale. La base est raccourcie
        // assez pour qu'un suffixe a quatre chiffres tienne encore.
        $base = Str::limit(Str::slug($fragment.'-'.$prenom, '_'), 24, '');
        $pseudo = $base;
        $suffixe = 2;

        while (isset($pris[$pseudo])) {
            $pseudo = $base.$suffixe;
            $suffixe++;
        }

        return $pseudo;
    }
    // -------------------------------------------------------------------------
    // Les avis
    // -------------------------------------------------------------------------

    /**
     * Entre trente et deux cent quatre-vingt-dix avis par domaine.
     *
     * Le tirage est propre à chaque domaine : une communauté n'a pas le même
     * volume sur les jeux vidéo et sur la poésie scaldique, et un fil où tous
     * les sujets pèsent pareil se voit tout de suite.
     *
     * @param  array<int, User>  $membres
     * @param  array<string, Tag>  $tags
     * @return array<int, int>  les identifiants créés
     */
    private function creerLesAvis(array $membres, array $tags): array
    {
        $parDomaine = [];
        foreach ($membres as $membre) {
            $parDomaine[$membre->domaine][] = $membre;
        }

        $total = 0;
        $prevision = [];
        foreach (array_keys($this->catalogue['domaines']) as $cle) {
            $prevision[$cle] = mt_rand(self::AVIS_MIN, self::AVIS_MAX);
            $total += $prevision[$cle];
        }

        $this->command?->info('Création de '.$total.' avis répartis sur '.count($prevision).' domaines...');
        $barre = $this->command?->getOutput()->createProgressBar($total);

        $lignes = [];
        $liaisons = [];

        foreach ($this->catalogue['domaines'] as $cle => $domaine) {
            $auteurs = $parDomaine[$cle] ?? $membres;

            for ($i = 0; $i < $prevision[$cle]; $i++) {
                $auteur = $auteurs[array_rand($auteurs)];
                $oeuvre = $domaine['oeuvres'][array_rand($domaine['oeuvres'])];
                $tournure = $this->catalogue['avis'][array_rand($this->catalogue['avis'])];
                $lien = mt_rand(1, 100) <= 55 ? $domaine['liens'][array_rand($domaine['liens'])] : null;

                // Publié après l'inscription de son auteur, jamais avant.
                $age = mt_rand(0, max(1, $auteur->created_at->diffInDays(now())));
                $publieLe = now()->subDays($age)->subMinutes(mt_rand(0, 1439));

                $vues = (int) round(mt_rand(3, 900) * (1 + $age / 120));
                // Les images viennent du fonds de ce domaine, jamais d'un
                // fonds commun : c'est ce qui fait qu'elles parlent du sujet.
                $medias = [];
                $fonds = $this->illustrations[$cle] ?? [];
                if ($fonds && mt_rand(1, 100) <= 45) {
                    $combien = min(mt_rand(1, 3), count($fonds));
                    $choisies = (array) array_rand($fonds, $combien);
                    foreach ($choisies as $index) {
                        $medias[] = $fonds[$index];
                    }
                }

                $lignes[] = [
                    'user_id' => $auteur->id,
                    'content' => sprintf($tournure, $oeuvre),
                    'link' => $lien,
                    'link_fingerprint' => $lien ? LinkNormaliser::fingerprint($lien) : null,
                    'medias' => json_encode(array_values(array_unique($medias))),
                    'nb_views' => $vues,
                    'nb_like' => 0,
                    'nb_dislike' => 0,
                    'is_published' => true,
                    'created_at' => $publieLe,
                    'updated_at' => $publieLe,
                ];

                $liaisons[] = $domaine['tags'];
                $barre?->advance();
            }
        }

        foreach (array_chunk($lignes, 250) as $paquet) {
            Review::insert($paquet);
        }

        $barre?->finish();
        $this->command?->newLine(2);

        // Rattachement des tags, en une passe.
        $ids = Review::orderBy('id')->pluck('id')->all();
        $depart = count($ids) - count($lignes);
        $pivot = [];

        foreach ($liaisons as $index => $noms) {
            $reviewId = $ids[$depart + $index] ?? null;
            if (! $reviewId) {
                continue;
            }
            // Un à trois tags : trois systématiquement donnerait un nuage plat.
            foreach (array_slice($noms, 0, mt_rand(1, count($noms))) as $nom) {
                if (isset($tags[$nom])) {
                    $pivot[] = ['review_id' => $reviewId, 'tag_id' => $tags[$nom]->id];
                }
            }
        }

        foreach (array_chunk($pivot, 1000) as $paquet) {
            DB::table('review_tag')->insertOrIgnore($paquet);
        }

        return array_slice($ids, $depart);
    }
    // -------------------------------------------------------------------------
    // La vie autour des avis
    // -------------------------------------------------------------------------

    /**
     * Les commentaires, dont un cinquième répond à un autre commentaire.
     *
     * @param  array<int, User>  $membres
     * @param  array<int, int>  $avis
     */
    private function creerLesCommentaires(array $membres, array $avis): void
    {
        $this->command?->info('Commentaires...');
        $lignes = [];

        foreach ($avis as $reviewId) {
            // La plupart des avis n'ont rien, quelques-uns portent la
            // discussion : c'est la forme réelle d'un fil.
            $combien = match (true) {
                mt_rand(1, 100) <= 40 => 0,
                mt_rand(1, 100) <= 75 => mt_rand(1, 3),
                mt_rand(1, 100) <= 95 => mt_rand(4, 9),
                default => mt_rand(10, 26),
            };

            for ($i = 0; $i < $combien; $i++) {
                $auteur = $membres[array_rand($membres)];
                $quand = now()->subDays(mt_rand(0, 200))->subMinutes(mt_rand(0, 1439));

                $lignes[] = [
                    'user_id' => $auteur->id,
                    'review_id' => $reviewId,
                    'parent_id' => null,
                    'content' => $this->catalogue['commentaires'][array_rand($this->catalogue['commentaires'])],
                    'created_at' => $quand,
                    'updated_at' => $quand,
                ];
            }
        }

        foreach (array_chunk($lignes, 500) as $paquet) {
            Comment::insert($paquet);
        }

        // Les réponses, accrochées à des commentaires existants.
        $racines = Comment::whereNull('parent_id')->inRandomOrder()
            ->limit((int) (count($lignes) * 0.2))->get(['id', 'review_id']);
        $reponses = [];

        foreach ($racines as $racine) {
            $auteur = $membres[array_rand($membres)];
            $quand = now()->subDays(mt_rand(0, 120));
            $reponses[] = [
                'user_id' => $auteur->id,
                'review_id' => $racine->review_id,
                'parent_id' => $racine->id,
                'content' => $this->catalogue['commentaires'][array_rand($this->catalogue['commentaires'])],
                'created_at' => $quand,
                'updated_at' => $quand,
            ];
        }

        foreach (array_chunk($reponses, 500) as $paquet) {
            Comment::insert($paquet);
        }

        $this->command?->line('  '.(count($lignes) + count($reponses)).' commentaires');
    }

    /**
     * Réactions et votes d'utilité, puis report des compteurs sur les avis.
     *
     * @param  array<int, User>  $membres
     * @param  array<int, int>  $avis
     */
    private function creerLesReactions(array $membres, array $avis): void
    {
        $this->command?->info('Réactions...');
        $reactions = [];
        $utiles = [];

        foreach ($avis as $reviewId) {
            $combien = match (true) {
                mt_rand(1, 100) <= 20 => 0,
                mt_rand(1, 100) <= 70 => mt_rand(1, 12),
                mt_rand(1, 100) <= 95 => mt_rand(13, 60),
                default => mt_rand(61, 210),
            };

            $vus = [];
            for ($i = 0; $i < $combien; $i++) {
                $auteur = $membres[array_rand($membres)];
                if (isset($vus[$auteur->id])) {
                    continue;
                }
                $vus[$auteur->id] = true;
                $quand = now()->subDays(mt_rand(0, 200));

                $reactions[] = [
                    'user_id' => $auteur->id,
                    'review_id' => $reviewId,
                    // Le pouce vers le bas reste minoritaire, comme partout.
                    'reaction' => mt_rand(1, 100) <= 82 ? 'like' : 'dislike',
                    'created_at' => $quand,
                    'updated_at' => $quand,
                ];

                if (mt_rand(1, 100) <= 18) {
                    $utiles[] = [
                        'user_id' => $auteur->id,
                        'review_id' => $reviewId,
                        'helpful' => mt_rand(1, 100) <= 85,
                        'created_at' => $quand,
                        'updated_at' => $quand,
                    ];
                }
            }
        }

        foreach (array_chunk($reactions, 1000) as $paquet) {
            DB::table('review_reactions')->insertOrIgnore($paquet);
        }
        foreach (array_chunk($utiles, 1000) as $paquet) {
            DB::table('helpful_votes')->insertOrIgnore($paquet);
        }

        // Les compteurs dénormalisés doivent refléter les lignes réelles.
        DB::statement('UPDATE reviews SET nb_like = (
            SELECT COUNT(*) FROM review_reactions
            WHERE review_reactions.review_id = reviews.id AND reaction = ?
        )', ['like']);
        DB::statement('UPDATE reviews SET nb_dislike = (
            SELECT COUNT(*) FROM review_reactions
            WHERE review_reactions.review_id = reviews.id AND reaction = ?
        )', ['dislike']);

        $this->command?->line('  '.count($reactions).' réactions, '.count($utiles).' votes d\'utilité');
    }

    /**
     * Abonnements aux membres et aux sujets, plus quelques blocages.
     *
     * @param  array<int, User>  $membres
     * @param  array<string, Tag>  $tags
     */
    private function creerLesAbonnements(array $membres, array $tags): void
    {
        $this->command?->info('Abonnements...');

        $parDomaine = [];
        foreach ($membres as $membre) {
            $parDomaine[$membre->domaine][] = $membre;
        }

        $suivis = [];
        $blocages = [];

        foreach ($membres as $membre) {
            // On suit surtout dans son domaine : c'est ce qui fait qu'un fil
            // personnalisé ressemble à quelque chose.
            $voisins = $parDomaine[$membre->domaine] ?? [];
            $combien = mt_rand(0, 22);

            for ($i = 0; $i < $combien && $voisins; $i++) {
                $cible = mt_rand(1, 100) <= 70
                    ? $voisins[array_rand($voisins)]
                    : $membres[array_rand($membres)];

                if ($cible->id === $membre->id) {
                    continue;
                }

                $suivis[] = [
                    'user_id' => $membre->id,
                    'followable_type' => User::class,
                    'followable_id' => $cible->id,
                    'created_at' => now()->subDays(mt_rand(0, 300)),
                    'updated_at' => now(),
                ];
            }

            foreach ($this->catalogue['domaines'][$membre->domaine]['tags'] as $nom) {
                if (isset($tags[$nom]) && mt_rand(1, 100) <= 60) {
                    $suivis[] = [
                        'user_id' => $membre->id,
                        'followable_type' => Tag::class,
                        'followable_id' => $tags[$nom]->id,
                        'created_at' => now()->subDays(mt_rand(0, 300)),
                        'updated_at' => now(),
                    ];
                }
            }

            // Le blocage est rare, mais une communauté sans aucun blocage
            // n'existe pas.
            if (mt_rand(1, 100) <= 3) {
                $cible = $membres[array_rand($membres)];
                if ($cible->id !== $membre->id) {
                    $blocages[] = [
                        'user_id' => $membre->id,
                        'blocked_id' => $cible->id,
                        'created_at' => now()->subDays(mt_rand(0, 200)),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($suivis, 1000) as $paquet) {
            DB::table('follows')->insertOrIgnore($paquet);
        }
        foreach (array_chunk($blocages, 500) as $paquet) {
            DB::table('blocks')->insertOrIgnore($paquet);
        }

        $this->command?->line('  '.count($suivis).' abonnements, '.count($blocages).' blocages');
    }

    /**
     * @param  array<int, User>  $membres
     * @param  array<int, int>  $avis
     */
    private function creerLesEnregistrements(array $membres, array $avis): void
    {
        $this->command?->info('Enregistrements...');
        $lignes = [];

        foreach ($membres as $membre) {
            for ($i = 0, $n = mt_rand(0, 14); $i < $n; $i++) {
                $quand = now()->subDays(mt_rand(0, 250));
                $lignes[] = [
                    'user_id' => $membre->id,
                    'review_id' => $avis[array_rand($avis)],
                    'created_at' => $quand,
                    'updated_at' => $quand,
                ];
            }
        }

        foreach (array_chunk($lignes, 1000) as $paquet) {
            DB::table('bookmarks')->insertOrIgnore($paquet);
        }

        $this->command?->line('  '.count($lignes).' enregistrements');
    }

    /**
     * Signalements, et les décisions de modération qui en découlent.
     *
     * @param  array<int, User>  $membres
     * @param  array<int, int>  $avis
     */
    private function creerLaModeration(array $membres, array $avis): void
    {
        $this->command?->info('Modération...');

        // Les motifs et les statuts viennent des constantes du modèle : les
        // écrire à la main ici produirait des lignes que l'interface refuse
        // d'afficher et que la validation rejetterait.
        $motifs = Report::REASONS;
        $lignes = [];
        $vises = [];

        // Un avis sur quarante fait l'objet d'un signalement.
        foreach ($avis as $reviewId) {
            if (mt_rand(1, 40) !== 1) {
                continue;
            }

            $combien = mt_rand(1, 4);
            $vises[$reviewId] = $combien;

            for ($i = 0; $i < $combien; $i++) {
                $quand = now()->subDays(mt_rand(0, 120));
                // Deux tiers restent en attente : une file vide ne montrerait
                // pas à quoi sert la console de modération.
                $statut = match (true) {
                    mt_rand(1, 100) <= 62 => Report::STATUS_PENDING,
                    mt_rand(1, 100) <= 60 => Report::STATUS_ACTIONED,
                    default => Report::STATUS_DISMISSED,
                };

                $lignes[] = [
                    'user_id' => $membres[array_rand($membres)]->id,
                    'reportable_type' => Review::class,
                    'reportable_id' => $reviewId,
                    'reason' => $motifs[array_rand($motifs)],
                    'details' => mt_rand(1, 100) <= 45
                        ? 'Ce passage me paraît sortir du cadre de la charte.'
                        : null,
                    'status' => $statut,
                    'created_at' => $quand,
                    'updated_at' => $quand,
                ];
            }
        }

        foreach (array_chunk($lignes, 500) as $paquet) {
            DB::table('reports')->insertOrIgnore($paquet);
        }

        // Les avis très signalés sont masqués, comme le ferait le seuil
        // automatique en production.
        $aMasquer = array_keys(array_filter($vises, fn ($n) => $n >= 3));
        if ($aMasquer) {
            Review::whereIn('id', $aMasquer)->update(['is_published' => false]);
        }

        $this->command?->line('  '.count($lignes).' signalements, '.count($aMasquer).' avis masqués');
    }

    /**
     * @param  array<int, User>  $membres
     */
    private function creerLesSuggestions(array $membres): void
    {
        $sujets = Suggestion::SUBJECTS;
        $messages = [
            'Ce serait bien de pouvoir trier le fil par sujet suivi uniquement.',
            'Un mode sombre manque vraiment, surtout le soir.',
            'Les images mettent du temps à charger sur mobile.',
            'Pourquoi pas des listes de lecture partagées entre membres ?',
            'La recherche pourrait accepter les guillemets pour une expression exacte.',
            'Un raccourci clavier pour publier serait pratique.',
            'Il manque un moyen de signaler un doublon.',
            'Les notifications groupées éviteraient d\'en recevoir dix d\'affilée.',
            'Une page par auteur avec ses meilleurs avis serait utile.',
            'Le compteur de caractères devient rouge trop tôt à mon goût.',
        ];

        $lignes = [];
        for ($i = 0; $i < 140; $i++) {
            $auteur = $membres[array_rand($membres)];
            $quand = now()->subDays(mt_rand(0, 300));
            $lignes[] = [
                'user_id' => mt_rand(1, 100) <= 80 ? $auteur->id : null,
                'name' => $auteur->fullname,
                'email' => $auteur->email,
                'subject' => $sujets[array_rand($sujets)],
                'message' => $messages[array_rand($messages)],
                'status' => Suggestion::STATUSES[array_rand(Suggestion::STATUSES)],
                'created_at' => $quand,
                'updated_at' => $quand,
            ];
        }

        DB::table('suggestions')->insert($lignes);
        $this->command?->line('  '.count($lignes).' suggestions');
    }

    // -------------------------------------------------------------------------
    // Finition
    // -------------------------------------------------------------------------

    private function recalculerLesScores(): void
    {
        $this->command?->info('Calcul des scores du fil...');
        Review::chunkById(500, function ($lot) {
            foreach ($lot as $review) {
                FeedScore::refresh($review);
            }
        });
    }

    private function resumer(): void
    {
        $this->command?->newLine();
        $this->command?->info('Terminé.');
        $this->command?->line('  membres        : '.User::count());
        $this->command?->line('  avis           : '.Review::count().' dont '.Review::where('is_published', false)->count().' masqués');
        $this->command?->line('  commentaires   : '.Comment::count());
        $this->command?->line('  réactions      : '.ReviewReaction::count());
        $this->command?->line('  abonnements    : '.Follow::count());
        $this->command?->line('  enregistrements: '.Bookmark::count());
        $this->command?->line('  signalements   : '.Report::count());
        $this->command?->line('  suggestions    : '.Suggestion::count());
        $this->command?->newLine();
        $this->command?->warn('Mot de passe commun à tous les comptes : '.self::MOT_DE_PASSE);
    }
}
