<?php

namespace App\Service;

use App\Entity\Contact;
use App\Entity\Structure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use Exception;
use libphonenumber\PhoneNumber;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntitySpreadsheetGenerator
{
    private TranslatorInterface $translator;

    private ?string $title = null;
    private array $valueReplacements = [
        [
            'value' => true,
            'defaultsTo' => 'Oui',
        ],
        [
            'value' => false,
            'defaultsTo' => 'Non',
        ],
        [
            'value' => null,
            'defaultsTo' => 'Aucun(e)',
        ],
    ];

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function setWorksheetTitle(string $title) : self
    {
        $this->title = $title;

        return $this;
    }

    public function setValueReplacements(array $valueReplacements) : self
    {
        $this->valueReplacements = $valueReplacements;

        return $this;
    }

    private function writeTitleFields(array $fields) : Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        if(!is_null($this->title)) $spreadsheet->getActiveSheet()->setTitle($this->title);

        /* Définit les noms des colonnes */
        $cell['column'] = 'A';
        $cell['row'] = 1;
        foreach($fields as $field) 
        {
            $cell['position'] = $cell['column'] . $cell['row'];
            $spreadsheet->getActiveSheet()->setCellValue($cell['position'], $this->translator->trans($field));
            $cell['column']++;
        }

        return $spreadsheet;
    }

    public function getSpreadsheet(array $entities, array $fields) : Spreadsheet
    {
        $spreadsheet = $this->writeTitleFields($fields);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        
        $cell['default_value'] = 'Aucun(e)';
        $cell['row'] = 2;
        foreach($entities as $entity)
        {
            $cell['column'] = 'A';
            
            foreach($fields as $field)
            {
                $cell['position'] = $cell['column'] . $cell['row'];
                $cell['value'] = $propertyAccessor->getValue($entity, $field);

                // Quick and dirty
                if($entity instanceof Structure && $field === 'is_receiving_festival_program') {
                    $structure = $entity;

                    if($structure->isReceivingFestivalProgram() === true) {
                        $contactAddresses = array_map(fn(Contact $contact) => $contact->getFormattedAddress(oneline : true), $structure->getContactsReceivingFestivalProgram()->toArray());
                        $cell['value'] = empty($contactAddresses) ? $structure->getFormattedAddress(oneline : true) : implode(', ', $contactAddresses);
                        $cell['vlaue'] = str_replace('<br>', ' ', $cell['value']);
                    } else {
                        $cell['value'] = 'Aucun.e';
                    }
                }

                if($cell['value'] instanceof Collection) {
                    $cell['value'] = implode(', ', $cell['value']->toArray());
                    if(empty($cell['value'])) $cell['value'] = $cell['default_value'];
                }

                // Remplacement de valeurs
                foreach($this->valueReplacements as $replacement)
                {
                    if($cell['value'] === $replacement['value']) $cell['value'] = $replacement['defaultsTo'];
                }

                $spreadsheet->getActiveSheet()->setCellValue($cell['position'], $cell['value']);
                $spreadsheet->getActiveSheet()->getColumnDimension($cell['column'])->setAutoSize(true);
                
                $cell['column']++;
            }

            $cell['row']++;
        }

        return $spreadsheet;
    }

    public function getContactsSpreadsheet(array $contacts, array $fields) : Spreadsheet
    {
        if(empty($contacts)) return new Spreadsheet();

        $columns = $fields;
        if(in_array('profile_types', $fields)) {
            $profileTypesColumnsCount = $this->getProfileTypesColumnsCount($contacts);
            $profileTypesColumns = array_fill(0, $profileTypesColumnsCount - 1, 'profile_types');
            array_splice($columns, array_search('profile_types', $fields), 0, $profileTypesColumns);
        }
       
        $spreadsheet = $this->writeTitleFields($columns);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        
        $cell['default_value'] = 'Aucun(e)';
        $cell['row'] = 2;
        foreach($contacts as $contact)
        {
            if(!($contact instanceof Contact)) throw new Exception('Method ' . __METHOD__ . ' can only be used with array of Contact Entity');
            $cell['column'] = 'A';

            $contactDetails = $contact->getContactDetails();
            $loops = ($contactDetails->count() > 0) ? $contactDetails->count() - 1 : 1;
            for($i = 0; $i <= $loops; $i++) {
                $cell['column'] = 'A';
                foreach($fields as $field)
                {
                    $cell['position'] = $cell['column'] . $cell['row'];
                    $cell['value'] = null;

                    if($propertyAccessor->isReadable($contact, $field)) {
                        $cell['value'] = $propertyAccessor->getValue($contact, $field);
        
                        if($cell['value'] instanceof Collection) {
                            $cell['value'] = implode(', ', $cell['value']->toArray());
                            if(empty($cell['value'])) $cell['value'] = $cell['default_value'];
                        }

                        if($field === 'civility') {
                            $cell['value'] = $this->translator->trans($cell['value']);
                        }

                        if($field === 'personnal_phone_number' && $cell['value'] instanceof PhoneNumber) {
                            $cell['value'] = '+' . $cell['value']->getCountryCode() . $cell['value']->getNationalNumber();
                            
                            $spreadsheet->getActiveSheet()->setCellValueExplicit($cell['position'], $cell['value'], DataType::TYPE_STRING);
                            $spreadsheet->getActiveSheet()->getColumnDimension($cell['column'])->setAutoSize(true);

                            $cell['column']++;

                            continue;
                        }

                        if($field === 'is_receiving_festival_program') {

                            if($cell['value'] === true) {
                                $cell['value'] = str_replace('<br>', ' ', $contact->getFormattedAddress());
                            } else {
                                $cell['value'] = str_replace('<br>', ' ', $contact->getStructureSendingFestivalProgram()?->getFormattedAddress(oneline : true)) ?? 'Aucun.e';
                            }

                            $spreadsheet->getActiveSheet()->setCellValue($cell['position'], $cell['value']);
                            $spreadsheet->getActiveSheet()->getColumnDimension($cell['column'])->setAutoSize(true);

                            $cell['column']++;
                            
                            continue;
                        }
                        
                        if($field === 'profile_types') {
                            $profileTypes = $contact->getProfileTypes()->toArray();
                            for($i = 0; $i <= $profileTypesColumnsCount - 1; $i++)
                            {
                                $cell['position'] = $cell['column'] . $cell['row'];
                                $cell['value'] = (isset($profileTypes[$i])) ? $profileTypes[$i] : 'Aucun(e)';

                                $spreadsheet->getActiveSheet()->setCellValue($cell['position'], $cell['value']);
                                $spreadsheet->getActiveSheet()->getColumnDimension($cell['column'])->setAutoSize(true);
                        
                                $cell['column']++;
                            }

                            continue;
                        }
                    } else {
                        if($contactDetails->count() > 0) {
                            if($field === 'structure') {
                                $contactDetail = $contactDetails->toArray()[$i];
    
                                $cell['value'] = $contactDetail->getStructure()?->__toString();
                            }
    
                            if($field === 'structure_function') {
                                $cell['value'] = $contactDetail->getStructureFunction();
                            }
    
                            if($field === 'professional_email') {
                                $cell['value'] = $contactDetail->getEmail();
                            }
    
                            if($field === 'professionnal_phone_numbers') {
                                $cell['value'] = implode(', ', $contactDetail->getContactDetailPhoneNumbers()->toArray());

                                $spreadsheet->getActiveSheet()->setCellValueExplicit($cell['position'], $cell['value'], DataType::TYPE_STRING);
                                $spreadsheet->getActiveSheet()->getColumnDimension($cell['column'])->setAutoSize(true);

                                $cell['column']++;

                                continue;
                            }
                        }
                    }

                    foreach($this->valueReplacements as $replacement)
                    {
                        if($cell['value'] === $replacement['value']) $cell['value'] = $replacement['defaultsTo'];
                    }  

                    $spreadsheet->getActiveSheet()->setCellValue($cell['position'], $cell['value']);
                    $spreadsheet->getActiveSheet()->getColumnDimension($cell['column'])->setAutoSize(true);
            
                    $cell['column']++;
                    
                }
                $cell['row']++;
            }
        }

        return $spreadsheet;
    }

    /**
     * Retourne le nombre de profils du contact ayant le plus de profils parmi la liste passée en paramètres
     * 
     * Pour un tableau de contacts donné, chaque contact peut avoir un nombre de "profils" associés
     * différents. Afin de pouvoir déterminer combien de colonnes seront dédiées pour afficher tout les 
     * profils, la fonction récupère le contact qui a le plus de profils, et renvoie le nombre de profils
     * de ce dernier.
     * 
     */
    private function getProfileTypesColumnsCount(array $contacts) : int
    {
        $columnsCount = $contacts[0]->getProfileTypes()->count();
        unset($contacts[0]);
        foreach($contacts as $contact)  
        {
            $profileTypesCount = $contact->getProfileTypes()->count();
            if($profileTypesCount > $columnsCount) $columnsCount = $profileTypesCount;
        }

        return $columnsCount;
    }
}