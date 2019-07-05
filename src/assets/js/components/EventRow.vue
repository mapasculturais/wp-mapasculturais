<template>
    <div class="mc-w__event">
        <div class="time" v-if="showTime">
            {{ event.occurrence.starts_at }} - {{ event.occurrence.ends_at }}
        </div>
        <div class="category">
            <i>{{ event.terms.linguagem[0] }}</i> | {{ event.classificacaoEtaria }}
        </div>
        <div class="description">
            <!--
            <i v-if="star" class="fas fa-star"></i>
            <i v-else-if="check" class="fas fa-check"></i>
            -->
            <a class="name" :href="event.permalink">{{ event.name }}</a>
            <a class="space" :href="event.space.permalink">{{ event.space.name }}</a>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'EventRow',
        props: {
            event: { type: Object, required: true },
            showTime: { type: Boolean, default: true }
        }
    }
</script>
