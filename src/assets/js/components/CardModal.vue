<template>
    <div class="mc-w__card-wrapper" @click="$emit('close')">
        <article class="mc-w__card" @click.stop>
            <footer class="mc-w__card-actions">
                <slot name="toolbars"/>
                <div class="toolbar">
                    <a role="button" tabindex="0" @click="$emit('close')"><i class="fas fa-times"></i></a>
                </div>
            </footer>
            <header class="mc-w__card-header" :style="{ 'background-image': background }">
                <div class="mc-w__card-slot">
                    <div class="icon"><i class="fas fa-circle"></i></div>
                    <div class="text">
                        <div class="name" v-html="name"/>
                        <div class="category">{{ category }}</div>
                    </div>
                </div>
            </header>
            <main class="mc-w__card-content">
                <slot name="content"/>
                <a :href="link" class="more-info">Mais infos</a>
            </main>
        </article>
    </div>
</template>

<script>
    export default {
        name: 'CardModal',
        props: {
            background: { type: String, default: '' },
            name: { type: String, required: true },
            category: { type: String, required: true },
            link: { type: String, required: true }
        }
    }
</script>
