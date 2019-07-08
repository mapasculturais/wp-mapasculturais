import Vue from 'vue'

import { MCApiPlugin } from './mcapi'

import WidgetAgenda from './components/MyAgenda.vue'
import WidgetCalendar from './components/EventsCalendar.vue'
import WidgetDay from './components/DailyEvents.vue'
import WidgetList from './components/EventsList.vue'
import WidgetNow from './components/HappeningNow.vue'

Vue.use(MCApiPlugin)

Vue.component('mc-w-agenda', WidgetAgenda)
Vue.component('mc-w-calendar', WidgetCalendar)
Vue.component('mc-w-day', WidgetDay)
Vue.component('mc-w-list', WidgetList)
Vue.component('mc-w-now', WidgetNow)

new Vue({
    el: '#content'
})
