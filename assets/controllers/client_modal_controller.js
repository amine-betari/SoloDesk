import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["modal", "nameInput", "currencySelect", "clientSelect", "error"];
    static values = { endpoint: String };

    openModal(event) {
        if (event?.preventDefault) {
            event.preventDefault();
            event.stopPropagation();
        }
        this.clearError();
        this.modalTarget.classList.remove("hidden");
        document.body.classList.add("overflow-hidden");
        this.nameInputTarget.focus();
    }

    closeModal(event) {
        if (event?.preventDefault) {
            event.preventDefault();
            event.stopPropagation();
        }
        this.modalTarget.classList.add("hidden");
        this.nameInputTarget.value = "";
        if (this.hasCurrencySelectTarget) {
            this.currencySelectTarget.selectedIndex = 0;
        }
        document.body.classList.remove("overflow-hidden");
        this.clearError();
    }

    async createClient(event) {
        if (event?.preventDefault) {
            event.preventDefault();
        }

        const name = this.nameInputTarget.value.trim();
        const currency = this.hasCurrencySelectTarget ? this.currencySelectTarget.value : "";

        if (!name) {
            this.showError("Le nom du client est obligatoire.");
            return;
        }

        if (this.hasCurrencySelectTarget && !currency) {
            this.showError("Veuillez choisir une devise.");
            return;
        }

        this.setBusy(true);

        try {
            const response = await fetch(this.endpointValue || "/client/create-from-modal", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({ name, currency })
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => null);
                const message = payload?.error || "Erreur lors de la création.";
                this.showError(message);
                return;
            }

            const data = await response.json();
            this.addClientToSelect(data);
            this.closeModal();
        } catch (error) {
            this.showError("Erreur réseau. Réessayez.");
        } finally {
            this.setBusy(false);
        }
    }

    addClientToSelect(data) {
        const select = this.clientSelectTarget;
        if (!select) return;

        const value = String(data.id);

        if (select.tomselect) {
            select.tomselect.addOption({ value, text: data.name });
            select.tomselect.addItem(value, true);
            return;
        }

        const option = new Option(data.name, value, true, true);
        select.add(option);
        select.dispatchEvent(new Event("change", { bubbles: true }));
    }

    showError(message) {
        if (!this.hasErrorTarget) {
            alert(message);
            return;
        }
        this.errorTarget.textContent = message;
        this.errorTarget.classList.remove("hidden");
    }

    clearError() {
        if (!this.hasErrorTarget) return;
        this.errorTarget.textContent = "";
        this.errorTarget.classList.add("hidden");
    }

    setBusy(isBusy) {
        if (!this.hasNameInputTarget) return;
        this.nameInputTarget.disabled = isBusy;
        if (this.hasCurrencySelectTarget) {
            this.currencySelectTarget.disabled = isBusy;
        }
    }
}
