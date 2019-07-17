<template>
    <div class="mc-w mc-w-calendar">
        <FiltersBar v-if="showFilters" :agents="agents" :spaces="spaces" @change="updateFilters"/>
        <WidgetHeader @previous="previousMonth" @next="nextMonth">{{ monthString }} de {{ currentYear }}</WidgetHeader>
        <Calendar defaultView="month" :events="calendarEvents" hideTitleBar hideViewSelector locale="pt-br" :selectedDate="selectedDate" :startWeekOnSunday="true" :style="{ 'min-height': '50vh' }" todayButton>
            <template class="mc-w-calendar__date" #cell-content="{ cell, view, events, goNarrower }">
                <div class="day"><span>{{ cell.content }}</span></div>
                <div class="mc-w-calendar__events" v-if="events.length > 0">
                    <div class="mc-w-calendar__event" role="button" v-for="event in events" :key="event.raw.id" @click="openEventModal(event.raw)">{{ event.raw.name }}</div>
                </div>
                <div class="mc-w-calendar__dots" v-if="events.length > 0">
                    <div class="mc-w-calendar__dot" role="button" v-for="event in events" :key="event.raw.id" @click="openEventModal(event.raw)"/>
                </div>
            </template>
        </Calendar>
        <EventModal v-if="modalEvent" :event="modalEvent" @close="closeEventModal"/>
        <SpaceModal v-if="modalSpace" :space="modalSpace" @close="closeSpaceModal"/>
    </div>
</template>

<script>
    import Calendar from 'vue-cal'

    import DateMixin from './mixins/DateMixin'
    import ModalMixin from './mixins/ModalMixin'
    import WidgetMixin from './mixins/WidgetMixin'

    export default {
        name: 'EventsCalendar',
        components: {
            Calendar,
        },
        mixins: [
            DateMixin,
            ModalMixin,
            WidgetMixin
        ],
        data () {
            return {
                events: []
            }
        },
        computed: {
            calendarEvents () {
                return this.events.map(event => ({
                    start: event.occurrence.starts.slice(0, 16),
                    end: event.occurrence.ends.slice(0, 16),
                    raw: event
                }))
            },
            selectedDate () {
                return `${this.currentYear}-${String(this.currentMonth).padStart(2, '0')}-01`
            }
        },
        watch: {
            currentMonth: 'fetchEvents',
            filters: 'fetchEvents'
        },
        created () {
            this.fetchEvents()
        },
        methods: {
            fetchEvents () {
                const firstDay = new Date(this.currentYear, this.currentMonth - 1, 1)
                const lastDay = new Date(this.currentYear, this.currentMonth, 0)
                this.$mc.EventOccurrences.find({
                    from: firstDay.toISOString().slice(0, 10),
                    to: lastDay.toISOString().slice(0, 10),
                    ...this.filters
                }).then(response => {
                    this.events = response.data
                })
            }
        }
    }
</script>
