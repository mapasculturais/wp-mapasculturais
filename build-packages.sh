#!/bin/bash

echo "Preparando pacote do plugin"
cp -a src wp-mapasculturais
cp LICENSE wp-mapasculturais
rm -rf wp-mapasculturais/node_modules
zip -r wp-mapasculturais.zip wp-mapasculturais
rm -rf wp-mapasculturais
echo "wp-mapasculturais.zip criado"

echo "Preparando pacote do tema"
cp -a themes/mapas-culturais mapasculturais-theme
cp LICENSE mapasculturais-theme
find mapasculturais-theme -name ".git*" -exec rm -rf {} \; #remove tudo o que começar com .git
zip -r mapasculturais-theme.zip mapasculturais-theme
rm -rf mapasculturais-theme
echo "mapasculturais-theme.zip criado"
