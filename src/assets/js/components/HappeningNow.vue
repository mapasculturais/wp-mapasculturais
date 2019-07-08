<template>
    <section class="mc-w mc-w-now">
        <WidgetHeader :showArrows="false">Agora</WidgetHeader>
        <div class="mc-w-now__content">
            <div class="mc-w-now__events" v-if="eventsNow.length > 0">
                <EventRow class="mc-w-now__event" v-for="event in eventsNow" :key="event.id" :event="event"/>
            </div>
            <div class="mc-w-now__no-content" v-else>
                Nenhum evento ocorrendo no momento
            </div>
        </div>
    </section>
</template>

<script>
    import axios from 'axios'

    import EventRow from './EventRow.vue'
    import WidgetHeader from './WidgetHeader.vue'

    export default {
        name: 'HappeningNow',
        components: {
            EventRow,
            WidgetHeader
        },
        data () {
           return {
               eventsToday: [],
               eventsNow: [],
               eventsNow$: null
           }
        },
        created () {
            const today = new Date().toISOString().slice(0, 10)
            axios.get('/mcapi/eventOccurrence/', {
                params: {
                    from: today,
                    to: today
                }
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
