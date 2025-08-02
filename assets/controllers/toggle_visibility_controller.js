import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkbox', 'toggleField']

    connect() {
        this.toggle(); // au chargement de la page, on masque/affiche les champs selon l'état de la checkbox
    }

    toggle() {
        const isChecked = this.checkboxTarget.checked;
        this.toggleFieldTargets.forEach(el => {
            if (isChecked) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });
    }
}
