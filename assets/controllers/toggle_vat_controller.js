import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["vat", "checkbox"];

    connect() {
        this.toggle();
    }

    toggle() {
        const vatField = this.vatTarget;

        if (this.checkboxTarget.checked) {
            vatField.classList.add("hidden");
        } else {
            vatField.classList.remove("hidden");
        }
    }
}
