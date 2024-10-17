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
    if(formPages.includes(easyAdminPage.name) && easyAdminPage.entity === 'Contact') {

        // MODIFICATION DES <select> DES COORDONNEES EN TOMSELECT

        const structureSelects = easyAdminPage.tabs['PROFESSIONNEL'].blocks['Coordonnées'].element.querySelectorAll('[id^="Contact_contact_details_"][id$="_structure"]');
        structureSelects.forEach((select) => new TomSelect(select));
        
        // MODIFICATION DYNAMIQUE EMAILS

        const optgroups = { 'personnal' : 1, 'professional' : 2 };
        const tomselects = [
            easyAdminPage.tabs['COMMUNICATION']?.blocks['Général']?.element?.querySelector('#Contact_festival_program_receipt_email').tomselect,
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

    if(formPages.includes(easyAdminPage.name) && easyAdminPage.entity === 'Structure') {
        const inputs = easyAdminPage.tabs['STRUCTURE'].blocks['Coordonnées'].element.querySelectorAll('#Structure_email');
        const output = easyAdminPage.tabs['COMMUNICATION'].blocks['Envoi newsletters'].element.querySelector('#Structure_newsletter_email');
        // console.log(output.tomselect);
        inputs.forEach((input) => {
            updateTomselectsOnInputChange([output.tomselect], input);
        })


    }
});