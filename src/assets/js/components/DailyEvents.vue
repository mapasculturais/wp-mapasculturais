<template>
    <section class="mc-w mc-w-day">
        <WidgetHeader @previous="previousDay" @next="nextDay">
            {{ currentDay }} de {{ monthString }} de {{ currentYear }}
        </WidgetHeader>
        <div class="mc-w-day__content">
            <div class="mc-w-day__period" v-for="(eventsOnPeriod, period) in eventsByPeriod" :key="period">
                <div class="mc-w-day__label">{{ period }}</div>
                <div class="mc-w-day__events" v-if="eventsOnPeriod.length > 0">
                    <EventRow class="mc-w-day__event" v-for="event in eventsOnPeriod" :key="event.id" :event="event"/>
                </div>
                <div class="mc-w-day__no-content" v-else>
                    Nenhum evento ocorrendo nesse período
                </div>
            </div>
        </div>
    </section>
</template>

<script>
    import EventRow from './EventRow.vue'
    import WidgetHeader from './WidgetHeader.vue'
    import WidgetMixin from './mixins/WidgetMixin'

    export default {
        name: 'DailyEvents',
        components: {
            EventRow,
            WidgetHeader
        },
        mixins: [WidgetMixin],
        props: {
            day: { type: Number, default: () => new Date().getDate() }
        },
        data () {
            return {
                currentDay: this.$props.day,
                events: []
            }
        },
        computed: {
            eventsByPeriod () {
                const periods = {
                    'Manhã': [],
                    'Tarde': [],
                    'Noite': []
                }
                const midday = new Date(this.currentYear, this.currentMonth - 1, this.currentDay, 12)
                const sixPM = new Date(this.currentYear, this.currentMonth - 1, this.currentDay, 18)
                for (const event of this.events) {
                    const beginning = new Date(event.occurrence.starts)
                    if (beginning <= midday) {
                        periods['Manhã'].push(event)
                    } else if (beginning <= sixPM) {
                        periods['Tarde'].push(event)
                    } else {
                        periods['Noite'].push(event)
                    }
                }
                return periods
            },
            isoDate () {
                return `${this.currentYear}-${String(this.currentMonth).padStart(2, '0')}-${String(this.currentDay).padStart(2, '0')}`
            }
        },
        created () {
            this.fetchEvents()
        },
        methods: {
            fetchEvents () {
                this.$mc.EventOccurrences.find({
                    from: this.isoDate,
                    to: this.isoDate
                }).then(response => {
                    this.events = response.data
                })
            },
            nextDay () {
                const date = new Date(this.currentYear, this.currentMonth - 1, this.currentDay + 1)
                this.currentDay = date.getDate()
                this.currentMonth = date.getMonth() + 1
                this.currentYear = date.getFullYear()
                this.fetchEvents()
            },
            previousDay () {
                const date = new Date(this.currentYear, this.currentMonth - 1, this.currentDay - 1)
                this.currentDay = date.getDate()
                this.currentMonth = date.getMonth() + 1
                this.currentYear = date.getFullYear()
                this.fetchEvents()
            }
        }
    }
</script>
