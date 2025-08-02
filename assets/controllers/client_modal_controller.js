import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["modal", "nameInput"];

    openModal() {
        this.modalTarget.classList.remove("hidden");
        this.nameInputTarget.focus();
    }

    closeModal() {
        this.modalTarget.classList.add("hidden");
        this.nameInputTarget.value = "";
    }

    async createClient() {
        const name = this.nameInputTarget.value.trim();
        if (!name) return;

        console.log(name);

        const response = await fetch("/client/create-from-modal", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({ name })
        });

        console.log(response);

        if (response.ok) {
            const data = await response.json();

            const select = document.querySelector("#project_client"); // adapte l'ID selon ton form
            const option = new Option(data.name, data.id, true, true);
            select.add(option);

            this.closeModal();
        } else {
            alert("Erreur lors de la création.");
        }
    }
}
