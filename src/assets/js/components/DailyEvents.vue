<template>
    <section class="mc-w mc-w-day">
        <WidgetHeader previous="#" next="#">1 de Maio de 2019</WidgetHeader>
        <div class="mc-w-day__content">
            <div class="mc-w-day__period">
                <div class="mc-w-day__label">Manhã</div>
                <div class="mc-w-day__events">
                    <EventRow class="mc-w-day__event"
                        check time="00:00 - 02:30" category="Artes circenses" rating="Livre"
                        name="Nome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        time="02:00 - 06:30" category="Cultura tradicional" rating="Livre"
                        name="Nome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        time="06:00 - 08:30" category="Arte digital" rating="Livre"
                        name="Nome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        star time="08:00 - 09:00" category="Curso ou oficina" rating="12 anos"
                        name="Nome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        time="10:00 - 16:30" category="Dança" rating="Livre"
                        name="Nome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                </div>
            </div>
            <div class="mc-w-day__period">
                <div class="mc-w-day__label">Tarde</div>
                <div class="mc-w-day__events">
                    <EventRow class="mc-w-day__event"
                        time="12:00 - 13:30" category="Cinema" rating="Livre"
                        name="Nome de um primeiro evento"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        time="13:00 - 14:30" category="Música erudita" rating="18 anos"
                        name="Nome de um evento com nome grande"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        time="15:00 - 22:30" category="Artes visuais" rating="18 anos"
                        name="Nome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        time="16:00 - 17:30" category="Cultura indígena" rating="Livre"
                        name="Nome de um primeiro evento"
                        space="Nome do espaço"/>
                </div>
            </div>
            <div class="mc-w-day__period">
                <div class="mc-w-day__label">Noite</div>
                <div class="mc-w-day__events">
                    <EventRow class="mc-w-day__event"
                        check time="18:00 - 22:30" category="Livro e  literatura" rating="Livre"
                        name="Nome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        time="19:00 - 21:30" category="Teatro" rating="Livre"
                        name="Nome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        time="20:00 - 20:30" category="Hip hop" rating="Livre"
                        name="ome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        star time="21:00 - 22:30" category="Rádio" rating="12 anos"
                        name="Nome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                    <EventRow class="mc-w-day__event"
                        time="21:00 - 23:30" category="Outros" rating="Livre"
                        name="Nome de um primeiro evento com nome grande"
                        space="Nome do espaço"/>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
    import EventRow from './EventRow.vue'
    import WidgetHeader from './WidgetHeader.vue'

    export default {
        name: 'DailyEvents',
        components: {
            EventRow,
            WidgetHeader
        }
    }
</script>
