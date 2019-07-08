<template>
    <CardModal :name="space.name" :category="space.terms.area[0]" :link="space.permalink" @close="$emit('close')">
        <template #content>
            <div class="mc-w__card-slot">
                <div class="icon"></div>
                <div class="text">{{ space.shortDescription }}</div>
            </div>
            <div class="mc-w__card-slot">
                <div class="icon" aria-label="Endereço">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                </div>
                <div class="text address">
                    <div class="name">{{ space.En_Municipio }} - {{ space.En_Estado }}</div>
                    <div class="location">{{ address }}</div>
                </div>
            </div>
        </template>
    </CardModal>
</template>

<script>
    import CardModal from "./CardModal.vue"

    export default {
        name: 'SpaceModal',
        components: {
            CardModal
        },
        props: {
            space: { type: Object, default: null }
        },
        computed: {
            address () {
                const space = this.space
                return [
                    [space.En_Nome_Logradouro, space.En_Numero, space.En_Complemento, space.En_Bairro, space.En_CEP].filter(Boolean).join(', '),
                    [space.En_Municipio, space.En_Estado].filter(Boolean).join(', ')
                ].filter(Boolean).join(' - ')
            },
        }
    }
</script>

