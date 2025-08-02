import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['container']
    static values = {
        prototype: String,
        index: Number
    }

    add() {
        const html = this.prototypeValue.replace(/__name__/g, this.indexValue)
        const element = document.createElement('div')
        element.classList.add('mb-2')
        element.innerHTML = html
        this.containerTarget.appendChild(element)
        this.indexValue++
    }
}
