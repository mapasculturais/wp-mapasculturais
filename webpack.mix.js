const mix = require('laravel-mix')

mix.sass('themes/mapas-culturais/assets/scss/app.scss', 'themes/mapas-culturais/dist')
    .combine('themes/mapas-culturais/assets/javascript/components/*.js', 'themes/mapas-culturais/dist/app.js')
