<template>
    <div class="mc-w__header">
        <a v-if="previous" :href="previous" class="previous"><i class="fas fa-chevron-left"></i></a>
        <strong><slot/></strong>
        <a v-if="next" :href="next" class="next"><i class="fas fa-chevron-right"></i></a>
    </div>
</template>

<script>
    export default {
        name: 'WidgetHeader',
        props: {
            previous: { type: String, default: null },
            next: { type: String, default: null },
        }
    }
</script>
