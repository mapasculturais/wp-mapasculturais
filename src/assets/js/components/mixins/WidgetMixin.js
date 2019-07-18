import FiltersBar from '../FiltersBar.vue'
import WidgetHeader from '../WidgetHeader.vue'

export default {
    components: {
        FiltersBar,
        WidgetHeader
    },
    props: {
        agents: { type: String, default: undefined },
        showFilters: { type: Boolean, default: true },
        spaces: { type: String, default: undefined },
    },
    data () {
        return {
            filters: {}
        }
    },
    computed: {
        propsParams () {
            return {
                'owner': this.$props.agents && `IN(${this.$props.agents})`,
                'space:id': this.$props.spaces && `IN(${this.$props.spaces})`,
            }
        }
    },
    methods: {
        updateFilters (filters) {
            this.filters = filters
        }
    }
}