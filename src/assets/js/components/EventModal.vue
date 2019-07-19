<template>
    <CardModal :name="event.name" :category="event.terms.linguagem[0]" :background="backgroundImage" :link="event.permalink" @close="$emit('close')">
        <template #toolbars>
            <div class="toolbar">
                <a role="button" tabindex="0" @click="attendEvent"><i class="fas fa-check"></i></a>
                <a role="button" tabindex="0" @click="favoriteEvent"><i class="fas fa-star"></i></a>
            </div>
        </template>
        <template #content>
            <div class="mc-w__card-slot">
                <div class="icon"></div>
                <div class="text">{{ event.shortDescription }}</div>
            </div>
            <div class="mc-w__card-slot">
                <div class="icon" aria-label="Data e horário">
                    <i class="far fa-calendar-alt" aria-hidden="true"></i>
                </div>
                <div class="text">{{ datetime }}</div>
            </div>
            <div class="mc-w__card-slot">
                <div class="icon" aria-label="Endereço">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                </div>
                <div class="text address">
                    <div class="name">{{ event.space.name }}</div>
                    <div class="location">{{ address }}</div>
                </div>
            </div>
            <div class="mc-w__card-slot">
                <div class="icon" aria-label="Classificação etária">
                    <i class="fas fa-child" aria-hidden="true"></i>
                </div>
                <div class="text">{{ event.classificacaoEtaria }}</div>
            </div>
        </template>
    </CardModal>
</template>

<script>
    import CardModal from "./CardModal.vue"

    export default {
        name: 'EventModal',
        components: {
            CardModal
        },
        props: {
            event: { type: Object, default: null }
        },
        computed: {
            address () {
                const space = this.event.space
                return [
                    [space.En_Nome_Logradouro, space.En_Num, space.En_Complemento, space.En_Bairro, space.En_CEP].filter(Boolean).join(', '),
                    [space.En_Municipio, space.En_Estado].filter(Boolean).join(', ')
                ].filter(Boolean).join(' - ')
            },
            backgroundImage () {
                return typeof this.event.avatar.medium === 'string' ? `url('${this.event.avatar.medium}')` : ''
            },
            datetime () {
                const startDate = new Date(this.event.occurrence.starts).toLocaleDateString('pt-BR')
                const endDate = new Date(this.event.occurrence.ends).toLocaleDateString('pt-BR')
                return `${ startDate } ${ this.event.occurrence.starts_on.slice(5) } -
                ${ endDate !== startDate ? endDate : '' } ${ this.event.occurrence.ends_on.slice(5) }`
            }
        },
        methods: {
            deleteEventAttendance () {
                console.log
                this.$mc.EventAttendance.delete(this.event.occurrence.attendence).then(() => {
                    this.event.occurrence.attendence = null
                });
            }, 
            attendEvent () {
                var event = this.event
                if(!event.occurrence.attendence || event.occurrence.attendence.type != 'confirmation'){
                    this.$mc.EventAttendance.confirm(this.event.occurrence.reccurrence_string).then((event_attendance) => {
                        event.occurrence.attendence = event_attendance.data
                    });
                }else {
                    this.deleteEventAttendance()
                }
            },
            favoriteEvent () {
                var event = this.event
                if(!event.occurrence.attendence || event.occurrence.attendence.type != 'interested'){
                    this.$mc.EventAttendance.interested(this.event.occurrence.reccurrence_string).then((event_attendance) => {
                        event.occurrence.attendence = event_attendance.data
                    });
                } else {
                    this.deleteEventAttendance()
                }
            }
        }
    }
</script>
