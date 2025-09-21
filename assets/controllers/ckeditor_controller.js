// assets/controllers/ckeditor_controller.js
import { Controller } from "@hotwired/stimulus";
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";

export default class extends Controller {
    static targets = ["textarea"];
    static values = {
        toolbar: Array,
        licenseKey: String, // <- on ajoute ça
    };

    async connect() {
        const el = this.textareaTarget || this.element;
        const toolbar = this.hasToolbarValue && this.toolbarValue.length
            ? this.toolbarValue
            : ["bold", "italic", "bulletedList", "numberedList", "undo", "redo"];

        const licenseKey = this.hasLicenseKeyValue ? this.licenseKeyValue : "GPL"; // <- valeur par défaut

        this.editor = await ClassicEditor.create(el, { toolbar, licenseKey });

        const form = el.closest("form");
        if (form) {
            this._onSubmit = () => { el.value = this.editor.getData(); };
            form.addEventListener("submit", this._onSubmit);
        }
    }

    disconnect() {
        if (this._onSubmit) {
            const el = this.textareaTarget || this.element;
            const form = el.closest("form");
            form && form.removeEventListener("submit", this._onSubmit);
        }
        this.editor && this.editor.destroy().catch(() => {});
    }
}
