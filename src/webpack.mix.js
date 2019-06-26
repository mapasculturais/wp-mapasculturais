const mix = require('laravel-mix')

mix.js('assets/js/index.js', 'dist')
   .sass('assets/scss/index.scss', 'dist')
   .setPublicPath('dist')