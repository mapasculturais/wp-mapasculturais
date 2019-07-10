<template>
    <section class="mc-w mc-w-agenda">
        <FiltersBar v-if="showFilters" @change="updateFilters"/>
        <WidgetHeader :showArrows="false">Minha agenda</WidgetHeader>
        <AgendaMonth v-for="month in months" :key="month" :events="eventsByMonth[month] || []" :filters="filters" :month="month"/>
    </section>
</template>

<script>
    import AgendaMonth from './AgendaMonth.vue'
    import DateMixin from './mixins/DateMixin'
    import WidgetMixin from './mixins/WidgetMixin'

    export default {
        name: 'MyAgenda',
        components: {
            AgendaMonth
        },
        mixins: [
            DateMixin,
            WidgetMixin
        ],
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
            },
            months () {
                return [...Array(13).keys()].slice(this.currentMonth)
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
                this.$mc.EventOccurrences.find({
                    from: new Date().toISOString().slice(0, 10),
                    to: `${new Date().getFullYear()}-12-31`,
                    ...this.filters
                }).then(response => {
                    this.events = response.data
                })
            }
        }
    }
</script>