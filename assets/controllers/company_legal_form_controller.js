import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['legalForm', 'rcField'];

    connect() {
        this.toggle();
    }

    toggle() {
        const value = this.legalFormTarget.value;
        const showRc = value === 'SARL_AU' || value === 'SARL';

        this.rcFieldTarget.classList.toggle('hidden', !showRc);
    }
}
