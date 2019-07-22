<template>
    <form class="mc-cmb-occurrences__form">
        <div class="fields-row">
            <label>
                <span>Espaço</span>
                <select name="spaceId" v-model="spaceId">
                    <option v-for="space in spaces" :key="space.id" :value="space.id">{{ space.name }}</option>
                </select>
            </label>
        </div>
        <div class="fields-row">
            <label>
                <span>Horário inicial</span>
                <input type="time" name="startsAt" v-model="startsAt" placeholder="00:00">
            </label>
            <label>
                <span>Duração</span>
                <input type="number" name="duration" v-model="duration" placeholder="minutos">
            </label>
            <label>
                <span>Horário final</span>
                <input type="time" name="endsAt" :value="endsAt" placeholder="00:00">
            </label>
            <label>
                <span>Frequência</span>
                <select name="frequency" v-model="frequency">
                    <option value="once">uma vez</option>
                    <option value="daily">todos os dias</option>
                    <option value="weekly">semanal</option>
                </select>
            </label>
        </div>
        <div class="fields-row">
            <label>
                <span>Data inicial</span>
                <input type="date" v-model="startsOn" placeholder="00/00/0000">
            </label>
            <label v-if="frequency !== 'once'">
                <span>Data final</span>
                <input type="date" v-model="until" placeholder="00/00/0000">
            </label>
            <label v-if="frequency === 'weekly'">
                <span>Repete</span>
                <div>
                    <label v-for="date in $options.dates" :key="date">
                        <span style="margin-right:1em;">
                            <input type="checkbox" v-model="dates__" :value="date">
                            {{ date.slice(0, 3).toUpperCase() }}
                        </span>
                    </label>
                </div>
            </label>
        </div>
        <div class="fields-row" v-if="defaultDescription !== '...'">
            <label>
                <span>{{ defaultDescription }}</span>
            </label>
            <a class="button" role="button" tabindex="0" @click="description = defaultDescription">Copiar abaixo</a>
        </div>
        <div class="fields-row">
            <label>
                <span>Descrição legível do horário</span>
                <input type="text" name="duration" v-model="description">
            </label>
        </div>
        <div class="fields-row">
            <label>
                <a class="button button-primary" role="button" tabindex="0" @click="save()">Atualizar</a>
            </label>
        </div>
    </form>
</template>

<script>
    import { addMinutes, format as formatDate, parse as parseDate } from 'date-fns'
    import locale from 'date-fns/locale/pt-BR'

    export default {
        name: 'OccurrenceForm',
        props: {
            occurrenceId: { type: Number, required: false },
            event: { type: Number, required: true },
            occurrence: { type: [Object, Boolean], default: false },
            occurrences: { type: Array, default: []}
        },
        data () {
            const oc = this.$props.occurrence
            return {
                dates__: [],
                description: oc.description || '',
                duration: oc.duration || '',
                frequency: oc.frequency || 'once',
                price: oc.price || '',
                spaceId: oc.spaceId || 0,
                spaces: [],
                startsOn: oc.startsOn || '',
                startsAt: oc.startsAt || '',
                until: oc.until || ''
            }
        },
        computed: {
            daysParams () {
                const days = {}
                this.$options.dates.forEach((date, index) => {
                    days[`day[${index}]`] = this.dates__.includes(date) ? 'on' : undefined
                })
                return days
            },
            defaultDescription () {
                switch (this.frequency) {
                    case 'once':
                        if (this.startDate && this.startsAt) {
                            const date = new Date(this.startDate)
                            return `Dia ${date.getDate()} de ${this.$options.months[date.getMonth()]} de ${date.getFullYear()} às ${this.startsAt}`
                        }
                        return '...'
                    case 'daily':
                        if (this.startDate && this.until && this.startsAt) {
                            return `Diariamente de ${this.interval} às ${this.startsAt}`
                        }
                        return '...'
                    case 'weekly':
                        if (this.startDate && this.until && this.startsAt && this.dates__.length > 0) {
                            const sortedDays = this.dates__.sort((d1, d2) => this.$options.dates.findIndex(d => d === d1) - this.$options.dates.findIndex(d => d === d2))
                            if (sortedDays.length === 1) {
                                return `Tod${sortedDays[0].slice(-1)} ${sortedDays[0]} de ${this.interval} às ${this.startsAt}`
                            } else {
                                const earlyDays = sortedDays.slice(0, -1)
                                return `Tod${sortedDays[0].slice(-1)} ${earlyDays.map(d => d.slice(0, 3)).join(', ')} e ${sortedDays[sortedDays.length - 1].slice(0, 3)} de ${this.interval} às ${this.startsAt}`
                            }
                        }
                        return '...'
                }
            },
            endsAt () {
                if (this.startsAt && this.duration) {
                    const date = addMinutes(parseDate(this.startsAt, 'k:m', new Date()), parseInt(this.duration))
                    return formatDate(date, 'kk:mm', { locale })
                }
                return ''
            },
            interval () {
                if (this.startDate && this.until) {
                    const d1 = new Date(this.startDate)
                    const d2 = new Date(this.until)
                    const endDate = `${d2.getDate()} de ${this.$options.months[d2.getMonth()]} de ${d2.getFullYear()}`
                    let startDate = d1.getDate()
                    if (d1.getMonth() !== d2.getMonth()) {
                        startDate += ` de ${this.$options.months[d1.getMonth()]}`
                    }
                    if (d1.getFullYear() !== d2.getFullYear()) {
                        startDate += ` de ${d1.getFullYear()}`
                    }
                    return `${startDate} a ${endDate}`
                }
                return ''
            },
            params () {
                return {
                    eventId: this.$props.event,
                    spaceId: this.spaceId,
                    startsAt: this.startsAt,
                    duration: this.duration,
                    endsAt: this.endsAt,
                    frequency: this.frequency,
                    startsOn: this.startsOn,
                    until: this.until,
                    ...this.daysParams,
                    description: this.description,
                    price: this.price
                }
            }
        },
        created () {
            this.$mc.Spaces.find().then(response => this.spaces = response.data)
        },
        methods: {
            save () {
                var occurrences = this.$props.occurrences;
                var occurrenceId = this.$props.occurrenceId;
                if (this.$props.occurrence) {
                    this.$mc.EventRules.update(this.$props.occurrenceId, this.params)
                        .then((response) => {
                            var index
                            for(var i in occurrences){
                                if(occurrences[i].id == occurrenceId){
                                    index = i;
                                }
                            }
                            occurrences[index].rule = response.data.rule;
                        })
                        .catch(error => {
                            // window.alert('Ocorreu um erro')
                            console.error(error)
                        })
                } else {
                    this.$mc.EventRules.create(this.params)
                        .then((response) => {
                            occurrences.push(response.data);
                        })
                        .catch(error => {
                            // window.alert('Ocorreu um erro')
                            console.error(error)
                        })
                }

            }
        },
        dates: [ 'domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'],
        months: ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro']
    }
</script>
