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

    if(easyAdminPage.entity === 'Contact' && formPages.includes(easyAdminPage.name)) {

        // MODIFICATION DES <select> DES COORDONNEES EN TOMSELECT

        const STRUCTURE_SELECTS = easyAdminPage.tabs['PROFESSIONNEL'].blocks['Coordonnées'].element.querySelectorAll('[id^="Contact_contact_details_"][id$="_structure"]');
        STRUCTURE_SELECTS.forEach((select) =>  new TomSelect(select, {maxOptions : null}));
        STRUCTURE_SELECTS.forEach((select) => {
            select?.parentElement?.querySelector('div.form-select')?.classList.add('text-wrap');
        })

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

        // INIT NEWSLETTER EMAILS

        OPTIONS[PERSONNAL_EMAIL] = generateTomselectOption(PERSONNAL_EMAIL, PERSONNAL_EMAIL, OPTGROUPS.personnal);

        Array.from(PROFESSIONAL_EMAIL_INPUTS).forEach((input) => {
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
            if(addedEmailInput === null) return;
            updateTomselectsOnInputChange([TOMSELECT], addedEmailInput, OPTGROUPS.professional);

            const structureSelect = ev.detail.addedElement.querySelector('[id^="Contact_contact_details_"][id$="_structure"]');
            if(structureSelect === null) return;
            new TomSelect(structureSelect);
            structureSelect?.parentElement?.querySelector('div.form-select')?.classList.add('text-wrap');
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

    if(easyAdminPage.name === EasyAdminPage.names.detail) {
        const TABS = document.querySelectorAll('.nav-tabs [id^="tablist-tab"]');
        const EDIT_LINK = document.querySelector('.action-edit[data-action-name="edit"]');
        if(EDIT_LINK instanceof HTMLAnchorElement) {
            const EDIT_URL = new URL(EDIT_LINK.href);
            TABS.forEach((tab) => {
                tab.addEventListener('show.bs.tab', function() {
                    EDIT_URL.searchParams.set('tab', this.hash.substring(1))
                    EDIT_LINK.href = EDIT_URL.href
                })
            })
        }
    }

    if(easyAdminPage.name === EasyAdminPage.names.edit) {
        const URL_PARAMS = new URLSearchParams(window.location.href);
        if(URL_PARAMS.has('tab')) {
            const TAB_NAME = URL_PARAMS.get('tab').replace(window.location.hash, '');
            const TAB_ELEMENT = document.querySelector(`[href="#${TAB_NAME}"]`);
            if(TAB_ELEMENT !== null) bootstrap.Tab.getOrCreateInstance(TAB_ELEMENT).show();
        }
    }

    {
        const IS_PROGRAM_SENT_TOGGLE = document.getElementById('Contact_postProgram_is_sent') ?? document.getElementById('Structure_postProgram_is_sent');
        const POST_PROGRAM_FIELD = document.getElementById('Contact_postProgram_structure') ?? document.getElementById('Structure_postProgram_contact');

        (IS_PROGRAM_SENT_TOGGLE.checked) ? POST_PROGRAM_FIELD.tomselect.enable() : POST_PROGRAM_FIELD.tomselect.disable();

        IS_PROGRAM_SENT_TOGGLE.addEventListener('change', function() {
            (this.checked) ? POST_PROGRAM_FIELD.tomselect.enable() : POST_PROGRAM_FIELD.tomselect.disable()
        });
    }
});