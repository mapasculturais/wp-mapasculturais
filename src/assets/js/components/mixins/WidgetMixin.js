export default {
    props: {
        month: { type: Number, default: () => new Date().getMonth() + 1 },
        year: { type: Number, default: () => new Date().getFullYear() }
    },
    data () {
        return {
            currentMonth: this.$props.month,
            currentYear: this.$props.year
        }
    },
    computed: {
        monthString () {
            return ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'][this.currentMonth]
        }
    },
    methods: {
        beforeCurrentMonth (date) {
            const today = new Date()
            const fistDayOfCurrentMonth = new Date(today.getFullYear(), today.getMonth(), 1)
            return date <= fistDayOfCurrentMonth
        },
        nextMonth () {
            if (this.currentMonth === 12) {
                this.currentMonth = 1
                this.currentYear++
            } else {
                this.currentMonth++
            }
        },
        previousMonth () {
            if (this.currentMonth === 1) {
                this.currentMonth = 12
                this.currentYear--
            } else {
                this.currentMonth--
            }
        },
        weekdayString (day) {
            const weekday = new Date(day).getDay()
            return ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][weekday]
        }
    }
}
