import { EasyAdminBlock } from "./EasyAdminBlock.js";
export { EasyAdminTab };

class EasyAdminTab
{
    #name;
    #element;

    constructor(element, name) {
        this.#element = element;
        this.#name = name;
    }

    get blocks() {
        const blocks = {};
        this.#element.querySelectorAll('.form-fieldset').forEach((block, index) => {
            const name = block.querySelector('.form-fieldset-title .form-fieldset-title-content').textContent.trim();
            const data = new EasyAdminBlock(block, name);
            blocks[name] = data;
        });

        return blocks;
    }

    get element() {
        return this.#element;
    }

    selectBlocks(names) {
        return names.map((name) => this.blocks[name]);
    } 
}