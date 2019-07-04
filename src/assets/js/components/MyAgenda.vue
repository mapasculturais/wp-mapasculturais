<template>
    <section class="mc-w mc-w-agenda">
        <WidgetHeader>Minha agenda</WidgetHeader>
        <div class="mc-w-agenda__month maximized">
            <div class="mc-w-agenda__label">Maio</div>
            <button class="mc-w-agenda__collapse"><i class="fas fa-arrow-up"></i></button>
            <div class="mc-w-agenda__content">
                <div class="mc-w-agenda__day">
                    <div class="mc-w-agenda__date">
                        <div class="day">3</div>
                        <div class="weekday">Quinta</div>
                    </div>
                    <div class="mc-w-agenda__events">
                        <EventRow class="mc-w-agenda__event"
                            check time="00:00 - 02:30" category="Cinema" rating="Livre"
                            name="Nome de um primeiro com nome grande"
                            space="Nome do espaço"/>
                        <EventRow class="mc-w-agenda__event"
                            star time="02:00 - 06:30" category="Música clássica" rating="Livre"
                            name="Nome de um primeiro evento"
                            space="Nome do espaço"/>
                    </div>
                </div>
                <div class="mc-w-agenda__day">
                    <div class="mc-w-agenda__date">
                        <div class="day">8</div>
                        <div class="weekday">Terça</div>
                    </div>
                    <div class="mc-w-agenda__events">
                        <EventRow class="mc-w-agenda__event"
                            check time="00:00 - 02:30" category="Artes visuais" rating="Livre"
                            name="Nome de um primeiro evento com nome"
                            space="Nome do espaço"/>
                        <EventRow class="mc-w-agenda__event"
                            star time="02:00 - 06:30" category="Artes circenses" rating="12 anos"
                            name="Nome de um primeiro com nome grande"
                            space="Nome do espaço"/>
                        <EventRow class="mc-w-agenda__event"
                            star time="02:00 - 06:30" category="Cultura digital" rating="Livre"
                            name="Nome de um primeiro evento"
                            space="Nome do espaço"/>
                        <EventRow class="mc-w-agenda__event"
                            star time="02:00 - 06:30" category="Audiovisual" rating="Livre"
                            name="Nome de um primeiro evento com nome grande"
                            space="Nome do espaço"/>
                    </div>
                </div>
                <div class="mc-w-agenda__day">
                    <div class="mc-w-agenda__date">
                        <div class="day">12</div>
                        <div class="weekday">Sábado</div>
                    </div>
                    <div class="mc-w-agenda__events">
                        <EventRow class="mc-w-agenda__event"
                            check time="00:00 - 02:30" category="Artes integradas" rating="18 anos"
                            name="Nome de um primeiro com nome grande"
                            space="Nome do espaço"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="mc-w-agenda__month maximized">
            <div class="mc-w-agenda__label">Junho</div>
            <button class="mc-w-agenda__collapse"><i class="fas fa-arrow-up"></i></button>
            <div class="mc-w-agenda__content">
                <div class="mc-w-agenda__day">
                    <div class="mc-w-agenda__date">
                        <div class="day">15</div>
                        <div class="weekday">Sábado</div>
                    </div>
                    <div class="mc-w-agenda__events">
                        <EventRow class="mc-w-agenda__event"
                            check time="00:00 - 02:30" category="Livro e literatura" rating="Livre"
                            name="Nome de um primeiro evento nome grande"
                            space="Nome do espaço"/>
                        <EventRow class="mc-w-agenda__event"
                            star time="02:00 - 06:30" category="Música popular" rating="Livre"
                            name="Nome de um primeiro evento nome grande"
                            space="Nome do espaço"/>
                    </div>
                </div>
                <div class="mc-w-agenda__day">
                    <div class="mc-w-agenda__date">
                        <div class="day">18</div>
                        <div class="weekday">Terça</div>
                    </div>
                    <div class="mc-w-agenda__events">
                        <EventRow class="mc-w-agenda__event"
                            check time="00:00 - 02:30" category="Exposição" rating="Livre"
                            name="Nome de um primeiro evento com nome grande"
                            space="Nome do espaço"/>
                        <EventRow class="mc-w-agenda__event"
                            star time="02:00 - 06:30" category="Cultura indígena" rating="12 anos"
                            name="Nome de um primeiro evento"
                            space="Nome do espaço"/>
                        <EventRow class="mc-w-agenda__event"
                            star time="02:00 - 06:30" category="Rádio" rating="Livre"
                            name="Nome de um primeiro evento com nome"
                            space="Nome do espaço"/>
                        <EventRow class="mc-w-agenda__event"
                            star time="02:00 - 06:30" category="Teatro" rating="Livre"
                            name="Nome de um primeiro evento com nome grande"
                            space="Nome do espaço"/>
                    </div>
                </div>
                <div class="mc-w-agenda__day">
                    <div class="mc-w-agenda__date">
                        <div class="day">26</div>
                        <div class="weekday">Quarta</div>
                    </div>
                    <div class="mc-w-agenda__events">
                        <EventRow class="mc-w-agenda__event"
                            check time="00:00 - 02:30" category="Outros" rating="18 anos"
                            name="Nome de um primeiro evento"
                            space="Nome do espaço"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="mc-w-agenda__month minimized">
            <div class="mc-w-agenda__label">Julho</div>
            <button class="mc-w-agenda__collapse"><i class="fas fa-arrow-down"></i></button>
            <div class="mc-w-agenda__content"></div>
        </div>
        <div class="mc-w-agenda__month maximized">
            <div class="mc-w-agenda__label">Agosto</div>
            <div class="mc-w-agenda__collapse"><i class="fas fa-arrow-up"></i></div>
            <div class="mc-w-agenda__content">
                <div class="mc-w-agenda__no-content">
                    Você ainda não possui eventos agendados neste mês
                </div>
            </div>
        </div>
    </section>
</template>

<script>
    import EventRow from './EventRow.vue'
    import WidgetHeader from './WidgetHeader.vue'

    export default {
        name: 'MyAgenda',
        components: {
            EventRow,
            WidgetHeader
        }
    }
</script>