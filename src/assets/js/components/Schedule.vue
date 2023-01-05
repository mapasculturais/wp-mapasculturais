<template>
    <div class="mc-cmb-schedule">
        <div class="row">
            <div class="column large-12">
                <div class="title-button mb-20 left">
                    <h3 v-html="title"></h3>
                    <a @click="displayFilters = !displayFilters" class="small-link mt-5 d-block"><i class="fas fa-filter"></i>&nbsp; Filtrar Resultados</a>
                </div>
            </div>

            <div class="column large-12">
                <FiltersBar v-if="displayFilters" @change="updateFilters"/>
            </div>
        </div>

        <div class="row" v-if="events.length > 0">
            <div class="column large-6" v-for="event in events" :key="event.id">
                <div class="card card-schedule">
                    <div class="card--image" :style="'background-image: url('+')'">
                        <i class="card--icon fas fa-bookmark"></i>
                        <div class="card--block">
                            <a tabindex="-1" :href="event.permalink">
                                <div class="card--title" v-html="event.name"/>
                            </a>

                            <div class="card--taxonomy">
                                <span v-html="event.terms.linguagem.join(', ')"></span>
                            </div>
                        </div>
                    </div>

                    <div class="card--footer">
                        <div class="card--info fz-12" v-if="event.occurrences.length > 0">
                            <i class="far fa-calendar-alt"></i>
                            <p v-html="event.occurrences[0].description"></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import WidgetMixin from './mixins/WidgetMixin'

export default {
    name: 'Schedule',
    props: ['title'],
    mixins: [
        WidgetMixin
    ],
    data () {
        return {
            events : [],
            displayFilters : false,
        }
    },
    watch: {
        filters: 'fetchEvents'
    },
    created () {
        this.fetchEvents()
    },
    methods: {
        fetchEvents () {
            const today = new Date()
            const lastDay = new Date(today.getFullYear(), today.getMonth(), 31)

            this.$mc.EventOccurrences.find({
                from: today.toISOString().slice(0, 10),
                to: lastDay.toISOString().slice(0, 10),
                groupBy : 'event',
                ...this.filters,
            }).then(response => {
                this.events = response.data
            })
        }
    }
}
</script>


<style>
.mc-cmb-schedule{ width: 100%; }
</style>
