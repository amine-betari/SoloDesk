import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["type", "status"]

    connect() {
        this.filterStatus() // filtre au chargement
        this.typeTarget.addEventListener('change', () => this.filterStatus())
    }

    filterStatus() {
        const type = this.typeTarget.value
        const estimateStatuses = window.EstimateStatuses || {}
        const invoiceStatuses = window.InvoiceStatuses || {}

        Array.from(this.statusTarget.options).forEach(option => {
            if (!option.value) return // placeholder

            if (!type) {
                // Pas de type sélectionné => montrer tout
                option.hidden = false
            } else if (type === 'estimate') {
                option.hidden = !Object.keys(estimateStatuses).includes(option.value)
            } else if (type === 'invoice' || type === 'project') {
                option.hidden = !Object.keys(invoiceStatuses).includes(option.value)
            } else {
                option.hidden = false
            }
        })
    }
}
