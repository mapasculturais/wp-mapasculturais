import axios from 'axios'

export const EventOccurrences = {
    find (params) {
        return axios.get('/mcapi/eventOccurrence/', { params })
    }
}

export const Taxonomies = window.mcTaxonomies

const mcapi = {
    EventOccurrences,
    Taxonomies
}

export default mcapi

export const MCApiPlugin = {
    install (Vue, options) {
        Vue.prototype.$mc = mcapi
    }
}