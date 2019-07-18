const mix = require('laravel-mix')

mix.sass('themes/mapa-culturais/assets/scss/app.scss', 'themes/mapa-culturais/dist')
    .combine('themes/mapa-culturais/assets/javascript/components/*.js', 'themes/mapa-culturais/dist/app.js')
