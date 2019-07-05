<template>
    <section class="mc-w mc-w-agenda">
        <WidgetHeader :showArrows="false">Minha agenda</WidgetHeader>
        <AgendaMonth v-for="i in (12 - currentMonth + 1)" :key="currentMonth + i - 1" :events="eventsByMonth[currentMonth + i - 1] || []" :month="currentMonth + i - 1"/>
    </section>
</template>

<script>
    import axios from 'axios'

    import AgendaMonth from './AgendaMonth.vue'
    import WidgetHeader from './WidgetHeader.vue'
    import WidgetMixin from './mixins/WidgetMixin'

    export default {
        name: 'MyAgenda',
        components: {
            AgendaMonth,
            WidgetHeader
        },
        mixins: [WidgetMixin],
        data () {
            return {
                events: []
            }
        },
        computed: {
            eventsByMonth () {
                const months = {}
                this.events.forEach(event => {
                    const month = new Date(event.occurrence.starts).getMonth() + 1
                    if (months[month]) {
                        months[month].push(event)
                    } else {
                        months[month] = [event]
                    }
                })
                return months
            }
        },
        created () {
            axios.get('/mcapi/eventOccurrence/', {
                params: {
                    from: new Date().toISOString().slice(0, 10),
                    to: `${new Date().getFullYear()}-12-31`
                }
            }).then(response => {
                this.events = response.data
            })
        }
    }
</script>