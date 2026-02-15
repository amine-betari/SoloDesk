// assets/controllers/sales_document_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["clientField", "projectSelect", "estimateSelect"]

    connect() {
        this.toggleClient();
    }

    toggleClient() {

        // a revoir car c tjr vrai

        const hasProject  = this.data.get('has-project-select') === 'true';
        const hasEstimate = this.data.get('has-estimate-select') === 'true';

        if (hasProject || hasEstimate) {
            this.clientFieldTarget.classList.add("hidden");
        } else {
            this.clientFieldTarget.classList.remove("hidden");
        }
    }
}
