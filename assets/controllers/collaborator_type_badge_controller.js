import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['select', 'badge'];

    connect() {
        this.update();
    }

    update() {
        const value = this.selectTarget.value;
        let label = '—';
        let classes = 'bg-gray-100 text-gray-700';

        if (value === 'salarie') {
            label = 'Salarié';
            classes = 'bg-emerald-100 text-emerald-700';
        } else if (value === 'freelance') {
            label = 'Freelance';
            classes = 'bg-blue-100 text-blue-700';
        } else if (value === 'collaborateur') {
            label = 'Collaborateur';
            classes = 'bg-amber-100 text-amber-700';
        }

        this.badgeTarget.textContent = label;
        this.badgeTarget.className = `inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${classes}`;
    }
}
