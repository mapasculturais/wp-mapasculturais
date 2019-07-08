<template>
    <div class="mc-w__event">
        <div class="time" v-if="showTime">
            {{ event.occurrence.starts_at.slice(0, 5) }} - {{ event.occurrence.ends_at.slice(0, 5) }}
        </div>
        <div class="category">
            <i>{{ event.terms.linguagem[0] }}</i> | {{ event.classificacaoEtaria }}
        </div>
        <div class="description">
            <!--
            <i v-if="star" class="fas fa-star"></i>
            <i v-else-if="check" class="fas fa-check"></i>
            -->
            <a class="name" role="button" @click="$emit('selectEvent', event)">{{ event.name }}</a>
            <a class="space" role="button" @click="$emit('selectSpace', event.space)">{{ event.space.name }}</a>
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
