export { EasyAdminBlock }

class EasyAdminBlock
{
    #element;
    #name;
    #inputs;
    #selects;
    #textareas;
    constructor(element, name) {
        this.#element = element;
        this.#name = name;
    }

    get element() {
        return this.#element;
    }

    get inputs() {
        return Array.from(this.#element.querySelectorAll('.form-widget input'));
    }

    get selects() {
       return Array.from(this.#element.querySelectorAll('.form-widget select'));
    }

    get textareas() {
       return Array.from(this.#element.querySelectorAll('.form-widget textarea'));
    }
}