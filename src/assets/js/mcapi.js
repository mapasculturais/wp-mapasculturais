import axios from 'axios'
import qs from 'qs';

export const EventAttendance = {
    create (recurrence_string, type) {
        return getProcurationToken().then(token => {                    
            return axios.post('/mcapi/eventAttendance/create', qs.stringify({
                token: token,
                reccurrenceString: recurrence_string,
                type: type
            }))
        })
    },
    confirm (recurrence_string) {
        return this.create(recurrence_string, 'confirmation')
    },
    interested (recurrence_string) {
        return this.create(recurrence_string, 'interested')
    },
    delete (event_attendance) {
        var params = qs.stringify({
            event_attendance_id: event_attendance.id
        });
        console.log(event_attendance, {
            event_attendance_id: event_attendance.id
        });
        return axios.post('/mcapi/eventAttendance/delete', params)
    }
}

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
    EventAttendance,
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