<template>
    <section class="mc-w mc-w-now">
        <WidgetHeader>Agora</WidgetHeader>
        <div class="mc-w-now__content">
            <div class="mc-w-now__events">
                <EventRow class="mc-w-now__event"
                    check time="12:00 - 23:30" category="Artes circenses" rating="Livre"
                    name="Nome de um primeiro evento com nome grande"
                    space="Nome do espaço"/>
                <EventRow class="mc-w-now__event"
                    time="14:00 - 16:30" category="Teatro" rating="12 anos"
                    name="Nome de um segundo evento"
                    space="Nome do espaço"/>
                <EventRow class="mc-w-now__event"
                    time="14:30 - 18:30" category="Show" rating="12 anos"
                    name="Outro nome de um segundo evento"
                    space="Nome do espaço"/>
                <EventRow class="mc-w-now__event"
                    star time="15:00 - 16:30" category="Cinema" rating="Livre"
                    name="ome de um primeiro evento"
                    space="Nome do espaço"/>
                <EventRow class="mc-w-now__event"
                    time="15:30 - 18:30" category="Show" rating="Livre"
                    name="Nome de um primeiro evento com outro nome"
                    space="Nome do espaço"/>
                <EventRow class="mc-w-now__event"
                    time="15:30 - 18:30" category="Teatro" rating="18 anos"
                    name="Nome de um primeiro evento"
                    space="Nome do espaço"/>
                <EventRow class="mc-w-now__event"
                    time="15:40 - 18:30" category="Ao ar livre" rating="Livre"
                    name="Nome de um primeiro evento"
                    space="Nome do espaço"/>
            </div>
        </div>
    </section>
</template>

<script>
    import EventRow from './EventRow.vue'
    import WidgetHeader from './WidgetHeader.vue'

    export default {
        name: 'HappeningNow',
        components: {
            EventRow,
            WidgetHeader
        }
    }
</script>
