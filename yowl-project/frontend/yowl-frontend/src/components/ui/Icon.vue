<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <component
    :is="rendu"
    :size="size"
    :stroke-width="strokeWidth"
    :fill="filled ? 'currentColor' : 'none'"
    aria-hidden="true"
    focusable="false"
  />
</template>

<script setup>
/**
 * Une icône, en SVG.
 *
 * L'application utilisait Font Awesome, une police d'icônes. Deux problèmes
 * l'ont fait remplacer. Le premier, visible : Tailwind 4 inline la feuille de
 * Font Awesome sans que Vite n'émette ses fichiers de police, et le site
 * livré n'affichait aucune icône, les .woff2 renvoyant l'index.html du site.
 * Le second, de fond : 72 ko de CSS et 231 ko de polices étaient livrés pour
 * les 111 icônes réellement employées.
 *
 * Ici chaque icône est un composant importé nommément, donc seul ce qui est
 * listé ci-dessous part dans le paquet. Ajouter une icône veut dire ajouter
 * une ligne à la table : c'est volontaire, ça garde le compte visible.
 *
 * Les noms sont ceux de Font Awesome, sans le préfixe, pour que la migration
 * n'ait pas demandé de rebaptiser cent cinquante appels.
 */
import {
  ArrowLeft,
  ArrowRight,
  AtSign,
  Ban,
  Bell,
  BellOff,
  Bold,
  Bookmark,
  Bug,
  Calendar,
  Camera,
  ChartLine,
  ChartPie,
  Check,
  CheckCheck,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  CircleAlert,
  CircleCheck,
  CircleHelp,
  Clipboard,
  ClipboardList,
  Clock,
  CloudUpload,
  Copy,
  DoorOpen,
  Download,
  Ellipsis,
  ExternalLink,
  Eye,
  EyeOff,
  FileDown,
  FileSignature,
  FileSpreadsheet,
  FileText,
  Flag,
  FolderOpen,
  Gauge,
  Globe,
  Handshake,
  Hash,
  Heading,
  Heart,
  History,
  Hourglass,
  House,
  Image,
  Info,
  Italic,
  KeyRound,
  Landmark,
  Languages,
  Lightbulb,
  Link,
  List,
  ListOrdered,
  Lock,
  LogIn,
  LogOut,
  Mail,
  MailOpen,
  Megaphone,
  MessageCircle,
  MessageCircleMore,
  MessagesSquare,
  Minus,
  MoveRight,
  Newspaper,
  Pen,
  PenTool,
  Plus,
  Puzzle,
  Quote,
  Reply,
  Rocket,
  RotateCcw,
  RotateCw,
  Scale,
  Search,
  Send,
  ShieldCheck,
  ShieldUser,
  SlidersHorizontal,
  Smartphone,
  Square,
  SquarePen,
  Star,
  Strikethrough,
  ThumbsDown,
  ThumbsUp,
  Trash2,
  TrendingUp,
  TriangleAlert,
  Unplug,
  Upload,
  User,
  UserCog,
  UserPlus,
  UserX,
  Users,
  Wand,
  X,
  Youtube,
  Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';

const ICONES = {
  'arrow-left': ArrowLeft,
  'arrow-right': ArrowRight,
  'arrow-right-from-bracket': LogOut,
  'arrow-right-long': MoveRight,
  'arrow-rotate-left': RotateCcw,
  'arrow-trend-up': TrendingUp,
  'arrow-up-from-bracket': Upload,
  'arrow-up-right-from-square': ExternalLink,
  'at': AtSign,
  'ban': Ban,
  'bell': Bell,
  'bell-slash': BellOff,
  'bold': Bold,
  'bolt': Zap,
  'bookmark': Bookmark,
  'bug': Bug,
  'building-columns': Landmark,
  'bullhorn': Megaphone,
  'calendar': Calendar,
  'camera': Camera,
  'chart-line': ChartLine,
  'chart-pie': ChartPie,
  'check': Check,
  'check-double': CheckCheck,
  'chevron-down': ChevronDown,
  'chevron-left': ChevronLeft,
  'chevron-right': ChevronRight,
  'circle-check': CircleCheck,
  'circle-exclamation': CircleAlert,
  'circle-info': Info,
  'circle-question': CircleHelp,
  'clipboard': Clipboard,
  'clipboard-list': ClipboardList,
  'clock': Clock,
  'clock-rotate-left': History,
  'cloud-arrow-up': CloudUpload,
  'comment': MessageCircle,
  'comment-dots': MessageCircleMore,
  'comments': MessagesSquare,
  'copy': Copy,
  'door-open': DoorOpen,
  'download': Download,
  'ellipsis': Ellipsis,
  'envelope': Mail,
  'envelope-open': MailOpen,
  'eye': Eye,
  'eye-slash': EyeOff,
  'file-arrow-down': FileDown,
  'file-csv': FileSpreadsheet,
  'file-lines': FileText,
  'file-signature': FileSignature,
  'flag': Flag,
  'folder-open': FolderOpen,
  'gauge-high': Gauge,
  'globe': Globe,
  'h': Heading,
  'handshake-angle': Handshake,
  'hashtag': Hash,
  'heading': Heading,
  'heart': Heart,
  'hourglass': Hourglass,
  'house': House,
  'image': Image,
  'italic': Italic,
  'key': KeyRound,
  'language': Languages,
  'lightbulb': Lightbulb,
  'link': Link,
  'list-ol': ListOrdered,
  'list-ul': List,
  'lock': Lock,
  'magnifying-glass': Search,
  'minus': Minus,
  'mobile-screen-button': Smartphone,
  'newspaper': Newspaper,
  'paper-plane': Send,
  'pen': Pen,
  'pen-nib': PenTool,
  'pen-to-square': SquarePen,
  'plug-circle-exclamation': Unplug,
  'plus': Plus,
  'puzzle-piece': Puzzle,
  'quote-left': Quote,
  'reply': Reply,
  'right-to-bracket': LogIn,
  'rocket': Rocket,
  'rotate-left': RotateCcw,
  'rotate-right': RotateCw,
  'scale-balanced': Scale,
  'search': Search,
  'shield-halved': ShieldCheck,
  'shield-heart': ShieldCheck,
  'sliders': SlidersHorizontal,
  'square': Square,
  'star': Star,
  'strikethrough': Strikethrough,
  'thumbs-down': ThumbsDown,
  'thumbs-up': ThumbsUp,
  'times': X,
  'trash': Trash2,
  'triangle-exclamation': TriangleAlert,
  'user': User,
  'user-gear': UserCog,
  'user-group': Users,
  'user-plus': UserPlus,
  'user-shield': ShieldUser,
  'user-slash': UserX,
  'users': Users,
  'wand-magic-sparkles': Wand,
  'xmark': X,
  'youtube': Youtube,
};

const props = defineProps({
  /** Nom Font Awesome sans préfixe, par exemple « thumbs-up ». */
  name: { type: String, required: true },
  /** Côté en pixels. Les icônes de Lucide sont dessinées sur une grille de 24. */
  size: { type: [Number, String], default: 18 },
  /** Épaisseur du trait. 2 est la valeur de Lucide, 1.9 s'accorde au texte. */
  strokeWidth: { type: [Number, String], default: 1.9 },
  /**
   * Icône pleine plutôt qu'en contour.
   *
   * Font Awesome livrait deux familles, « solid » et « regular », et l'état
   * actif se disait en changeant de famille. Lucide n'en a qu'une : l'état
   * se dit en remplissant le trait, ce qui donne le même signal sans doubler
   * le poids livré.
   */
  filled: { type: Boolean, default: false },
});

const rendu = computed(() => {
  const icone = ICONES[props.name];

  if (!icone && import.meta.env.DEV) {
    // En développement seulement : une icône absente doit se voir pendant
    // qu'on écrit la page, pas se découvrir en production.
    console.warn(`[YOWL] Icône inconnue : « ${props.name} ». Ajoute-la dans Icon.vue.`);
  }

  return icone ?? ICONES.circle;
});
</script>
