<template>
    <section class="mc-w mc-w-now">
        <FiltersBar v-if="showFilters" :showDates="false" @change="updateFilters"/>
        <WidgetHeader :showArrows="false">Agora</WidgetHeader>
        <div class="mc-w-now__content">
            <div class="mc-w-now__events" v-if="eventsNow.length > 0">
                <EventRow class="mc-w-now__event" v-for="event in eventsNow" :key="event.id" :event="event" @selectEvent="openEventModal" @selectSpace="openSpaceModal"/>
            </div>
            <div class="mc-w-now__no-content" v-else>
                Nenhum evento ocorrendo no momento
            </div>
        </div>
        <EventModal v-if="modalEvent" :event="modalEvent" @close="closeEventModal"/>
        <SpaceModal v-if="modalSpace" :space="modalSpace" @close="closeSpaceModal"/>
    </section>
</template>

<script>
    import EventRow from './EventRow.vue'
    import ModalMixin from './mixins/ModalMixin'
    import WidgetMixin from './mixins/WidgetMixin'

    export default {
        name: 'HappeningNow',
        components: {
            EventRow
        },
        mixins: [
            ModalMixin,
            WidgetMixin
        ],
        data () {
           return {
               eventsToday: [],
               fetchEvents$: null
           }
        },
        computed: {
            eventsNow () {
                const now = new Date()
                return this.eventsToday.filter(event => {
                    const beginning = new Date(event.occurrence.starts)
                    const end = new Date(event.occurrence.ends)
                    return beginning < now && end > now
                })
            }
        },
        watch: {
            filters: 'fetchEvents'
        },
        created () {
            this.fetchEvents()
        },
        mounted () {
            this.fetchEvents$ = window.setInterval(this.fetchEvents,  5 * 60 * 1000)
        },
        beforeDestroy () {
            window.clearInterval(this.fetchEvents$)
        },
        methods: {
            fetchEvents () {
                const today = new Date().toISOString().slice(0, 10)
                this.$mc.EventOccurrences.find({
                    ...this.filters,
                    from: today,
                    to: today
                }).then(response => {
                    this.eventsToday = response.data
                })
            }
        }
    }
</script>
