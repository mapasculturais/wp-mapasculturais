<template>
    <div class="mc-w__event">
        <div class="time" v-if="time">{{ time }}</div>
        <div class="category"><i>{{ category }}</i> | {{ rating }}</div>
        <div class="description">
            <i v-if="star" class="fas fa-star"></i>
            <i v-else-if="check" class="fas fa-check"></i>
            <div class="name">{{ name }}</div>
            <div class="space">{{ space }}</div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'EventRow',
        props: {
            check: { type: Boolean, default: false },
            category: { type: String, required: true },
            name: { type: String, required: true },
            rating: { type: String, required: true },
            space: { type: String, required: true },
            star: { type: Boolean, default: false },
            time: { type: String, default: '' },
        }
    }
</script>
