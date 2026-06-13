import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["errorSummary", "financialSummary", "totalHt", "vatAmount", "totalTtc", "currency"];

    connect() {
        this.dirty = false;
        this.submitted = false;
        this.beforeUnload = this.confirmBeforeUnload.bind(this);
        this.handleInput = this.markDirty.bind(this);
        this.handleChange = this.handleFormChange.bind(this);
        this.handleSubmit = this.markSubmitted.bind(this);
        this.observer = new MutationObserver((mutations) => {
            const itemsChanged = mutations.some((mutation) => mutation.target.closest('[data-controller~="sales-document-items"]'));
            if (!itemsChanged) {
                return;
            }

            this.dirty = true;
            this.markRequiredFields();
            this.updateFinancialSummary();
        });

        this.element.addEventListener("input", this.handleInput);
        this.element.addEventListener("change", this.handleChange);
        this.element.addEventListener("submit", this.handleSubmit);
        window.addEventListener("beforeunload", this.beforeUnload);

        this.markRequiredFields();
        this.showErrors();
        this.updateFinancialSummary();
        this.observer.observe(this.element, { childList: true, subtree: true });
    }

    disconnect() {
        this.element.removeEventListener("input", this.handleInput);
        this.element.removeEventListener("change", this.handleChange);
        this.element.removeEventListener("submit", this.handleSubmit);
        window.removeEventListener("beforeunload", this.beforeUnload);
        this.observer.disconnect();
    }

    markDirty() {
        this.dirty = true;
        this.updateFinancialSummary();
    }

    handleFormChange() {
        this.markDirty();
    }

    markSubmitted() {
        this.submitted = true;
    }

    confirmBeforeUnload(event) {
        if (!this.dirty || this.submitted) {
            return;
        }

        event.preventDefault();
        event.returnValue = "";
    }

    markRequiredFields() {
        this.element.querySelectorAll("[required]").forEach((field) => {
            if (!field.id) {
                return;
            }

            const label = this.element.querySelector(`label[for="${CSS.escape(field.id)}"]`);
            if (label && !label.querySelector("[data-required-marker]")) {
                label.insertAdjacentHTML("beforeend", ' <span data-required-marker class="text-red-600">*</span>');
            }
        });
    }

    showErrors() {
        const invalidField = this.element.querySelector('[aria-invalid="true"], .border-red-500');
        if (!invalidField) {
            return;
        }

        if (this.hasErrorSummaryTarget) {
            this.errorSummaryTarget.classList.remove("hidden");
        }

        window.setTimeout(() => invalidField.focus(), 100);
    }

    updateFinancialSummary() {
        if (!this.hasFinancialSummaryTarget) {
            return;
        }

        let totalHt = this.numberFromSelector('[data-financial-role="amount"]');
        if (totalHt === null) {
            totalHt = this.sumDocumentItems();
        }

        const noVat = this.element.querySelector('[data-financial-role="no-vat"]');
        const taxApplied = this.element.querySelector('[data-financial-role="tax-applied"]');
        const vatRate = this.numberFromSelector('[data-financial-role="vat-rate"]') ?? 0;
        const appliesVat = noVat ? !noVat.checked : (taxApplied ? taxApplied.checked : vatRate > 0);
        const vatAmount = appliesVat ? totalHt * vatRate / 100 : 0;
        const currency = this.resolveCurrency();

        this.totalHtTarget.textContent = this.formatAmount(totalHt, currency);
        this.vatAmountTarget.textContent = this.formatAmount(vatAmount, currency);
        this.totalTtcTarget.textContent = this.formatAmount(totalHt + vatAmount, currency);
        if (this.hasCurrencyTarget) {
            this.currencyTarget.textContent = currency || "—";
        }
    }

    sumDocumentItems() {
        const quantities = this.element.querySelectorAll('[name*="[salesDocumentItems]"][name$="[quantity]"]');
        let total = 0;

        quantities.forEach((quantity) => {
            const prefix = quantity.name.replace(/\[quantity\]$/, "");
            const unitPrice = this.element.querySelector(`[name="${CSS.escape(prefix)}[unitPrice]"]`);
            total += this.parseNumber(quantity.value) * this.parseNumber(unitPrice?.value ?? "0");
        });

        return total;
    }

    numberFromSelector(selector) {
        const field = this.element.querySelector(selector);

        return field ? this.parseNumber(field.value) : null;
    }

    parseNumber(value) {
        const normalized = String(value).replace(/\s/g, "").replace(",", ".");
        const number = Number.parseFloat(normalized);

        return Number.isFinite(number) ? number : 0;
    }

    resolveCurrency() {
        const clientSelect = this.element.querySelector('[data-client-modal-target="clientSelect"]');
        if (!clientSelect?.value) {
            return this.element.dataset.newFormCurrencyValue || "";
        }

        const optionCurrency = clientSelect.selectedOptions?.[0]?.dataset.currency;
        const tomSelectCurrency = clientSelect.tomselect?.options?.[clientSelect.value]?.currency;

        return optionCurrency || tomSelectCurrency || this.element.dataset.newFormCurrencyValue || "";
    }

    formatAmount(value, currency) {
        const options = {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        };
        if (currency) {
            options.style = "currency";
            options.currency = currency;
        }

        return new Intl.NumberFormat(document.documentElement.lang || "fr", options).format(value);
    }
}
