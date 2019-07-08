import EventModal from '../EventModal.vue'
import SpaceModal from '../SpaceModal.vue'

export default {
    components: {
        EventModal,
        SpaceModal
    },
    data () {
        return {
            modalEvent: null,
            modalSpace: null,
        }
    },
    methods: {
        closeEventModal () {
            this.modalEvent = null
        },
        closeSpaceModal () {
            this.modalSpace = null
        },
        openEventModal (event) {
            this.modalEvent = event
        },
        openSpaceModal (space) {
            this.modalSpace = space
        }
    }
}