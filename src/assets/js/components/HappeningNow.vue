<template>
    <section class="mc-w mc-w-now">
        <WidgetHeader>Agora</WidgetHeader>
        <div class="mc-w-now__content">
            <div class="mc-w-now__events">
                <div class="mc-w__event mc-w-now__event">
                    <div class="time">12:00 - 23:30</div>
                    <div class="category"><i>Artes circenses</i> | Livre</div>
                    <div class="description">
                        <i class="fas fa-check"></i>
                        <div class="name">Nome de um primeiro evento com nome grande</div>
                        <div class="place">Nome do espaço</div>
                    </div>
                </div>
                <div class="mc-w__event mc-w-now__event">
                    <div class="time">14:00 - 16:30</div>
                    <div class="category"><i>Teatro</i> | 12 anos</div>
                    <div class="description">
                        <div class="name">Nome de um segundo evento</div>
                        <div class="place">Nome do espaço</div>
                    </div>
                </div>
                <div class="mc-w__event mc-w-now__event">
                    <div class="time">14:30 - 18:30</div>
                    <div class="category"><i>Show</i> | 12 anos</div>
                    <div class="description">
                        <div class="name">Outro nome de um segundo evento</div>
                        <div class="place">Nome do espaço</div>
                    </div>
                </div>
                <div class="mc-w__event mc-w-now__event">
                    <div class="time">15:00 - 16:30</div>
                    <div class="category"><i>Cinema</i> | Livre</div>
                    <div class="description">
                        <i class="fas fa-star"></i>
                        <div class="name">Nome de um primeiro evento</div>
                        <div class="place">Nome do espaço</div>
                    </div>
                </div>
                <div class="mc-w__event mc-w-now__event">
                    <div class="time">15:30 - 18:30</div>
                    <div class="category"><i>Show</i> | Livre</div>
                    <div class="description">
                        <div class="name">Nome de um primeiro evento com outro nome</div>
                        <div class="place">Nome do espaço</div>
                    </div>
                </div>
                <div class="mc-w__event mc-w-now__event">
                    <div class="time">15:30 - 18:30</div>
                    <div class="category"><i>Teatro</i> | 18 anos</div>
                    <div class="description">
                        <div class="name">Nome de um primeiro evento</div>
                        <div class="place">Nome do espaço</div>
                    </div>
                </div>
                <div class="mc-w__event mc-w-now__event">
                    <div class="time">15:40 - 18:30</div>
                    <div class="category"><i>Ao ar livre</i> | Livre</div>
                    <div class="description">
                        <div class="name">Nome de um primeiro evento</div>
                        <div class="place">Nome do espaço</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
    import WidgetHeader from './WidgetHeader.vue'

    export default {
        name: 'HappeningNow',
        components: {
            WidgetHeader
        }
    }
</script>
