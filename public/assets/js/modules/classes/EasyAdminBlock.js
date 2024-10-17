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
        return Array.from(this.#element.querySelectorAll('input')).filter((input) => input.checkVisibility());
    }

    get selects() {
       return Array.from(this.#element.querySelectorAll('select')).filter((select) => select.checkVisibility());
    }

    get textareas() {
       return Array.from(this.#element.querySelectorAll('textarea')).filter((textarea) => textarea.checkVisibility());
    }
}