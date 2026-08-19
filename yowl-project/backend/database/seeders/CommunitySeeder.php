<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewReaction;
use App\Models\Suggestion;
use App\Models\Tag;
use App\Models\User;
use App\Support\SeedImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Populate the platform with a community that reads like a real one.
 *
 * The previous seed produced fifty accounts named user1 to user50 with the
 * same birthdate pattern and no content at all, which made every screen look
 * broken: an empty feed, counters at zero, a moderation queue with nothing in
 * it. Demonstrating or testing the product needed content that behaves like
 * content, so this seeder writes reviews people would plausibly post, threaded
 * discussions, an uneven distribution of reactions, and a moderation queue
 * with something waiting in it.
 */
class CommunitySeeder extends Seeder
{
    private const MEMBERS = 40;

    /** Reviews are spread over this many days, most recent weighted. */
    private const HISTORY_DAYS = 120;

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('CommunitySeeder refuses to run in production.');
        }

        $password = env('SEED_MEMBER_PASSWORD') ?: Str::password(20);

        $members = $this->createMembers($password);
        $tags = $this->tags();
        $reviews = $this->createReviews($members, $tags);
        $this->createDiscussions($members, $reviews);
        $this->createReactions($members, $reviews);
        $this->createModerationQueue($members, $reviews);
        $this->createSuggestions($members);
        $this->refreshCounters();

        $this->command?->newLine();
        $this->command?->info('Communauté générée :');
        $this->command?->line('  '.count($members).' membres, mot de passe commun : '.$password);
        $this->command?->line('  '.count($reviews).' reviews, '.Comment::count().' commentaires');
        $this->command?->line('  '.Report::count().' signalements, '.Suggestion::count().' suggestions');
        $this->command?->newLine();
    }

    /**
     * Members with names, handles and biographies that read as human.
     *
     * @return array<int, User>
     */
    private function createMembers(string $password): array
    {
        $firstNames = [
            'Awa', 'Lucas', 'Nadia', 'Yanis', 'Chloé', 'Ibrahim', 'Léa', 'Mathis', 'Fatou', 'Enzo',
            'Camille', 'Rayan', 'Inès', 'Théo', 'Sarah', 'Noah', 'Manon', 'Adam', 'Jade', 'Gabriel',
            'Louise', 'Amir', 'Emma', 'Sofiane', 'Alice', 'Malik', 'Zoé', 'Hugo', 'Aya', 'Nathan',
            'Lina', 'Ethan', 'Maya', 'Samuel', 'Nour', 'Antoine', 'Salma', 'Victor', 'Élisa', 'Karim',
        ];
        $lastNames = [
            'Diallo', 'Martin', 'Benali', 'Dubois', 'Traoré', 'Moreau', 'Nguyen', 'Lefèvre', 'Sow',
            'Garcia', 'Rossi', 'Bernard', 'Kaboré', 'Petit', 'Silva', 'Roux', 'Camara', 'Fontaine',
            'Mendes', 'Girard',
        ];

        $members = [];
        $hashed = Hash::make($password);

        for ($index = 0; $index < self::MEMBERS; $index++) {
            $first = $firstNames[$index % count($firstNames)];
            $last = $lastNames[($index * 7) % count($lastNames)];
            $handle = Str::slug($first.'.'.$last, '_').($index > 0 && $index % 13 === 0 ? $index : '');

            $members[] = User::create([
                'username' => $handle,
                'fullname' => $first.' '.$last,
                'email' => $handle.'@yowl.local',
                'password' => $hashed,
                // Une communaute a des ages varies, pas une seule generation.
                'birthdate' => now()->subYears(rand(15, 34))->subDays(rand(0, 364))->format('Y-m-d'),
                'picture' => rand(0, 100) < 80 ? self::avatarUrl($handle) : null,
                'created_at' => now()->subDays(rand(30, 400)),
            ]);
            $members[$index]->forceFill(['email_verified_at' => now()])->save();
            $members[$index]->assignRole('client');
        }

        return $members;
    }

    /**
     * A photograph for a given seed, downloaded once and stored locally.
     */
    private static function illustrationUrl(string $seed, string $label = ''): ?string
    {
        return SeedImage::illustration($seed);
    }

    /**
     * Give the seeded members an avatar, so the feed does not show forty
     * identical initials.
     */
    private static function avatarUrl(string $seed): ?string
    {
        return SeedImage::avatar($seed);
    }

    /**
     * @return array<string, Tag>
     */
    private function tags(): array
    {
        $names = [
            'cinema', 'serie', 'musique', 'jeuxvideo', 'tech', 'ia', 'cuisine', 'voyage',
            'sport', 'football', 'livre', 'podcast', 'streaming', 'photo', 'mode', 'sante',
            'ecologie', 'science', 'humour', 'actualite',
        ];

        $tags = [];
        foreach ($names as $name) {
            $tags[$name] = Tag::firstOrCreate(['name' => $name]);
        }

        return $tags;
    }

    /**
     * Reviews with the shape of real posts: an opinion, a link, sometimes tags.
     *
     * @param  array<int, User>  $members
     * @param  array<string, Tag>  $tags
     * @return array<int, Review>
     */
    private function createReviews(array $members, array $tags): array
    {
        // Illustrations de demonstration, dessinees et ecrites sur le disque
        // des medias. Elles ne sont pas versionnees et ne dependent d'aucun
        // service tiers, donc le fil reste illustre hors ligne comme en ligne.
        $illustrations = [
            'cinema' => ['film-1', 'film-2', 'cinema-hall'],
            'serie' => ['series-1', 'series-2'],
            'musique' => ['concert-1', 'vinyl-1', 'studio-1'],
            'jeuxvideo' => ['gaming-1', 'gaming-2'],
            'tech' => ['desk-1', 'laptop-1', 'circuit-1'],
            'ia' => ['neural-1', 'server-1'],
            'cuisine' => ['food-1', 'food-2', 'kitchen-1'],
            'voyage' => ['travel-1', 'travel-2', 'city-1'],
            'sport' => ['stadium-1', 'running-1'],
            'football' => ['football-1'],
            'livre' => ['books-1', 'books-2'],
            'podcast' => ['mic-1'],
            'streaming' => ['screen-1'],
            'photo' => ['camera-1', 'camera-2'],
            'mode' => ['fashion-1', 'fashion-2'],
            'sante' => ['health-1'],
            'ecologie' => ['forest-1', 'ocean-1'],
            'science' => ['lab-1'],
            'humour' => ['comedy-1'],
            'actualite' => ['news-1'],
        ];

        $posts = [
            ['Le montage de ce documentaire est remarquable, mais la dernière demi-heure traîne franchement. À voir pour la première partie.', 'https://www.arte.tv', ['cinema', 'actualite']],
            ['Trois épisodes et je suis accroché. L\'écriture des dialogues est au-dessus du lot cette année.', 'https://www.imdb.com', ['serie']],
            ['Album écouté en boucle depuis une semaine. La production est dense sans être fatigante, ce qui est rare.', 'https://open.spotify.com', ['musique']],
            ['Le patch a corrigé les chutes de framerate, mais l\'équilibrage du mode multijoueur reste discutable.', 'https://store.steampowered.com', ['jeuxvideo']],
            ['Article clair sur le sujet, et surtout il cite ses sources. Ça change.', 'https://www.lemonde.fr', ['actualite', 'science']],
            ['Testé la recette hier soir : il faut vraiment respecter le temps de repos, sinon la pâte ne lève pas.', 'https://www.marmiton.org', ['cuisine']],
            ['Deux jours sur place suffisent largement. Le centre se fait à pied et les transports sont fiables.', 'https://www.tripadvisor.fr', ['voyage']],
            ['Le modèle se débrouille bien sur les tâches courtes et décroche complètement sur les longues. Utile de le savoir avant de s\'en servir.', 'https://arxiv.org', ['ia', 'tech']],
            ['Match sérieux, milieu de terrain enfin équilibré. Reste la finition, toujours le même problème.', 'https://www.lequipe.fr', ['sport', 'football']],
            ['Livre dense mais très lisible. Le chapitre sur les biais de mesure vaut à lui seul le détour.', 'https://www.babelio.com', ['livre', 'science']],
            ['Episode intéressant, sauf que l\'invité coupe la parole en permanence. Dommage pour le fond.', 'https://podcasts.apple.com', ['podcast']],
            ['La qualité vidéo a chuté depuis la mise à jour, ou alors c\'est ma connexion. Quelqu\'un a remarqué ?', 'https://www.youtube.com', ['streaming', 'tech']],
            ['Boîtier léger, autonomie correcte, mais l\'ergonomie du menu est un cauchemar.', 'https://www.dpreview.com', ['photo', 'tech']],
            ['Bonne surprise sur la coupe, moins sur la matière qui se froisse au premier lavage.', 'https://www.vinted.fr', ['mode']],
            ['Application utile pour suivre son sommeil, à condition de ne pas prendre les chiffres au pied de la lettre.', 'https://www.who.int', ['sante', 'tech']],
            ['Le rapport est accablant et pourtant très mesuré dans son ton. À lire en entier.', 'https://www.ipcc.ch', ['ecologie', 'science']],
            ['Le sketch d\'ouverture est excellent, la suite retombe. Ça reste une bonne soirée.', 'https://www.netflix.com', ['humour']],
            ['Interface refaite, et pour une fois c\'est plus rapide qu\'avant. Bravo à l\'équipe.', 'https://github.com', ['tech']],
            ['Concert impeccable côté son, beaucoup moins côté organisation. Une heure d\'attente à l\'entrée.', 'https://www.songkick.com', ['musique']],
            ['Franchement déçu. Le scénario part dans tous les sens et aucun personnage n\'est développé.', 'https://www.allocine.fr', ['cinema']],
        ];

        $reviews = [];
        $count = 0;

        foreach ($members as $index => $member) {
            // Une communaute n'est pas uniforme : quelques membres publient
            // beaucoup, la plupart peu, certains jamais.
            $howMany = match (true) {
                $index < 4 => rand(4, 7),
                $index < 16 => rand(1, 3),
                $index < 30 => rand(0, 1),
                default => 0,
            };

            for ($n = 0; $n < $howMany; $n++) {
                [$content, $link, $tagNames] = $posts[$count % count($posts)];
                $count++;

                // Les publications recentes sont plus nombreuses.
                $daysAgo = (int) round(self::HISTORY_DAYS * (rand(0, 100) / 100) ** 2);
                $createdAt = now()->subDays($daysAgo)->subMinutes(rand(0, 1439));

                // Deux publications sur trois portent une illustration, comme
                // sur un fil reel ou le texte seul reste minoritaire.
                $medias = [];
                if (rand(0, 100) < 66) {
                    $pool = [];
                    foreach ($tagNames as $name) {
                        foreach ($illustrations[$name] ?? [] as $seed) {
                            $pool[] = $seed;
                        }
                    }
                    if ($pool) {
                        shuffle($pool);
                        foreach (array_slice($pool, 0, rand(1, min(3, count($pool)))) as $seed) {
                            // Une image indisponible ne bloque pas la publication.
                            if ($stored = self::illustrationUrl($seed)) {
                                $medias[] = $stored;
                            }
                        }
                    }
                }

                $review = Review::create([
                    'user_id' => $member->id,
                    'content' => $content,
                    'link' => rand(0, 10) > 2 ? $link : null,
                    'medias' => $medias,
                    'nb_views' => rand(3, 480),
                    'is_published' => rand(0, 20) > 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $ids = [];
                foreach ($tagNames as $name) {
                    if (isset($tags[$name])) {
                        $ids[] = $tags[$name]->id;
                    }
                }
                if ($ids) {
                    $review->tags()->sync($ids);
                }

                $reviews[] = $review;
            }
        }

        return $reviews;
    }

    /**
     * Threaded discussions, with replies that answer the comment above.
     *
     * @param  array<int, User>  $members
     * @param  array<int, Review>  $reviews
     */
    private function createDiscussions(array $members, array $reviews): void
    {
        $openers = [
            'Complètement d\'accord, surtout sur le dernier point.',
            'Je n\'ai pas eu la même impression, mais je comprends l\'argument.',
            'Merci pour le retour, ça m\'évite d\'y passer une soirée.',
            'Tu as testé la version précédente ? La comparaison m\'intéresse.',
            'Exactement ce que je cherchais, merci.',
            'Bof. Ça a été survendu partout et je ne vois pas pourquoi.',
            'Le début est lent mais ça vaut le coup de s\'accrocher.',
            'Des sources sur cette partie ? Je voudrais creuser.',
        ];
        $replies = [
            'Oui, je l\'ai fait la semaine dernière, c\'était déjà mieux.',
            'Ah, je n\'avais pas vu ça sous cet angle.',
            'Je maintiens, mais je vois ce que tu veux dire.',
            'Je te mets le lien en message.',
            'Pareil de mon côté.',
        ];

        foreach ($reviews as $review) {
            $threadSize = match (true) {
                rand(0, 100) < 30 => 0,
                rand(0, 100) < 70 => rand(1, 3),
                default => rand(4, 8),
            };

            $roots = [];
            for ($n = 0; $n < $threadSize; $n++) {
                $author = $members[array_rand($members)];
                $createdAt = $review->created_at->copy()->addHours(rand(1, 72))->addMinutes(rand(0, 59));
                if ($createdAt->isFuture()) {
                    $createdAt = now()->subMinutes(rand(5, 600));
                }

                $isReply = $roots && rand(0, 100) < 40;
                $comment = Comment::create([
                    'user_id' => $author->id,
                    'review_id' => $review->id,
                    'parent_id' => $isReply ? $roots[array_rand($roots)]->id : null,
                    'content' => $isReply
                        ? $replies[array_rand($replies)]
                        : $openers[array_rand($openers)],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                if (! $isReply) {
                    $roots[] = $comment;
                }
            }
        }
    }

    /**
     * Reactions, weighted so that popular content stands out.
     *
     * @param  array<int, User>  $members
     * @param  array<int, Review>  $reviews
     */
    private function createReactions(array $members, array $reviews): void
    {
        foreach ($reviews as $review) {
            $reactors = collect($members)
                ->reject(fn (User $member) => $member->id === $review->user_id)
                ->shuffle()
                ->take(rand(0, 18));

            foreach ($reactors as $member) {
                ReviewReaction::create([
                    'review_id' => $review->id,
                    'user_id' => $member->id,
                    // L'approbation domine, comme sur un vrai reseau.
                    'reaction' => rand(0, 100) < 82 ? 'like' : 'dislike',
                ]);
            }
        }

        foreach (Comment::inRandomOrder()->limit(60)->get() as $comment) {
            $reactors = collect($members)
                ->reject(fn (User $member) => $member->id === $comment->user_id)
                ->shuffle()
                ->take(rand(0, 6));

            foreach ($reactors as $member) {
                CommentReaction::create([
                    'comment_id' => $comment->id,
                    'user_id' => $member->id,
                    'reaction' => rand(0, 100) < 85 ? 'like' : 'dislike',
                ]);
            }
        }
    }

    /**
     * A moderation queue with something actually waiting in it.
     *
     * @param  array<int, User>  $members
     * @param  array<int, Review>  $reviews
     */
    private function createModerationQueue(array $members, array $reviews): void
    {
        $reasons = ['spam', 'harassment', 'hate', 'misinformation', 'other'];
        $details = [
            'Ce compte publie le même lien sur toutes les reviews.',
            'Le ton est agressif envers les autres membres.',
            'Information démentie depuis, la source est un faux site.',
            null,
        ];

        $targets = collect($reviews)->shuffle()->take(6);
        foreach ($targets as $review) {
            $reporter = collect($members)->first(fn (User $m) => $m->id !== $review->user_id);
            if (! $reporter) {
                continue;
            }

            $report = new Report([
                'user_id' => $reporter->id,
                'reason' => $reasons[array_rand($reasons)],
                'details' => $details[array_rand($details)],
            ]);
            $report->reportable()->associate($review);
            $report->save();
        }

        foreach (Comment::inRandomOrder()->limit(3)->get() as $comment) {
            $reporter = collect($members)->first(fn (User $m) => $m->id !== $comment->user_id);
            if (! $reporter) {
                continue;
            }

            $report = new Report([
                'user_id' => $reporter->id,
                'reason' => $reasons[array_rand($reasons)],
                'details' => $details[array_rand($details)],
            ]);
            $report->reportable()->associate($comment);
            $report->save();
        }
    }

    /**
     * @param  array<int, User>  $members
     */
    private function createSuggestions(array $members): void
    {
        $messages = [
            'Ce serait bien de pouvoir filtrer le fil par tag directement depuis une review.',
            'Un mode sombre, s\'il vous plaît.',
            'Pouvoir enregistrer une review pour la relire plus tard.',
            'Les notifications par email en plus des notifications push.',
            'Un moyen de signaler une erreur factuelle sans que ce soit un signalement de modération.',
        ];

        foreach ($messages as $index => $message) {
            $author = $index % 2 === 0 ? $members[array_rand($members)] : null;

            Suggestion::create([
                'user_id' => $author?->id,
                'name' => $author?->fullname,
                'email' => $author?->email,
                'message' => $message,
                'status' => $index < 3 ? 'new' : 'read',
                'created_at' => now()->subDays(rand(1, 45)),
            ]);
        }
    }

    /**
     * Bring the denormalised counters in line with the reactions written.
     *
     * They are recomputed once at the end rather than maintained row by row:
     * a seed writing thousands of reactions would otherwise spend its time
     * updating the same counters over and over.
     */
    private function refreshCounters(): void
    {
        DB::statement('
            UPDATE reviews SET
                nb_like = (SELECT COUNT(*) FROM review_reactions WHERE review_reactions.review_id = reviews.id AND reaction = ?),
                nb_dislike = (SELECT COUNT(*) FROM review_reactions WHERE review_reactions.review_id = reviews.id AND reaction = ?)
        ', ['like', 'dislike']);

        DB::statement('
            UPDATE comments SET
                nb_like = (SELECT COUNT(*) FROM comment_reactions WHERE comment_reactions.comment_id = comments.id AND reaction = ?),
                nb_dislike = (SELECT COUNT(*) FROM comment_reactions WHERE comment_reactions.comment_id = comments.id AND reaction = ?)
        ', ['like', 'dislike']);
    }
}
