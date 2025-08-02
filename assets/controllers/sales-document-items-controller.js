import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["itemsContainer"];

    connect() {
        // On récupère le prototype du formulaire imbriqué
        this.prototype = this.itemsContainerTarget.dataset.prototype;
        this.index = this.itemsContainerTarget.children.length;
    }

    addItem() {
        if (!this.prototype) return;

        // On remplace __name__ par l’index courant
        const newForm = this.prototype.replace(/__name__/g, this.index);

        // Crée un div pour contenir la nouvelle ligne
        const div = document.createElement("div");
        div.innerHTML = newForm;
        div.classList.add("mb-4", "p-4", "border", "rounded", "bg-gray-700");

        // Ajoute un bouton supprimer
        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.textContent = "Supprimer la ligne";
        removeButton.classList.add("mt-2", "px-3", "py-1", "bg-red-600", "text-white", "rounded");
        removeButton.addEventListener("click", () => {
            div.remove();
        });

        div.appendChild(removeButton);

        this.itemsContainerTarget.appendChild(div);

        this.index++;
    }
}
