import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["typeSelect", "details"];
    static values = { otherValue: String };

    connect() {
        this.toggle();
    }

    toggle() {
        if (!this.hasTypeSelectTarget || !this.hasDetailsTarget) return;

        const currentValue = this.typeSelectTarget.value;
        const shouldShow = currentValue === (this.otherValueValue || "other");

        this.detailsTarget.classList.toggle("hidden", !shouldShow);

        const input = this.detailsTarget.querySelector("input, textarea");
        if (!input) return;

        if (shouldShow) {
            input.setAttribute("required", "required");
        } else {
            input.removeAttribute("required");
            input.value = "";
        }
    }
}
