export { initContactDetailsObserver, updateTomselectsOnInputChange, generateTomselectOption, updateOrInsertOption };

function isFieldCollection(node) {
    return node instanceof HTMLElement && node.classList.contains('field-collection-item');
}

function initContactDetailsObserver(professionalTab) {
    const observedElement = document.querySelector('[data-form-type-name-placeholder="__contact_detailsname__"]');
    const observerConfig = { attributes: false, childList: true, subtree: true };
    const mutationObserver = new MutationObserver((mutationList) => {
        const contactDetailsMutation = mutationList.filter((mutation) => !mutation.target.classList.contains('ea-form-collection-items'))[0];

        const addedFieldCollections = Array.from(contactDetailsMutation.addedNodes).filter((node) => isFieldCollection(node));
        if(addedFieldCollections.length > 0) {
            const contactDetailAddedEvent = new CustomEvent('contactDetailAdded', { detail : { addedElement : addedFieldCollections[0]} });
            professionalTab.element.dispatchEvent(contactDetailAddedEvent);
        }

        const removedFieldCollections = Array.from(contactDetailsMutation.removedNodes).filter((node) => isFieldCollection(node));
        if(removedFieldCollections.length > 0) {
            const contactDetailAddedEvent = new CustomEvent('contactDetailRemoved', { detail :{ removedElement : removedFieldCollections[0]} });
            professionalTab.element.dispatchEvent(contactDetailAddedEvent);
        }
    });
    mutationObserver.observe(observedElement, observerConfig);
}

function updateOrInsertOption(tomSelect, oldValue, newValue, optgroupId = null) {
    const option = { value : newValue , text : newValue };
    if(optgroupId !== null) option.optgroup = optgroupId; 

    if(newValue === '') {
        tomSelect.removeOption(oldValue);
        tomSelect.refreshItems();
        return;
    }

    if(oldValue !== '' && tomSelect.options[oldValue] !== undefined) {
        tomSelect.updateOption(oldValue, option);
        tomSelect.refreshItems();
    } else {
        tomSelect.addOption(option);
        tomSelect.refreshItems();
    }
}

function updateTomselectsOnInputChange(tomselects, input, optgroup = null) {
    let oldValue = input.value;
    input.addEventListener('focus', () => {
        oldValue = input.value;
    });

    input.addEventListener('blur', () => {
        tomselects.forEach((tomselect) => updateOrInsertOption(tomselect, oldValue, input.value, optgroup))
    })     
}

function generateTomselectOption(value, text, optgroup = null) {
    const OPTION = { value : value, text : text };
    if(optgroup !== null) OPTION.optgroup = optgroup;
    return OPTION;
}