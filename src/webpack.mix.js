const mix = require('laravel-mix')

mix.js('assets/js/index.js', 'dist')
   .sass('assets/scss/index.scss', 'dist')
   .setPublicPath('dist')

mix.js('assets/js/admin.js', 'dist')
   .sass('assets/scss/admin.scss', 'dist')
   .setPublicPath('dist')