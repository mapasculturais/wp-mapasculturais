<template>
    <div class="mc-cmb-occurrences">
        <p v-if="event <= 0">
            Salve o evento primeiro.
        </p>
        <template v-else>
            <ul class="mc-cmb-occurrences__list">
                <li v-for="(oc, index) in occurrences" :key="oc.id">
                    <span>{{ oc.rule.description }}</span>
                    <a class="button" role="button" @click="occurrence = oc">Editar</a>
                    <a class="button" role="button" @click="removeOccurrence(oc, index)">Remover</a>
                </li>
                <li>
                    <span>Mais ocorrências?</span>
                    <a class="button" role="button" @click="occurrence = false">Criar nova</a>
                </li>
            </ul>
            <div class="mc-cmb-occurrences__form-wrapper">
                <h3 v-if="occurrence === false">Criando nova ocorrência</h3>
                <h3 v-else>Editando ocorrência {{ occurrence.id }}</h3>
                <OccurrenceForm :key="occurrence ? occurrence.id : -1" :occurrences="occurrences" :occurrenceId="occurrence.id" :event="event" :occurrence="occurrence && occurrence.rule"/>
            </div>
        </template>
    </div>
</template>

<script>
    import OccurrenceForm from './OccurrenceForm.vue'

    export default {
        name: 'OccurrenceMetabox',
        components: {
            OccurrenceForm
        },
        props: {
            event: { type: Number, required: true },
            post: { type: Number, required: true }
        },
        data () {
            return {
                occurrence: false,
                occurrences: [],
            }
        },
        created () {
            if (this.$props.event <= 0) {
                return;
            }
            this.$mc.EventRules.get(this.$props.event).then(response => {
                this.occurrences = response.data
            })
        },
        methods: {
            removeOccurrence (occurrence, index) {
                if(confirm('Deletar a ocorrência "' + occurrence.rule.description + '"?')){
                    this.$mc.EventRules.delete(occurrence.id).then(() => {
                        this.occurrences.splice(index, 1)
                    })
                }
            }
        }
    }
</script>
