import axios from 'axios'

export const EventOccurrences = {
    create (params) {
        return axios.post('/mcapi/eventOccurrence/create', { params })
    },
    find (params) {
        return axios.get('/mcapi/eventOccurrence/', { params })
    }
}

export const Spaces = {
    find (params) {
        return axios.get('/mcapi/space/', { params })
    }
}

export const Taxonomies = window.mcTaxonomies

const mcapi = {
    EventOccurrences,
    Spaces,
    Taxonomies
}

export default mcapi

export const MCApiPlugin = {
    install (Vue, options) {
        Vue.prototype.$mc = mcapi
    }
}