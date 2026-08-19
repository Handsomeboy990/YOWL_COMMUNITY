<template>
  <div class="landing w-full overflow-x-hidden bg-white">
    <!-- ====== NAVBAR ====== -->
    <nav
      class="fixed top-0 left-0 w-full z-50 transition-all duration-500"
      :class="scrolled ? 'bg-white/90 backdrop-blur-lg shadow-lg shadow-blue-night/5 py-3' : 'bg-transparent py-5'"
    >
      <div class="w-full px-6 md:px-12 flex items-center justify-between">
        <router-link to="/" class="flex items-center gap-2.5">
          <img src="@/assets/logo.png" alt="Logo YOWL" class="w-10 h-10" />
          <span
            class="font-poppins font-extrabold text-2xl tracking-tight"
            :class="scrolled ? 'text-blue-night' : 'text-white'"
            >YOWL</span
          >
        </router-link>

        <div class="hidden md:flex items-center gap-8 font-medium" :class="scrolled ? 'text-blue-night' : 'text-white/90'">
          <a href="#fonctionnalites" class="hover:text-orange-primary transition-colors">Fonctionnalités</a>
          <a href="#comment-ca-marche" class="hover:text-orange-primary transition-colors">Comment ça marche</a>
          <a href="#partage" class="hover:text-orange-primary transition-colors">Partage rapide</a>
          <a href="#communaute" class="hover:text-orange-primary transition-colors">Communauté</a>
        </div>

        <div class="flex items-center gap-3">
          <BaseButton
            :tag="'router-link'"
            :to="'/login'"
            :variant="scrolled ? 'ghost' : 'outline'"
            size="sm"
            :shine="false"
          >
            Connexion
          </BaseButton>
          <BaseButton :tag="'router-link'" :to="'/signup'" variant="primary" size="sm">
            Rejoindre
          </BaseButton>
        </div>
      </div>
    </nav>

    <!-- ====== HERO ====== -->
    <header class="relative min-h-screen flex items-center bg-blue-night overflow-hidden">
      <!-- Fond animé -->
      <div class="absolute inset-0" aria-hidden="true">
        <div class="hero-gradient absolute inset-0"></div>
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="absolute inset-0 hero-grid opacity-[0.07]"></div>
      </div>

      <div class="relative w-full px-6 md:px-12 pt-32 pb-40 grid lg:grid-cols-2 gap-16 items-center">
        <!-- Texte -->
        <div class="max-w-2xl">
          <span
            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 border border-white/20 text-white/90 text-sm font-medium backdrop-blur-sm animate-fade-in-up"
          >
            <span class="w-2 h-2 rounded-full bg-orange-primary animate-ping-slow"></span>
            La communauté des 13-35 ans
          </span>

          <h1
            class="mt-6 font-poppins font-extrabold text-white leading-[1.08] animate-fade-in-up animation-delay-200"
            style="font-size: clamp(2.5rem, 6vw, 4.5rem)"
          >
            Ton avis sur le web,
            <span class="relative inline-block">
              <span class="gradient-text-hero">sans filtre</span>
              <svg class="absolute -bottom-2 left-0 w-full" height="10" viewBox="0 0 200 10" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 8 Q 50 0 100 6 T 200 4" stroke="#FF6B35" stroke-width="4" fill="none" stroke-linecap="round" class="draw-line" />
              </svg>
            </span>
          </h1>

          <p class="mt-8 text-lg md:text-xl text-white/70 leading-relaxed animate-fade-in-up animation-delay-400">
            Partage, commente et réagis sur n'importe quel contenu trouvé sur internet.
            Rejoins une communauté jeune, active et bienveillante où chaque voix compte.
          </p>

          <div class="mt-10 flex flex-wrap items-center gap-4 animate-fade-in-up animation-delay-400">
            <BaseButton :tag="'router-link'" :to="'/signup'" variant="primary" size="xl" icon="fa-solid fa-rocket">
              Créer mon compte
            </BaseButton>
            <BaseButton :tag="'router-link'" :to="'/feed'" variant="outline" size="xl" icon="fa-regular fa-eye" :shine="false">
              Explorer le fil
            </BaseButton>
          </div>

          <div class="mt-12 flex items-center gap-4 text-white/60 text-sm animate-fade-in-up animation-delay-400">
            <div class="flex -space-x-3" aria-hidden="true">
              <span v-for="(color, i) in avatarColors" :key="i"
                class="w-10 h-10 rounded-full border-2 border-blue-night grid place-items-center font-poppins font-bold text-white text-xs"
                :style="{ backgroundColor: color }"
              >{{ ['YO', 'WL', 'CM', 'TY'][i] }}</span>
            </div>
            <p>
              Déjà <strong class="text-white">{{ displayed.nbUsers }}</strong> membres partagent leurs avis
            </p>
          </div>
        </div>

        <!-- Mockup animé du fil -->
        <div class="relative hidden lg:block" aria-hidden="true">
          <div class="mockup-stack relative h-[540px]">
            <article
              v-for="(card, i) in mockCards"
              :key="i"
              class="mock-card absolute w-[420px] max-w-full bg-white rounded-2xl shadow-2xl shadow-black/30 p-6"
              :style="card.style"
            >
              <div class="flex items-center gap-3 mb-4">
                <span
                  class="w-11 h-11 rounded-full grid place-items-center font-poppins font-bold text-white"
                  :style="{ backgroundColor: card.color }"
                  >{{ card.initials }}</span
                >
                <div>
                  <p class="font-semibold text-blue-night">{{ card.author }}</p>
                  <p class="text-xs text-gray-400">{{ card.time }}</p>
                </div>
                <span class="ml-auto text-xs font-semibold px-3 py-1 rounded-full" :style="{ backgroundColor: card.color + '18', color: card.color }">
                  #{{ card.tag }}
                </span>
              </div>
              <p class="text-blue-night/80 text-[15px] leading-relaxed mb-4">{{ card.content }}</p>
              <div class="flex items-center gap-5 text-sm text-gray-400 border-t border-gray-100 pt-3.5">
                <span class="flex items-center gap-1.5 text-orange-primary font-semibold">
                  <i class="fa-solid fa-thumbs-up"></i> {{ card.likes }}
                </span>
                <span class="flex items-center gap-1.5"><i class="fa-regular fa-comment"></i> {{ card.comments }}</span>
                <span class="flex items-center gap-1.5 ml-auto"><i class="fa-regular fa-eye"></i> {{ card.views }}</span>
              </div>
            </article>

            <!-- Bulles de réaction flottantes -->
            <span class="float-emoji-badge badge-like"><i class="fa-solid fa-thumbs-up"></i></span>
            <span class="float-emoji-badge badge-comment"><i class="fa-solid fa-comment"></i></span>
            <span class="float-emoji-badge badge-star"><i class="fa-solid fa-bolt"></i></span>
          </div>
        </div>
      </div>

      <!-- Vague de transition -->
      <svg class="absolute bottom-0 left-0 w-full" viewBox="0 0 1440 110" fill="none" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 60 C 240 110 480 10 720 45 C 960 80 1200 100 1440 40 L 1440 110 L 0 110 Z" fill="white" />
      </svg>
    </header>

    <!-- ====== BANDEAU TAGS (marquee) ====== -->
    <section class="w-full py-10 bg-white overflow-hidden" aria-label="Thèmes populaires">
      <div class="marquee flex gap-4 w-max">
        <div v-for="n in 2" :key="n" class="flex gap-4 pr-4">
          <span
            v-for="tag in marqueeTags"
            :key="n + tag"
            class="whitespace-nowrap px-5 py-2.5 rounded-full border border-gray-200 text-blue-night/70 font-medium text-sm hover:border-orange-primary hover:text-orange-primary transition-colors cursor-default"
          >
            #{{ tag }}
          </span>
        </div>
      </div>
    </section>

    <!-- ====== STATS LIVE ====== -->
    <section id="communaute" ref="statsSection" class="w-full px-6 md:px-12 py-20">
      <div class="relative rounded-3xl bg-blue-night overflow-hidden px-8 md:px-16 py-14">
        <div class="blob blob-stats" aria-hidden="true"></div>
        <div class="relative grid grid-cols-2 lg:grid-cols-4 gap-10 text-center">
          <div v-for="stat in stats" :key="stat.label" class="reveal">
            <p class="font-poppins font-extrabold text-4xl md:text-5xl text-white">
              {{ stat.value }}<span class="text-orange-primary">{{ stat.suffix }}</span>
            </p>
            <p class="mt-2 text-white/60 text-sm md:text-base">{{ stat.label }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ====== FONCTIONNALITES ====== -->
    <section id="fonctionnalites" class="w-full px-6 md:px-12 py-24 bg-gradient-to-b from-white to-orange-50/40">
      <div class="text-center max-w-3xl mx-auto reveal">
        <span class="text-orange-primary font-poppins font-semibold uppercase tracking-widest text-sm">Fonctionnalités</span>
        <h2 class="mt-3 font-poppins font-extrabold text-blue-night" style="font-size: clamp(1.8rem, 4vw, 3rem)">
          Tout ce qu'il faut pour t'exprimer
        </h2>
        <p class="mt-4 text-gray-500 text-lg">
          Une plateforme pensée pour partager tes découvertes du web et échanger avec une communauté qui te ressemble.
        </p>
      </div>

      <div class="mt-16 grid sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-7xl mx-auto">
        <article
          v-for="feature in features"
          :key="feature.title"
          class="reveal group bg-white rounded-2xl border border-gray-100 p-8 shadow-sm hover:shadow-2xl hover:shadow-orange-primary/10 hover:-translate-y-2 transition-all duration-500"
        >
          <div
            class="w-14 h-14 rounded-2xl grid place-items-center text-xl text-white mb-6 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500"
            :class="feature.bg"
          >
            <i :class="feature.icon"></i>
          </div>
          <h3 class="font-poppins font-bold text-xl text-blue-night mb-3">{{ feature.title }}</h3>
          <p class="text-gray-500 leading-relaxed">{{ feature.text }}</p>
        </article>
      </div>
    </section>

    <!-- ====== COMMENT CA MARCHE ====== -->
    <section id="comment-ca-marche" class="w-full px-6 md:px-12 py-24">
      <div class="text-center max-w-3xl mx-auto reveal">
        <span class="text-orange-primary font-poppins font-semibold uppercase tracking-widest text-sm">Simple et rapide</span>
        <h2 class="mt-3 font-poppins font-extrabold text-blue-night" style="font-size: clamp(1.8rem, 4vw, 3rem)">
          Comment ça marche ?
        </h2>
      </div>

      <div class="mt-16 grid md:grid-cols-3 gap-10 max-w-6xl mx-auto">
        <div v-for="(step, i) in steps" :key="step.title" class="reveal relative text-center px-4">
          <div class="relative inline-block">
            <span
              class="w-20 h-20 rounded-3xl bg-gradient-to-br from-orange-primary to-[#ff8c5a] text-white inline-grid place-items-center text-2xl shadow-xl shadow-orange-primary/30 rotate-3 hover:rotate-0 transition-transform duration-500"
            >
              <i :class="step.icon"></i>
            </span>
            <span
              class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-blue-night text-white text-sm font-poppins font-bold grid place-items-center"
              >{{ i + 1 }}</span
            >
          </div>
          <h3 class="mt-6 font-poppins font-bold text-xl text-blue-night">{{ step.title }}</h3>
          <p class="mt-3 text-gray-500 leading-relaxed">{{ step.text }}</p>
          <i
            v-if="i < steps.length - 1"
            class="hidden md:block fa-solid fa-arrow-right-long absolute top-8 -right-7 text-orange-primary/40 text-2xl"
            aria-hidden="true"
          ></i>
        </div>
      </div>
    </section>

    <!-- ====== PARTAGE RAPIDE ====== -->
    <section id="partage" class="w-full px-6 md:px-12 py-24 bg-gradient-to-b from-orange-50/40 to-white">
      <div class="text-center max-w-3xl mx-auto reveal">
        <span class="text-orange-primary font-poppins font-semibold uppercase tracking-widest text-sm">Partage en 10 secondes</span>
        <h2 class="mt-3 font-poppins font-extrabold text-blue-night" style="font-size: clamp(1.8rem, 4vw, 3rem)">
          Partage depuis n'importe où
        </h2>
        <p class="mt-4 text-gray-500 text-lg">
          Tu navigues, tu trouves une pépite, tu la partages. Trois façons de le faire,
          sans jamais interrompre ta navigation. Choisis la tienne.
        </p>
      </div>

      <div class="mt-16 grid md:grid-cols-3 gap-6 max-w-7xl mx-auto">
        <!-- Application (PWA) -->
        <article class="reveal bg-white rounded-2xl border border-gray-100 p-8 shadow-sm hover:shadow-2xl hover:shadow-orange-primary/10 hover:-translate-y-2 transition-all duration-500">
          <div class="flex items-center justify-between mb-6">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-primary to-[#ff8c5a] grid place-items-center text-white text-xl">
              <i class="fa-solid fa-mobile-screen-button"></i>
            </div>
            <span class="text-xs font-bold uppercase tracking-wide px-3 py-1 rounded-full bg-emerald-50 text-emerald-600">Recommandé</span>
          </div>
          <h3 class="font-poppins font-bold text-xl text-blue-night mb-3">L'application YOWL</h3>
          <p class="text-gray-500 text-sm leading-relaxed mb-5">
            Installe YOWL sur ton téléphone : l'app apparaît directement dans le
            menu de partage natif. Aucun store, aucune extension.
          </p>
          <ol class="space-y-2.5 text-sm text-gray-600">
            <li class="flex gap-2.5"><span class="step-num">1</span> Ouvre YOWL dans ton navigateur</li>
            <li class="flex gap-2.5"><span class="step-num">2</span> Menu du navigateur, puis « Ajouter à l'écran d'accueil »</li>
            <li class="flex gap-2.5"><span class="step-num">3</span> Sur n'importe quel site : Partager, puis YOWL</li>
          </ol>
        </article>

        <!-- Bookmarklet -->
        <article class="reveal bg-white rounded-2xl border border-gray-100 p-8 shadow-sm hover:shadow-2xl hover:shadow-orange-primary/10 hover:-translate-y-2 transition-all duration-500">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#7C5CFC] to-[#9d85ff] grid place-items-center text-white text-xl mb-6">
            <i class="fa-solid fa-bookmark"></i>
          </div>
          <h3 class="font-poppins font-bold text-xl text-blue-night mb-3">Le bouton favori</h3>
          <p class="text-gray-500 text-sm leading-relaxed mb-5">
            Sur ordinateur, glisse ce bouton dans ta barre de favoris.
            Un clic depuis n'importe quelle page ouvre le composeur YOWL pré-rempli.
          </p>
          <a :href="bookmarkletHref" draggable="true"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#7C5CFC] text-white font-semibold text-sm shadow-lg shadow-[#7C5CFC]/30 cursor-grab active:cursor-grabbing select-none"
            title="Glisse-moi dans ta barre de favoris"
            @click.prevent>
            <i class="fa-solid fa-bolt"></i>
            Partager sur YOWL
          </a>
          <p class="mt-4 text-xs text-gray-400">
            Astuce : affiche la barre de favoris avec Ctrl+Maj+B puis glisse le bouton dessus.
          </p>
        </article>

        <!-- Extension -->
        <article class="reveal bg-white rounded-2xl border border-gray-100 p-8 shadow-sm hover:shadow-2xl hover:shadow-orange-primary/10 hover:-translate-y-2 transition-all duration-500">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-night to-blue-night-light grid place-items-center text-white text-xl mb-6">
            <i class="fa-solid fa-puzzle-piece"></i>
          </div>
          <h3 class="font-poppins font-bold text-xl text-blue-night mb-3">L'extension Chrome</h3>
          <p class="text-gray-500 text-sm leading-relaxed mb-5">
            Pour les habitués du clic droit : une icône dans la barre d'outils et un
            menu contextuel « Partager sur YOWL » sur toutes les pages.
          </p>
          <ol class="space-y-2.5 text-sm text-gray-600">
            <li class="flex gap-2.5"><span class="step-num">1</span> Ouvre chrome://extensions et active le « Mode développeur »</li>
            <li class="flex gap-2.5"><span class="step-num">2</span> « Charger l'extension non empaquetée », puis choisis le dossier yowl-project/extension</li>
            <li class="flex gap-2.5"><span class="step-num">3</span> Clique sur l'icône YOWL depuis n'importe quelle page</li>
          </ol>
        </article>
      </div>
    </section>

    <!-- ====== TEMOIGNAGES ====== -->
    <section class="w-full px-6 md:px-12 py-24 bg-blue-night relative overflow-hidden">
      <div class="blob blob-testimonials" aria-hidden="true"></div>
      <div class="relative text-center max-w-3xl mx-auto reveal">
        <span class="text-orange-primary font-poppins font-semibold uppercase tracking-widest text-sm">Ils en parlent</span>
        <h2 class="mt-3 font-poppins font-extrabold text-white" style="font-size: clamp(1.8rem, 4vw, 3rem)">
          La communauté a la parole
        </h2>
      </div>

      <div class="relative mt-16 grid md:grid-cols-3 gap-6 max-w-6xl mx-auto">
        <blockquote
          v-for="quote in testimonials"
          :key="quote.author"
          class="reveal bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl p-8 hover:bg-white/10 transition-colors duration-500"
        >
          <div class="flex gap-1 text-orange-primary mb-5" aria-hidden="true">
            <i v-for="s in 5" :key="s" class="fa-solid fa-star text-sm"></i>
          </div>
          <p class="text-white/80 leading-relaxed italic">« {{ quote.text }} »</p>
          <footer class="mt-6 flex items-center gap-3">
            <span
              class="w-11 h-11 rounded-full grid place-items-center font-poppins font-bold text-white"
              :style="{ backgroundColor: quote.color }"
              >{{ quote.initials }}</span
            >
            <div>
              <p class="text-white font-semibold text-sm">{{ quote.author }}</p>
              <p class="text-white/50 text-xs">{{ quote.role }}</p>
            </div>
          </footer>
        </blockquote>
      </div>
    </section>

    <!-- ====== CTA FINAL ====== -->
    <section class="w-full px-6 md:px-12 py-24">
      <div
        class="reveal relative rounded-3xl bg-gradient-to-br from-orange-primary to-[#ff8c5a] px-8 md:px-16 py-16 text-center overflow-hidden"
      >
        <div class="cta-rings" aria-hidden="true"></div>
        <h2 class="relative font-poppins font-extrabold text-white" style="font-size: clamp(1.8rem, 4vw, 3rem)">
          Prêt à faire entendre ta voix ?
        </h2>
        <p class="relative mt-4 text-white/85 text-lg max-w-2xl mx-auto">
          Inscription gratuite en moins de deux minutes. Rejoins la conversation dès maintenant.
        </p>
        <div class="relative mt-10 flex flex-wrap justify-center gap-4">
          <BaseButton :tag="'router-link'" :to="'/signup'" variant="night" size="xl" icon="fa-solid fa-user-plus">
            Je m'inscris gratuitement
          </BaseButton>
          <BaseButton :tag="'router-link'" :to="'/about'" variant="outline" size="xl" :shine="false">
            En savoir plus
          </BaseButton>
        </div>
      </div>
    </section>

    <!-- ====== FOOTER ====== -->
    <footer class="w-full bg-blue-night text-white">
      <div class="w-full px-6 md:px-12 py-14 grid md:grid-cols-4 gap-10">
        <div class="md:col-span-2">
          <div class="flex items-center gap-2.5 mb-4">
            <img src="@/assets/logo.png" alt="Logo YOWL" class="w-9 h-9" />
            <span class="font-poppins font-extrabold text-xl">YOWL</span>
          </div>
          <p class="text-white/60 max-w-md leading-relaxed">
            La plateforme communautaire où les 13-35 ans partagent, commentent et réagissent
            sur les contenus du web. Ton avis compte.
          </p>
        </div>
        <div>
          <h3 class="font-poppins font-bold mb-4">Navigation</h3>
          <ul class="space-y-2.5 text-white/60">
            <li><router-link to="/feed" class="hover:text-orange-primary transition-colors">Le fil</router-link></li>
            <li><router-link to="/about" class="hover:text-orange-primary transition-colors">À propos</router-link></li>
            <li><router-link to="/faq" class="hover:text-orange-primary transition-colors">FAQ</router-link></li>
            <li><router-link to="/suggestion" class="hover:text-orange-primary transition-colors">Suggestions</router-link></li>
          </ul>
        </div>
        <div>
          <h3 class="font-poppins font-bold mb-4">Légal</h3>
          <ul class="space-y-2.5 text-white/60">
            <li><router-link to="/policy" class="hover:text-orange-primary transition-colors">Charte de la communauté</router-link></li>
            <li><router-link to="/policy" class="hover:text-orange-primary transition-colors">Confidentialité</router-link></li>
          </ul>
        </div>
      </div>
      <div class="border-t border-white/10 py-5 text-center text-sm text-white/50">
        © {{ new Date().getFullYear() }} YOWL Community — Réalisé par LONG Corp
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useReviewStore } from '@/stores/review';

const reviewStore = useReviewStore();

const scrolled = ref(false);
const statsSection = ref(null);
const counters = ref({ nbUsers: 0, nbReviews: 0, nbComments: 0, nbMeanReviewsPerDay: 0 });
let observers = [];

const avatarColors = ['#FF6B35', '#1E2A38', '#7C5CFC', '#12B886'];

const marqueeTags = [
  'gaming', 'musique', 'cinéma', 'tech', 'mode', 'voyage', 'cuisine', 'sport',
  'photo', 'design', 'humour', 'actu', 'streaming', 'lecture', 'fitness', 'art',
];

const mockCards = [
  {
    author: 'Amina K.',
    initials: 'AK',
    color: '#7C5CFC',
    time: 'il y a 2 min',
    tag: 'tech',
    content: "Ce comparatif d'écouteurs est hyper complet, je viens enfin de me décider. Merci pour la découverte !",
    likes: 128, comments: 24, views: '1,2 k',
    style: { top: '0px', left: '0px', animationDelay: '0s', zIndex: 3 },
  },
  {
    author: 'Noah T.',
    initials: 'NT',
    color: '#12B886',
    time: 'il y a 18 min',
    tag: 'cinéma',
    content: 'La bande-annonce est incroyable mais le film ne tient pas ses promesses... Vous en pensez quoi ?',
    likes: 87, comments: 41, views: '956',
    style: { top: '185px', left: '60px', animationDelay: '1.2s', zIndex: 2 },
  },
  {
    author: 'Lisa M.',
    initials: 'LM',
    color: '#FF6B35',
    time: "il y a 1 h",
    tag: 'cuisine',
    content: 'Testé ce week-end : la meilleure recette de cookies que j\'ai trouvée sur le web. La communauté valide ?',
    likes: 203, comments: 58, views: '2,4 k',
    style: { top: '370px', left: '10px', animationDelay: '2.4s', zIndex: 1 },
  },
];

const features = [
  {
    icon: 'fa-solid fa-pen-nib',
    bg: 'bg-gradient-to-br from-orange-primary to-[#ff8c5a]',
    title: 'Publie tes avis',
    text: "Un article, une vidéo, un produit ? Partage le lien, ajoute tes photos et donne ton avis en toute liberté.",
  },
  {
    icon: 'fa-solid fa-comments',
    bg: 'bg-gradient-to-br from-blue-night to-blue-night-light',
    title: 'Discussions en fil',
    text: 'Réponds aux avis, lance des débats et suis les conversations grâce aux fils de commentaires imbriqués.',
  },
  {
    icon: 'fa-solid fa-thumbs-up',
    bg: 'bg-gradient-to-br from-[#7C5CFC] to-[#9d85ff]',
    title: 'Réactions instantanées',
    text: "Like ou dislike en un clic : l'opinion de la communauté se dessine en temps réel sur chaque contenu.",
  },
  {
    icon: 'fa-solid fa-magnifying-glass',
    bg: 'bg-gradient-to-br from-[#12B886] to-[#3dd9a4]',
    title: 'Recherche et filtres',
    text: "Retrouve les avis par mots-clés, tags, popularité ou fraîcheur. Le contenu qui t'intéresse, sans bruit.",
  },
  {
    icon: 'fa-solid fa-chart-line',
    bg: 'bg-gradient-to-br from-[#F59F00] to-[#ffc94d]',
    title: 'Statistiques personnelles',
    text: "Suis l'impact de tes publications : vues, réactions et engagement, le tout dans ton tableau de bord.",
  },
  {
    icon: 'fa-solid fa-shield-heart',
    bg: 'bg-gradient-to-br from-[#E64980] to-[#f783ac]',
    title: 'Espace bienveillant',
    text: 'Une charte claire et une modération active pour des échanges respectueux entre 13 et 35 ans.',
  },
];

const steps = [
  {
    icon: 'fa-solid fa-user-plus',
    title: 'Crée ton compte',
    text: 'Inscription gratuite avec ton email. Un code de vérification et te voilà membre de la communauté.',
  },
  {
    icon: 'fa-solid fa-link',
    title: 'Partage un contenu',
    text: "Colle le lien d'une page qui t'a marqué, ajoute ton avis, des images et quelques tags.",
  },
  {
    icon: 'fa-solid fa-bolt',
    title: 'Fais réagir',
    text: 'La communauté commente, like et débat. Ton avis devient une conversation.',
  },
];

const testimonials = [
  {
    text: "Enfin un endroit où je peux donner mon avis sur ce que je regarde sans me faire juger. L'ambiance est vraiment cool.",
    author: 'Sarah, 19 ans',
    role: 'Membre depuis 6 mois',
    initials: 'SA',
    color: '#7C5CFC',
  },
  {
    text: "J'ai découvert plein de pépites grâce aux reviews de la communauté. C'est devenu mon réflexe avant d'acheter quoi que ce soit.",
    author: 'Mehdi, 24 ans',
    role: 'Top contributeur',
    initials: 'ME',
    color: '#12B886',
  },
  {
    text: 'Le système de tags et de filtres est super pratique. Je retrouve en deux secondes les sujets qui me passionnent.',
    author: 'Chloé, 27 ans',
    role: 'Membre active',
    initials: 'CH',
    color: '#FF6B35',
  },
];

// Bookmarklet : ouvre le composeur /share avec l'URL et le titre de la page courante
const bookmarkletHref = computed(() => {
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  const code = `(function(){window.open('${origin}/share?url='+encodeURIComponent(location.href)+'&title='+encodeURIComponent(document.title),'yowl-share','width=640,height=760');})()`;
  return `javascript:${code}`;
});

const displayed = computed(() => ({
  nbUsers: formatNumber(counters.value.nbUsers),
  nbReviews: formatNumber(counters.value.nbReviews),
  nbComments: formatNumber(counters.value.nbComments),
  nbMeanReviewsPerDay: counters.value.nbMeanReviewsPerDay,
}));

const stats = computed(() => [
  { label: 'Membres actifs', value: displayed.value.nbUsers, suffix: '+' },
  { label: 'Avis publiés', value: displayed.value.nbReviews, suffix: '' },
  { label: 'Commentaires échangés', value: displayed.value.nbComments, suffix: '' },
  { label: 'Avis par jour', value: displayed.value.nbMeanReviewsPerDay, suffix: '' },
]);

function formatNumber(n) {
  return new Intl.NumberFormat('fr-FR').format(Math.round(n));
}

// Compteurs animés déclenchés à l'apparition de la section stats
function animateCounters(target) {
  const duration = 1600;
  const start = performance.now();
  const from = { nbUsers: 0, nbReviews: 0, nbComments: 0, nbMeanReviewsPerDay: 0 };

  const tick = (now) => {
    const progress = Math.min((now - start) / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 3);
    counters.value = {
      nbUsers: from.nbUsers + (target.nbUsers - from.nbUsers) * ease,
      nbReviews: from.nbReviews + (target.nbReviews - from.nbReviews) * ease,
      nbComments: from.nbComments + (target.nbComments - from.nbComments) * ease,
      nbMeanReviewsPerDay: Math.round((from.nbMeanReviewsPerDay + (target.nbMeanReviewsPerDay - from.nbMeanReviewsPerDay) * ease) * 10) / 10,
    };
    if (progress < 1) requestAnimationFrame(tick);
  };
  requestAnimationFrame(tick);
}

const onScroll = () => {
  scrolled.value = window.scrollY > 40;
};

onMounted(async () => {
  window.addEventListener('scroll', onScroll, { passive: true });

  // Reveal au scroll
  const revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 }
  );
  document.querySelectorAll('.reveal').forEach((el) => revealObserver.observe(el));
  observers.push(revealObserver);

  // KPI live
  await reviewStore.getKPI();
  const kpi = reviewStore.kpi || {};
  const target = {
    nbUsers: kpi.nbUsers || 0,
    nbReviews: kpi.nbReviews || 0,
    nbComments: kpi.nbComments || 0,
    nbMeanReviewsPerDay: kpi.nbMeanReviewsPerDay || 0,
  };
  // Afficher immédiatement les vraies valeurs (le hero les utilise sans scroll)
  counters.value = { ...target };

  const statsObserver = new IntersectionObserver(
    (entries) => {
      if (entries.some((e) => e.isIntersecting)) {
        animateCounters(target);
        statsObserver.disconnect();
      }
    },
    { threshold: 0.3 }
  );
  if (statsSection.value) statsObserver.observe(statsSection.value);
  observers.push(statsObserver);
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll);
  observers.forEach((o) => o.disconnect());
  observers = [];
});
</script>

