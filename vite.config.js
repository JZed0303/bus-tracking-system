import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import react from '@vitejs/plugin-react';

export default defineConfig({
  build: {
    manifest: true,
    rtl: true,
    outDir: 'public/build/',
    cssCodeSplit: true,
    rollupOptions: {
      output: {
        assetFileNames: (asset) => {
          const name = asset?.name || '';
          const ext = name.split('.').pop();

          if (ext === 'css') {
            return `css/[name].min.css`;
          }
          return `icons/${name}`;
        },
        entryFileNames: 'js/[name].js',
      },
    },
  },

  plugins: [
    laravel({
      input: [
        // SCSS entry points
        'resources/scss/app.scss',
        'resources/scss/bootstrap.scss',
        'resources/scss/icons.scss',

        // React entry points
        'resources/js/app.jsx',
        'resources/js/admin-chat-root.jsx',
        'resources/js/admin-chat-threads.jsx',
        'resources/js/admin-bus-chat.jsx',

        // Group chat entry
        'resources/js/admin-group-chat.jsx',
      ],
      refresh: true,
    }),

    react(),

    viteStaticCopy({
      targets: [
        { src: 'resources/fonts', dest: '' },
        { src: 'resources/images', dest: '' },

        // ⚠️ Not needed for @vite() manifest builds.
        // Keep ONLY if you have legacy scripts referenced directly from public/build
        // (e.g. <script src="/build/js/somefile.js"></script> without @vite).
        { src: 'resources/js', dest: '' },

        { src: 'resources/libs', dest: '' },
      ],
    }),
  ],
});
