import Vue from 'vue'

import { MCApiPlugin } from './mcapi'

import OccurrenceMetabox from './components/OccurrenceMetabox.vue'

Vue.use(MCApiPlugin)

Vue.component('mc-occurrence-cmb', OccurrenceMetabox)

new Vue({
    el: '#wpbody'
})
