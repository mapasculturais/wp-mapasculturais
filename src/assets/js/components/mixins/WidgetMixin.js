import FiltersBar from '../FiltersBar.vue'
import WidgetHeader from '../WidgetHeader.vue'

export default {
    components: {
        FiltersBar,
        WidgetHeader
    },
    props: {
        showFilters: { type: Boolean, default: true }
    },
    data () {
        return {
            filters: {}
        }
    },
    methods: {
        updateFilters (filters) {
            this.filters = filters
        }
    }
}