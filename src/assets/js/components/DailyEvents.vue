<template>
    <section class="mc-w mc-w-day">
        <WidgetHeader previous="#" next="#">1 de Maio de 2019</WidgetHeader>
        <div class="mc-w-day__content">
            <div class="mc-w-day__period">
                <div class="mc-w-day__label">Manhã</div>
                <div class="mc-w-day__events">
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">00:00 - 02:30</div>
                        <div class="category"><i>Artes circenses</i> | Livre</div>
                        <div class="description">
                            <i class="fas fa-check"></i>
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">02:00 - 06:30</div>
                        <div class="category"><i>Cultura tradicional</i> | Livre</div>
                        <div class="description">
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">06:00 - 08:30</div>
                        <div class="category"><i>Cultura digital</i> | Livre</div>
                        <div class="description">
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">08:00 - 09:00</div>
                        <div class="category"><i>Curso ou oficina</i> | 12 anos</div>
                        <div class="description">
                            <i class="fas fa-star"></i>
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">10:00 - 16:30</div>
                        <div class="category"><i>Dança</i> | Livre</div>
                        <div class="description">
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mc-w-day__period">
                <div class="mc-w-day__label">Tarde</div>
                <div class="mc-w-day__events">
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">12:00 - 13:30</div>
                        <div class="category"><i>Cinema</i> | Livre</div>
                        <div class="description">
                            <div class="name">Nome de um primeiro evento</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">13:00 - 14:30</div>
                        <div class="category"><i>Música erudita</i> | 18 anos</div>
                        <div class="description">
                            <div class="name">Nome de um evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">15:00 - 22:30</div>
                        <div class="category"><i>Artes visuais</i> | 18 anos</div>
                        <div class="description">
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">16:00 - 17:30</div>
                        <div class="category"><i>Cultura indígena</i> | Livre</div>
                        <div class="description">
                            <div class="name">Nome de um primeiro evento</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mc-w-day__period">
                <div class="mc-w-day__label">Noite</div>
                <div class="mc-w-day__events">
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">18:00 - 22:30</div>
                        <div class="category"><i>Livro e  literatura</i> | Livre</div>
                        <div class="description">
                            <i class="fas fa-check"></i>
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">19:00 - 21:30</div>
                        <div class="category"><i>Teatro</i> | Livre</div>
                        <div class="description">
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">20:00 - 20:30</div>
                        <div class="category"><i>Hip hop</i> | Livre</div>
                        <div class="description">
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">21:00 - 22:30</div>
                        <div class="category"><i>Rádio</i> | 12 anos</div>
                        <div class="description">
                            <i class="fas fa-star"></i>
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                    <div class="mc-w__event mc-w-day__event">
                        <div class="time">21:00 - 23:30</div>
                        <div class="category"><i>Outros</i> | Livre</div>
                        <div class="description">
                            <div class="name">Nome de um primeiro evento com nome grande</div>
                            <div class="place">Nome do espaço</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
    import WidgetHeader from './WidgetHeader.vue'

    export default {
        name: 'DailyEvents',
        components: {
            WidgetHeader
        }
    }
</script>
