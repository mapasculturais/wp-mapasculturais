import axios from 'axios'

export const EventOccurrences = {
    find (params) {
        return axios.get('/mcapi/eventOccurrence/', { params })
    }
}

export const EventRules = {
    create (params) {
        return axios.post('/mcapi/createEventRule/', { params })
    },
    delete (id, params) {
        return axios.post(`/mcapi/deleteEventRule/${id}/`, { params })
    },
    get (id, params) {
        return axios.get(`/mcapi/eventRules/${id}/`, { params })
    },
    update (id, params) {
        return axios.post(`/mcapi/updateEventRules/${id}/`, params)
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
    EventRules,
    Spaces,
    Taxonomies
}

export default mcapi

export const MCApiPlugin = {
    install (Vue, options) {
        Vue.prototype.$mc = mcapi
    }
}