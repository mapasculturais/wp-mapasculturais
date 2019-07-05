<template>
    <div class="mc-w__header">
        <button v-if="showArrows" class="previous" :disabled="!previous" @click="$emit('previous')" aria-label="Mês anterior">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
        <strong><slot/></strong>
        <button v-if="showArrows" class="next" :disabled="!next" @click="$emit('next')" title="Próximo mês">
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
        </button>
    </div>
</template>

<script>
    export default {
        name: 'WidgetHeader',
        props: {
            next: { type: Boolean, default: true },
            previous: { type: Boolean, default: true },
            showArrows: { type: Boolean, default: true },
        }
    }
</script>
