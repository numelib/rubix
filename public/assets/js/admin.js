import { EasyAdminPage } from "./modules/classes/EasyAdminPage.js";
import { updateTomselectsOnInputChange, initContactDetailsObserver, generateTomselectOption, updateOrInsertOption } from "./modules/functions.js";

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

        const STRUCTURE_SELECTS = easyAdminPage.tabs['PROFESSIONNEL'].blocks['Coordonnées'].element.querySelectorAll('[id^="Contact_contact_details_"][id$="_structure"]');
        STRUCTURE_SELECTS.forEach((select) =>  new TomSelect(select, {maxOptions : null}));

        // SETUP

        const OPTIONS = {};
        const OPTGROUPS = { 'personnal' : 1, 'professional' : 2 };
        const PROFESSIONAL_TAB = easyAdminPage.tabs['PROFESSIONNEL'];

        const PERSONNAL_EMAIL_INPUT = document.querySelector('#Contact_personnal_email');
        const PERSONNAL_EMAIL = PERSONNAL_EMAIL_INPUT.value;

        const PROFESSIONAL_EMAIL_INPUTS = PROFESSIONAL_TAB.blocks['Coordonnées'].inputs.filter((input) => /Contact_contact_details_[0-9]+_email/.test(input.id));

        const CONTACT_NEWSLETTER_SELECT = document.querySelector('#Contact_newsletter_email');
        const CONTACT_NEWSLETTER_SELECT_WRAPPER = CONTACT_NEWSLETTER_SELECT.parentElement;
        const DATABASE_VALUE = CONTACT_NEWSLETTER_SELECT.value;
        const TOMSELECT = CONTACT_NEWSLETTER_SELECT.tomselect;

        TOMSELECT.clear();
        TOMSELECT.clearOptions();
        TOMSELECT.refreshOptions();
        TOMSELECT.addOptionGroup(OPTGROUPS.professional, { value: 'Professionnel', label: 'Professionnel' });
        TOMSELECT.addOptionGroup(OPTGROUPS.personnal, { value: 'Personnel', label: 'Personnel' });

        if(CONTACT_NEWSLETTER_SELECT_WRAPPER.querySelector('.invalid-feedback') === null) {
            const WARNING_PARAGRAPH = document.createElement('p');
            WARNING_PARAGRAPH.classList.add('invalid-feedback');
            CONTACT_NEWSLETTER_SELECT_WRAPPER.appendChild(WARNING_PARAGRAPH);
        }

        const WARNING_PARAGRAPH = CONTACT_NEWSLETTER_SELECT_WRAPPER.querySelector('.invalid-feedback');

        // INIT NEWSLETTER EMAILS

        OPTIONS[PERSONNAL_EMAIL] = generateTomselectOption(PERSONNAL_EMAIL, PERSONNAL_EMAIL, OPTGROUPS.personnal);

        Array.from(PROFESSIONAL_EMAIL_INPUTS).forEach((input) => {
            if(OPTIONS[input.value] !== undefined) {
                WARNING_PARAGRAPH.classList.add('d-block');
                WARNING_PARAGRAPH.textContent = `L\'email ${input.value} a été référencé au minimum deux fois dans des champs différents !`;
            }

            OPTIONS[input.value] = generateTomselectOption(input.value, input.value, OPTGROUPS.professional);
        })

        Object.values(OPTIONS).forEach((option) => {
            TOMSELECT.addOption(option);

            if(option.value === DATABASE_VALUE) {
                TOMSELECT.setValue(option.value);
            }
        })

        TOMSELECT.addOptions(Object.values(OPTIONS));

        // UPDATE PERSONNAL EMAIL
       
        let oldValue = PERSONNAL_EMAIL;
        PERSONNAL_EMAIL_INPUT.addEventListener('focus', function() {
            oldValue = this.value;
        });

        PERSONNAL_EMAIL_INPUT.addEventListener('blur', function() {
            updateOrInsertOption(TOMSELECT, oldValue, this.value, OPTGROUPS.personnal);
        });

        // UPDATE PROFESSIONAL EMAILS

        PROFESSIONAL_EMAIL_INPUTS.forEach((input) => {
            updateTomselectsOnInputChange([TOMSELECT], input, OPTGROUPS.professional);
        })

        initContactDetailsObserver(PROFESSIONAL_TAB);

        PROFESSIONAL_TAB.element.addEventListener('contactDetailAdded', (ev) => {
            const addedEmailInput = ev.detail.addedElement.querySelector('[id^="Contact_contact_details_"][id$="_email"]');
            updateTomselectsOnInputChange([TOMSELECT], addedEmailInput, OPTGROUPS.professional);

            const structureSelect = ev.detail.addedElement.querySelector('[id^="Contact_contact_details_"][id$="_structure"]')
            new TomSelect(structureSelect);
        });

        PROFESSIONAL_TAB.element.addEventListener('contactDetailRemoved', (ev) => {
            const removedEmailInput = ev.detail.removedElement.querySelector('[id^="Contact_contact_details_"][id$="_email"]');
            TOMSELECT.removeOption(removedEmailInput.value);
        });
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