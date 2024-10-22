import { EasyAdminPage } from "./modules/classes/EasyAdminPage.js";
import { updateTomselectsOnInputChange, initContactDetailsObserver } from "./modules/functions.js";

$(document).ready(function () {
   /** Contact Profile Type **/

    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    urlParams.set('crudAction', 'new');
    const newUrl = window.location.href.replace(queryString, '?' + urlParams.toString());

    var $profileTypes = $('#Contact_profile_types');
    var $structureType = $('#Structure_structureType');

    if($structureType.val() === '') {
        document.getElementById('Structure_structure_type_specializations').tomselect.disable();
    }

    if($profileTypes.val()?.length === 0) {
        document.getElementById('Contact_disciplines').tomselect.disable();
    }
    
    
    $profileTypes.on('change', function() {
        var $form = $(this).closest('form');
        var data = {};
        data[$profileTypes.attr('name')] = $profileTypes.val();
        $.ajax({
            url : newUrl,
            method: $form.attr('method'),
            data : data,
            dataType: 'html',
            complete: function(html) {
                $('#Contact_disciplines').parent().parent().replaceWith(
                    $(html.responseText).find('#Contact_disciplines').parent().parent()
                );
                var control = new TomSelect('#Contact_disciplines', {});
                if(Object.keys(control.options).length === 0) control.disable();
            },
        });
    });

    $structureType.on('change', function() {
        var $form = $(this).closest('form');
        var data = {};
        data[$structureType.attr('name')] = $structureType.val();
        $.ajax({
            url : newUrl,
            method: $form.attr('method'),
            data : data,
            dataType: 'html',
            complete: function(html) {
                $('#Structure_structure_type_specializations').parent().parent().replaceWith(
                    $(html.responseText).find('#Structure_structure_type_specializations').parent().parent()
                );
                var control = new TomSelect('#Structure_structure_type_specializations', {});
                if(Object.keys(control.options).length === 0) control.disable();
            },
        });
    });


    const easyAdminPage = new EasyAdminPage(document.body);
    const formPages = [EasyAdminPage.names.new, EasyAdminPage.names.edit];

    if(easyAdminPage.entity === 'Contact' && easyAdminPage.name === EasyAdminPage.names.edit) {
        const CONTACT_ID = urlParams.get('entityId');
        const FESTIVAL_PROGRAM_TOGGLE = document.querySelector('#Contact_is_receiving_festival_program');

        const WARNING_PARAGRAPH = document.createElement('p');
        WARNING_PARAGRAPH.classList.add('text-warning', 'my-0', 'd-none');
        FESTIVAL_PROGRAM_TOGGLE.parentElement.insertAdjacentElement('afterend', WARNING_PARAGRAPH);

        FESTIVAL_PROGRAM_TOGGLE.addEventListener('change', function() {
            if(this.checked === false) {
                WARNING_PARAGRAPH.classList.add('d-none');
                return;
            }

            const fetchStructureSendingFestivalProgram = async function(contactId) {
                const API_URL = window.location.origin + '/api/festival-program/contact?contactId=' + contactId;
                const RESPONSE = await fetch(API_URL);
                const DATA = await RESPONSE.json();

                return DATA.structure;
            };

            fetchStructureSendingFestivalProgram(CONTACT_ID).then((structure) => {
                if(structure !== null) {
                    // Display a warning message
                    const MESSAGE = 'ATTENTION : le contact recoit déjà le programme via la structure : ' + structure;
                    WARNING_PARAGRAPH.textContent = MESSAGE;
                    WARNING_PARAGRAPH.classList.remove('d-none');
                }
            })
        })
    }

    if(easyAdminPage.entity === 'Structure' && formPages.includes(easyAdminPage.name)) {
        const FESTIVAL_PROGRAM_TOGGLE = document.querySelector('#Structure_is_receiving_festival_program');
        const CONTACT_SELECT = document.querySelector('#Structure_contact_receiving_festival_program');

        const WARNING_PARAGRAPH = document.createElement('p');
        WARNING_PARAGRAPH.classList.add('text-warning', 'my-0', 'd-none');
        CONTACT_SELECT.parentElement.insertAdjacentElement('afterend', WARNING_PARAGRAPH);

        const fetchContactReceivingFestivalProgram = async function(contactId) {
            const API_URL = window.location.origin + '/api/festival-program/structure?contactId=' + contactId;
            const RESPONSE = await fetch(API_URL);
            const DATA = await RESPONSE.json();

            return DATA.contact;
        };

        FESTIVAL_PROGRAM_TOGGLE.addEventListener('change', function() {
            if(this.checked !== true || CONTACT_SELECT.value === '') {
                WARNING_PARAGRAPH.classList.add('d-none');
                return;
            }

            const CONTACT_ID = CONTACT_SELECT.value;
            fetchContactReceivingFestivalProgram(CONTACT_ID).then((contact) => {
                if(contact !== null) {
                    const MESSAGE = 'ATTENTION : le contact ' + contact + 'recoit déjà le programme';
                    WARNING_PARAGRAPH.textContent = MESSAGE;
                    WARNING_PARAGRAPH.classList.remove('d-none');
                }
            });
        });

        CONTACT_SELECT.addEventListener('change', function() {
            if(FESTIVAL_PROGRAM_TOGGLE.checked !== true || this.value === '') {
                WARNING_PARAGRAPH.classList.add('d-none');
                return;
            }

            const CONTACT_ID = this.value;
            fetchContactReceivingFestivalProgram(CONTACT_ID).then((contact) => {
                if(contact !== null) {
                    const MESSAGE = 'ATTENTION : le contact ' + contact + ' recoit déjà le programme';
                    WARNING_PARAGRAPH.textContent = MESSAGE;
                    WARNING_PARAGRAPH.classList.remove('d-none');
                }
            });
        });
    }

    if(easyAdminPage.entity === 'Contact' && formPages.includes(easyAdminPage.name)) {

        // MODIFICATION DES <select> DES COORDONNEES EN TOMSELECT

        const structureSelects = easyAdminPage.tabs['PROFESSIONNEL'].blocks['Coordonnées'].element.querySelectorAll('[id^="Contact_contact_details_"][id$="_structure"]');
        structureSelects.forEach((select) =>  new TomSelect(select, {maxOptions : null}));
        
        // MODIFICATION DYNAMIQUE EMAILS

        const optgroups = { 'personnal' : 1, 'professional' : 2 };
        const tomselects = [
            easyAdminPage.tabs['COMMUNICATION']?.blocks['Envoi newsletters']?.element?.querySelector('#Contact_newsletter_email').tomselect
        ];

        tomselects.forEach((tomselect) => tomselect.addOptionGroup(optgroups.professional, { value: 'professional', label: 'Professionnel' }));
        tomselects.forEach((tomselect) => tomselect.addOptionGroup(optgroups.personnal, { value: 'personnal', label: 'Personnel' }));

        // 1 - GESTON AJOUT/SUPPRESSION/MODIFICATION EMAILS PROFESSIONNELS

        const professionalTab = easyAdminPage.tabs['PROFESSIONNEL'];

        const proEmailInputs = professionalTab.blocks['Coordonnées'].inputs.filter((input) => /Contact_contact_details_[0-9]+_email/.test(input.id));
        proEmailInputs.forEach((proEmailInput) => {
            updateTomselectsOnInputChange(tomselects, proEmailInput, optgroups.professional);
        })

        initContactDetailsObserver(professionalTab);

        professionalTab.element.addEventListener('contactDetailAdded', (ev) => {
            const addedEmailInput = ev.detail.addedElement.querySelector('[id^="Contact_contact_details_"][id$="_email"]');
            updateTomselectsOnInputChange(tomselects, addedEmailInput, optgroups.professional);

            const structureSelect = ev.detail.addedElement.querySelector('[id^="Contact_contact_details_"][id$="_structure"]')
            new TomSelect(structureSelect);
        });

        professionalTab.element.addEventListener('contactDetailRemoved', (ev) => {
            const removedEmailInput = ev.detail.removedElement.querySelector('[id^="Contact_contact_details_"][id$="_email"]');
            tomselects.forEach((tomselect) => tomselect.removeOption(removedEmailInput.value));
        });

        // 2 - GESTION MODIFICATION EMAIL PERSONNEL

        const persoEmailInput = document.querySelector('#Contact_personnal_email');
        updateTomselectsOnInputChange(tomselects, persoEmailInput, optgroups.personnal);
    }

    if(easyAdminPage.entity === 'Structure' && formPages.includes(easyAdminPage.name)) {
        const inputs = easyAdminPage.tabs['STRUCTURE'].blocks['Coordonnées'].element.querySelectorAll('#Structure_email');
        const output = easyAdminPage.tabs['COMMUNICATION'].blocks['Envoi newsletters'].element.querySelector('#Structure_newsletter_email');
        // console.log(output.tomselect);
        inputs.forEach((input) => {
            updateTomselectsOnInputChange([output.tomselect], input);
        })


    }
});