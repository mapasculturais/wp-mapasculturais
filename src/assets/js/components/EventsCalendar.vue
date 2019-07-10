<template>
    <div class="mc-w mc-w-calendar">
        <FiltersBar v-if="showFilters" @change="updateFilters"/>
        <WidgetHeader>Maio de 2019</WidgetHeader>
        <p>TODO</p>
    </div>
</template>

<script>
    import WidgetMixin from './mixins/WidgetMixin'

    export default {
        name: 'EventsCalendar',
        mixins: [
            WidgetMixin
        ]
    }
</script>
