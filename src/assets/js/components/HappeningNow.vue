<template>
    <section class="mc-w mc-w-now">
        <FiltersBar v-if="showFilters"/>
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
               eventsNow: [],
               eventsNow$: null
           }
        },
        created () {
            const today = new Date().toISOString().slice(0, 10)
            this.$mc.EventOccurrences.find({
                from: today,
                to: today
            }).then(response => {
                this.eventsToday = response.data
                this.getEventsNow()
            })
        },
        mounted () {
            this.eventsNow$ = window.setInterval(this.getEventsNow, 60000)
        },
        beforeDestroy () {
            window.clearInterval(this.eventsNow$)
        },
        methods: {
            getEventsNow () {
                const now = new Date()
                this.eventsNow = this.eventsToday.filter(event => {
                    const beginning = new Date(event.occurrence.starts)
                    const end = new Date(event.occurrence.ends)
                    return beginning < now && end > now
                })
            }
        }
    }
</script>
