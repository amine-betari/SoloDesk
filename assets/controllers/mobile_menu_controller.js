import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panel', 'iconOpen', 'iconClose'];

    connect() {
        this.open = false;
        this.sync();
    }

    toggle() {
        this.open = !this.open;
        this.sync();
    }

    sync() {
        this.panelTarget.classList.toggle('hidden', !this.open);
        this.iconOpenTarget.classList.toggle('hidden', this.open);
        this.iconCloseTarget.classList.toggle('hidden', !this.open);
    }
}
