import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import vueDevTools from 'vite-plugin-vue-devtools'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueJsx(),
    vueDevTools(),
    VitePWA({
      registerType: 'autoUpdate',
      strategies: 'injectManifest',
      srcDir: 'src',
      filename: 'sw.js',
      injectRegister: 'auto',
      devOptions: {
        enabled: true,
        type: 'module',
      },
      includeAssets: ['favicon.ico', 'apple-touch-icon.png'],
      manifest: {
        name: 'YOWL Community',
        short_name: 'YOWL',
        // Identifiant stable de l'application installee. Sans lui, le
        // navigateur derive l'identite de start_url : changer cette derniere
        // ferait apparaitre une seconde application au lieu de mettre a jour
        // celle qui est deja sur l'ecran d'accueil.
        id: '/',
        description:
          "La communauté où les 13-35 ans partagent leurs avis sur les contenus du web.",
        lang: 'fr',
        start_url: '/feed',
        scope: '/',
        display: 'standalone',
        orientation: 'portrait',
        // La couleur du manifeste habille l'ecran de demarrage dessine par
        // le systeme. En reprenant le fond, la barre d'etat et l'ecran ne
        // font plus qu'une seule surface. L'application lancee, la balise
        // theme-color de index.html prend le relais et suit l'en-tete.
        theme_color: '#1e2a38',
        background_color: '#1e2a38',
        icons: [
          {
            src: '/pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'any',
          },
          {
            src: '/pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any',
          },
          {
            src: '/pwa-maskable-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable',
          },
        ],
        // Partage natif : YOWL apparait dans le menu de partage du systeme
        share_target: {
          action: '/share',
          method: 'GET',
          params: {
            title: 'title',
            text: 'text',
            url: 'url',
          },
        },
        shortcuts: [
          {
            name: 'Publier un avis',
            url: '/share',
            icons: [{ src: '/pwa-192x192.png', sizes: '192x192' }],
          },
        ],
      },
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
})
