<template>
    <section class="mc-w mc-w-list">
        <FiltersBar v-if="showFilters" @change="updateFilters"/>
        <WidgetHeader @previous="previousMonth" @next="nextMonth">
            {{ monthString }} de {{ currentYear }}
        </WidgetHeader>
        <div class="mc-w-list__content">
            <div class="mc-w-list__day" v-for="(eventsOnDay, day) in eventsByDay" :key="day">
                <div class="mc-w-list__date">
                    <div class="day">{{ new Date(eventsOnDay[0].occurrence.starts).getDate() }}</div>
                    <div class="weekday">{{ weekdayString(eventsOnDay[0].occurrence.starts) }}</div>
                </div>
                <div class="mc-w-list__events">
                    <EventRow class="mc-w-list__event" v-for="event in eventsOnDay" :key="event.id" :event="event" :showTime="false" @selectEvent="openEventModal" @selectSpace="openSpaceModal"/>
                </div>
            </div>
            <div class="mc-w-list__no-content" v-if="events.length === 0">
                Nenhum evento ocorrendo nesse mês
            </div>
            <EventModal v-if="modalEvent" :event="modalEvent" @close="closeEventModal"/>
            <SpaceModal v-if="modalSpace" :space="modalSpace" @close="closeSpaceModal"/>
        </div>
    </section>
</template>

<script>
    import DateMixin from './mixins/DateMixin'
    import EventRow from './EventRow.vue'
    import ModalMixin from './mixins/ModalMixin'
    import WidgetMixin from './mixins/WidgetMixin'

    export default {
        name: 'EventsList',
        components: {
            EventRow
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
            eventsByDay () {
                const days = {}
                this.events.forEach(event => {
                    const day = event.occurrence.starts_on
                    if (days[day]) {
                        days[day].push(event)
                    } else {
                        days[day] = [event]
                    }
                })
                return days
            }
        },
        watch: {
            currentMonth: {
                handler: function currentMonthWatchHandler () {
                    const firstDay = new Date(this.currentYear, this.currentMonth - 1, 1)
                    const lastDay = new Date(this.currentYear, this.currentMonth, 0)
                    this.$mc.EventOccurrences.find({
                        ...this.filters,
                        from: firstDay.toISOString().slice(0, 10),
                        to: lastDay.toISOString().slice(0, 10)
                    }).then(response => {
                        this.events = response.data
                    })
                },
                immediate: true
            }
        }
    }
</script>
