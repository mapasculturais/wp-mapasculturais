<template>
    <div class="mc-w__filters">
        <div class="mc-w__filters-row">
            <input type="text" aria-label="Buscar evento" placeholder="Buscar evento">
            <label>
                <span>de</span>
                <input type="date">
            </label>
            <label for="">
                <span>até</span>
                <input type="date">
            </label>
            <Multiselect v-model="languages" :options="$mc.Taxonomies.languages" :searchable="false" :multiple="true" :taggable="true" placeholder="Linguagens" select-label="Aperte Enter para selecionar" selected-label="Opção selecionada" deselect-label="Aperte Enter para remover"/>
            <Multiselect v-model="rates" :options="[]" :searchable="false" :multiple="true" :taggable="true" placeholder="Classificação Etária" select-label="Aperte Enter para selecionar" selected-label="Opção selecionada" deselect-label="Aperte Enter para remover"/>
            <button aria-label="Filtrar">
                <i class="fas fa-search" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</template>

<script>
    import Multiselect from 'vue-multiselect'

    export default {
        name: 'FiltersBar',
        components: {
            Multiselect
        },
        data () {
            return {
                languages: [],
                rates: []
            }
        }
    }
</script>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
