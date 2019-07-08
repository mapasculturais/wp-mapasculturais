import axios from 'axios'

export const EventOccurrences = {
    find (params) {
        return axios.get('/mcapi/eventOccurrence/', { params })
    }
}

const mcapi = {
    EventOccurrences
}

export default mcapi

export const MCApiPlugin = {
    install (Vue, options) {
        Vue.prototype.$mc = mcapi
    }
}