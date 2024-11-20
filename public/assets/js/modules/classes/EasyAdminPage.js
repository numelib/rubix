import { EasyAdminTab } from "./EasyAdminTab.js";
export { EasyAdminPage };

class EasyAdminPage 
{
    static #names = { edit : "EDIT", new : "NEW", detail : "DETAIL", index : "INDEX" };
    #name = null;
    #entity = null;
    #element = null;

    constructor(element) {
        this.#element = element;
        this.#name = Object.values(EasyAdminPage.#names).filter((name) => ( element.classList.contains('ea-' + name.toLowerCase())))[0];
        this.#entity = (this.#name !== undefined) ? Array.from(this.#element.classList).filter((cssClass) => cssClass.includes(`ea-${this.#name.toLowerCase()}-`))[0].replace(`ea-${this.#name.toLowerCase()}-`, '') : null;
    }

    static get names() {
        return EasyAdminPage.#names;
    }

    get name() {
        return this.#name;
    }
    
    get tabs() {
        const tabs = {};
        this.#element.querySelectorAll('.nav-tabs-custom.form-tabs-content > .tab-content > *').forEach((tab) => {
            const name = this.#element.querySelector('#tablist-' + tab.id).textContent.trim();
            const data = new EasyAdminTab(tab, name);
            tabs[name] = data;
        });

        return tabs;
    }

    get entity() {
        return this.#entity;
    }

    get activeTab() {
        return this.#element.querySelector('.nav.nav-tabs .nav-link.active');
    }
}