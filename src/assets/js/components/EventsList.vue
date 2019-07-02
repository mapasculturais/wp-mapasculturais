<template>
    <section class="mc-w mc-w-list">
        <WidgetHeader previous="#" next="#">Maio de 2019</WidgetHeader>
        <div class="mc-w-list__content">
            <div class="mc-w-list__day">
                <div class="mc-w-list__date">
                    <div class="day">3</div>
                    <div class="weekday">Quinta</div>
                </div>
                <div class="mc-w-list__events">
                    <EventRow class="mc-w-list__event"
                        category="Artes circenses" rating="Livre"
                        name="Nome de um primeiro evento com nome grande"
                        space="Condomínio Cultural"/>
                    <EventRow class="mc-w-list__event"
                        category="Música erudita" rating="12 anos"
                        name="Nome de um segundo evento"
                        space="Museu das Bandeiras"/>
                    <EventRow class="mc-w-list__event"
                        category="Cinema" rating="12 anos"
                        name="Outro nome de um segundo evento"
                        space="Biblioteca Monteiro Lobato"/>
                </div>
            </div>
            <div class="mc-w-list__day">
                <div class="mc-w-list__date">
                    <div class="day">4</div>
                    <div class="weekday">Sexta</div>
                </div>
                <div class="mc-w-list__events">
                    <EventRow class="mc-w-list__event"
                        category="Artes visuais" rating="Livre"
                        name="Nome de um primeiro evento"
                        space="Biblioteca Itinerante de Quadrinhos"/>
                    <EventRow class="mc-w-list__event"
                        star category="Arte indígena" rating="Livre"
                        name="Nome de um primeiro evento com outro nome"
                        space="Grupo Livre Capoeira Regional"/>
                </div>
            </div>
            <div class="mc-w-list__day">
                <div class="mc-w-list__date">
                    <div class="day">7</div>
                    <div class="weekday">Segunda</div>
                </div>
                <div class="mc-w-list__events">
                    <EventRow class="mc-w-list__event"
                        check category="Cinema" rating="18 anos"
                        name="Nome de um primeiro evento"
                        space="Nome do espaço"/>
                </div>
            </div>
            <div class="mc-w-list__day">
                <div class="mc-w-list__date">
                    <div class="day">12</div>
                    <div class="weekday">Sábado</div>
                </div>
                <div class="mc-w-list__events">
                    <EventRow class="mc-w-list__event"
                        category="Hip hop" rating="Livre"
                        name="Nome de um primeiro evento"
                        space="Livraria Dom Bosco"/>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
    import EventRow from './EventRow.vue'
    import WidgetHeader from './WidgetHeader.vue'

    export default {
        name: 'EventsList',
        components: {
            EventRow,
            WidgetHeader
        }
    }
</script>
