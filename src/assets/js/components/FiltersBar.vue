<template>
    <div class="mc-w__filters">
        <div class="mc-w__filters-row">
            <input v-model="keyword" type="text" aria-label="Buscar evento" placeholder="Buscar evento">
            <label v-if="showDates">
                <span>de</span>
                <input type="date" v-model="from">
            </label>
            <label v-if="showDates">
                <span>até</span>
                <input type="date" v-model="to">
            </label>
            <Multiselect v-model="languages" :options="$mc.Taxonomies.languages" :searchable="false" :multiple="true" :taggable="true" placeholder="Linguagens" select-label="Selecionar" selected-label="Opção selecionada" deselect-label="Remover"/>
            <Multiselect v-model="rate" :options="['Livre', '18 anos', '16 anos', '14 anos', '12 anos', '10 anos']" :searchable="false" :multiple="false" :taggable="true" placeholder="Classificação Etária" select-label="Aperte Enter para selecionar" selected-label="Opção selecionada" deselect-label="Aperte Enter para remover"/>
            <button aria-label="Filtrar" @click="$emit('change', params)">
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
        props: {
            showDates: { type: Boolean, default: true }
        },
        data () {
            return {
                from: undefined,
                keyword: '',
                languages: [],
                rate: undefined,
                to: undefined
            }
        },
        computed: {
            params () {
                return {
                    'from': this.from,
                    'to': this.to,
                    '@keyword': this.keyword || undefined,
                    'term:linguagem': this.languages.length > 0
                        ? `IN(${this.languages.map(language => language.replace(`,`, `\\,`)).join(',')})`
                        : undefined,
                    'classificacaoEtaria': this.rate && `EQ(${this.rate})`
                }
            }
        }
    }
</script>
