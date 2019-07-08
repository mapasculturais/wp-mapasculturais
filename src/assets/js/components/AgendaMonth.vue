<template>
    <div class="mc-w-agenda__month" :class="maximized ? 'maximized' : 'minimized'">
        <div class="mc-w-agenda__label">{{ monthString }}</div>
        <button class="mc-w-agenda__collapse" @click="maximized = !maximized"><i class="fas" :class="maximized ? 'fa-arrow-up' : 'fa-arrow-down'"></i></button>
        <div class="mc-w-agenda__content" v-show="maximized">
            <div class="mc-w-agenda__day" v-for="(eventsOnDay, day) in eventsByDay" :key="day">
                <div class="mc-w-agenda__date">
                    <div class="day">{{ new Date(eventsOnDay[0].occurrence.starts).getDate() }}</div>
                    <div class="weekday">{{ weekdayString(eventsOnDay[0].occurrence.starts) }}</div>
                </div>
                <div class="mc-w-agenda__events">
                    <EventRow class="mc-w-agenda__event" v-for="event in eventsOnDay" :key="event.id" :event="event" :showTime="false"/>
                </div>
            </div>
            <div class="mc-w-agenda__no-content" v-if="events.length === 0">
                Você ainda não possui eventos agendados nesse mês
            </div>
        </div>
    </div>
</template>

<script>
    import EventRow from './EventRow.vue'
    import WidgetMixin from './mixins/WidgetMixin'

    export default {
        name: 'AgendaMonth',
        components: {
            EventRow
        },
        mixins: [WidgetMixin],
        props: {
            events: { type: Array, required: true }
        },
        data () {
            return {
                maximized: true
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
    }
</script>