<style scoped>
/* --- Etapes numérotées (section partage) --- */
.step-num {
  flex-shrink: 0;
  width: 22px;
  height: 22px;
  border-radius: 9999px;
  background: rgba(255, 107, 53, 0.12);
  color: #ff6b35;
  font-weight: 700;
  font-size: 12px;
  display: grid;
  place-items: center;
}

/* --- Hero --- */
.hero-gradient {
  background:
    radial-gradient(ellipse 80% 60% at 70% 20%, rgba(255, 107, 53, 0.18), transparent 60%),
    radial-gradient(ellipse 60% 50% at 20% 80%, rgba(124, 92, 252, 0.14), transparent 60%),
    linear-gradient(160deg, #1e2a38 0%, #16202c 60%, #1a2433 100%);
}

.hero-grid {
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.5) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.5) 1px, transparent 1px);
  background-size: 56px 56px;
  mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, black 30%, transparent 75%);
}

.gradient-text-hero {
  background: linear-gradient(120deg, #ff6b35 10%, #ffb02e 50%, #ff6b35 90%);
  background-size: 200% auto;
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  animation: gradientSlide 4s linear infinite;
}

@keyframes gradientSlide {
  to {
    background-position: 200% center;
  }
}

.draw-line {
  stroke-dasharray: 220;
  stroke-dashoffset: 220;
  animation: drawLine 1.4s ease forwards 0.8s;
}

@keyframes drawLine {
  to {
    stroke-dashoffset: 0;
  }
}

/* --- Blobs flottants --- */
.blob {
  position: absolute;
  border-radius: 9999px;
  filter: blur(90px);
  opacity: 0.5;
  animation: blobFloat 14s ease-in-out infinite alternate;
}
.blob-1 {
  width: 480px;
  height: 480px;
  top: -120px;
  right: -100px;
  background: rgba(255, 107, 53, 0.35);
}
.blob-2 {
  width: 380px;
  height: 380px;
  bottom: -80px;
  left: -120px;
  background: rgba(124, 92, 252, 0.3);
  animation-delay: 3s;
}
.blob-3 {
  width: 260px;
  height: 260px;
  top: 40%;
  left: 45%;
  background: rgba(18, 184, 134, 0.18);
  animation-delay: 6s;
}
.blob-stats {
  width: 420px;
  height: 420px;
  top: -180px;
  right: -120px;
  background: rgba(255, 107, 53, 0.25);
}
.blob-testimonials {
  width: 500px;
  height: 500px;
  bottom: -220px;
  left: -160px;
  background: rgba(255, 107, 53, 0.15);
}

@keyframes blobFloat {
  from {
    transform: translate(0, 0) scale(1);
  }
  to {
    transform: translate(40px, -30px) scale(1.12);
  }
}

/* --- Mockup de cartes --- */
.mock-card {
  animation: cardFloat 6s ease-in-out infinite;
}

@keyframes cardFloat {
  0%,
  100% {
    transform: translateY(0) rotate(-1deg);
  }
  50% {
    transform: translateY(-14px) rotate(0.5deg);
  }
}

.float-emoji-badge {
  position: absolute;
  display: grid;
  place-items: center;
  width: 52px;
  height: 52px;
  border-radius: 9999px;
  color: white;
  font-size: 1.1rem;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
  animation: badgeFloat 5s ease-in-out infinite;
}
.badge-like {
  top: 6%;
  right: 4%;
  background: linear-gradient(135deg, #ff6b35, #ff8c5a);
}
.badge-comment {
  top: 48%;
  right: -10px;
  background: linear-gradient(135deg, #7c5cfc, #9d85ff);
  animation-delay: 1.4s;
}
.badge-star {
  bottom: 4%;
  right: 18%;
  background: linear-gradient(135deg, #12b886, #3dd9a4);
  animation-delay: 2.8s;
}

@keyframes badgeFloat {
  0%,
  100% {
    transform: translateY(0) scale(1);
  }
  50% {
    transform: translateY(-16px) scale(1.08);
  }
}

/* --- Marquee des tags --- */
.marquee {
  animation: marqueeScroll 30s linear infinite;
}
.marquee:hover {
  animation-play-state: paused;
}

@keyframes marqueeScroll {
  from {
    transform: translateX(0);
  }
  to {
    transform: translateX(-50%);
  }
}

/* --- Reveal au scroll --- */
.reveal {
  opacity: 0;
  transform: translateY(28px);
  transition:
    opacity 0.7s ease,
    transform 0.7s ease;
}
.reveal-visible {
  opacity: 1;
  transform: translateY(0);
}

/* --- Ping lent du badge hero --- */
.animate-ping-slow {
  animation: pingSlow 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}

@keyframes pingSlow {
  0% {
    box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.7);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(255, 107, 53, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(255, 107, 53, 0);
  }
}

/* --- Anneaux décoratifs CTA --- */
.cta-rings {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 85% 20%, rgba(255, 255, 255, 0.18) 0, transparent 26%),
    radial-gradient(circle at 10% 90%, rgba(255, 255, 255, 0.12) 0, transparent 30%);
}
.cta-rings::before,
.cta-rings::after {
  content: '';
  position: absolute;
  border: 2px solid rgba(255, 255, 255, 0.15);
  border-radius: 9999px;
  animation: ringPulse 6s ease-in-out infinite;
}
.cta-rings::before {
  width: 300px;
  height: 300px;
  top: -140px;
  left: -100px;
}
.cta-rings::after {
  width: 420px;
  height: 420px;
  bottom: -220px;
  right: -140px;
  animation-delay: 3s;
}

@keyframes ringPulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 0.6;
  }
  50% {
    transform: scale(1.15);
    opacity: 1;
  }
}
</style>
