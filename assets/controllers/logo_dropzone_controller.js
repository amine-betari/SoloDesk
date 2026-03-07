import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'dropzone', 'preview', 'filename'];
    static values = {
        currentSrc: String,
    };

    connect() {
        this.dropzoneTarget.addEventListener('dragover', this.onDragOver);
        this.dropzoneTarget.addEventListener('dragleave', this.onDragLeave);
        this.dropzoneTarget.addEventListener('drop', this.onDrop);

        if (this.currentSrcValue) {
            this.previewTarget.src = this.currentSrcValue;
            this.previewTarget.classList.remove('hidden');
            this.filenameTarget.textContent = this.currentSrcValue.split('/').pop() || '';
        }
    }

    disconnect() {
        this.dropzoneTarget.removeEventListener('dragover', this.onDragOver);
        this.dropzoneTarget.removeEventListener('dragleave', this.onDragLeave);
        this.dropzoneTarget.removeEventListener('drop', this.onDrop);
    }

    onDragOver = (event) => {
        event.preventDefault();
        this.dropzoneTarget.classList.add('ring-2', 'ring-blue-400', 'bg-blue-50');
    };

    onDragLeave = (event) => {
        event.preventDefault();
        this.dropzoneTarget.classList.remove('ring-2', 'ring-blue-400', 'bg-blue-50');
    };

    onDrop = (event) => {
        event.preventDefault();
        this.dropzoneTarget.classList.remove('ring-2', 'ring-blue-400', 'bg-blue-50');

        const files = event.dataTransfer?.files;
        if (!files || files.length === 0) return;

        this.inputTarget.files = files;
        this.preview();
    };

    open() {
        this.inputTarget.click();
    }

    preview() {
        const file = this.inputTarget.files?.[0];
        if (!file) return;

        this.filenameTarget.textContent = file.name;
        const reader = new FileReader();
        reader.onload = () => {
            this.previewTarget.src = reader.result;
            this.previewTarget.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
}
