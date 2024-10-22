<?php

namespace App\Command;

use App\Entity\Contact;
use App\Entity\ContactDetail;
use App\Entity\ContactDetailPhoneNumber;
use App\Entity\Discipline;
use App\Entity\FormationParticipantType;
use App\Entity\NewsletterType;
use App\Entity\Parc;
use App\Entity\ProfileType;
use App\Entity\Structure;
use App\Entity\StructureType;
use App\Entity\StructureTypeSpecialization;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Throwable;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:excel-import')]
class ExcelImportCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct('app:excel-import');
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->entityManager->getConnection();

        $truncateTables = [
            'contact_newsletter_type',
            'contact_detail_phone_number',
            'contact_detail',
            'contact_disciplines',
            'contact_profile_types',
            'formation_participant_type_contact',
            'contact',
            'structure',
            'structure_discipline',
            'structure_newsletter_type',
            'structure_parc',
            'structure_structure_type_specialization',
        ];

        $truncateStatements = array_map(fn($tableName) => 'TRUNCATE ' . $tableName . ';', $truncateTables);
        $sql = 'START TRANSACTION;SET FOREIGN_KEY_CHECKS=0; ' . implode(' ', $truncateStatements) . ' SET FOREIGN_KEY_CHECKS=1; COMMIT;';

        $connection->executeQuery($sql);

        $this->getStructuresFromExcel();
        $this->getContactsFromExcel();

        return Command::SUCCESS;
    }

    private function getStructuresFromExcel() : array
    {
        $filesystem = new Filesystem();
        $inputFileName = './public/spreadsheets/193soleil-dataset-structures.xlsx';

        $fields = [
            'name',
            'email',
            'phone_number',
            'address_street',
            'address_adition',
            'address_code',
            'address_city',
            'address_country',
            'website',
            'structure_type.name',
            'structure_type_specializations[0].name',
            'structure_type_specializations[1].name',
            'structure_type_specializations[2].name',
            'discipline[0].name',
            'discipline[1].name',
            'discipline[2].name',
            'is_festival_organizer',
            'structure_notes',
            'is_receiving_festival_program',
            'near_parcs[0].name',
            'near_parcs[1].name',
            'near_parcs[2].name',
            'newsletter_types[0].name',
            'newsletter_email',
            'newsletter_types[1].name',
            'newsletter_email(duplicate)',
            'newsletter_types[2].name',
            'newsletter_email(duplicate)',
            'communication_notes',
            'is_festival_partner',
            'is_company_programmed_in_festival',
            'is_workshop_partner',
            'organization_notes'
        ];

        // Utilisé pour retrouver le champ de l'entité associé à une colonne
        $columnMapping = [];
        $column = 'A';
        $index = '0';
        for($index; $index <= count($fields) - 1; $index++)
        {
            $columnMapping[$index] = $column;
            $column++;
        }

        $spreadsheet = IOFactory::load($inputFileName);

        $rows = $spreadsheet->getActiveSheet()->rangeToArray(
            'A3:AG1516',     // The worksheet range that we want to retrieve
            null,        // Value that should be returned for empty cells
            false,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
            false,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
            TRUE         // Should the array be indexed by cell row and cell column
        );

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $newParcs = [];
        $newStructureTypes = [];
        $structures = [];
        foreach($rows as $rowIndex => $columns)
        {
            $structure = new Structure();

            for($index = 0; $index <= count($columns) - 1; $index++)
            {
                $column = $columnMapping[$index];
                $value = $columns[$column];
                $field = $fields[$index];

                $value = (str_starts_with($field, 'is_')) ? $this->getBooleanValueOfCell($value) : $this->getStringValueOfCell($value);

                if($propertyAccessor->isWritable($structure, $field)) {
                    $value = match($field) {
                        'address_code' => (int) $value,
                        default => $value,
                    };

                    if($value === null) continue;

                    $propertyAccessor->setValue($structure, $field, $value);
                } else {

                    if(str_starts_with($field,'discipline')) {
                        $value = match($value) {
                            'x' => null,
                            default => $value,
                        };

                        if($value !== null) {
                            $discipline = $this->entityManager->getRepository(Discipline::class)->findOneBy(['name' => $value]);

                            ($discipline !== null) ? $structure->addDiscipline($discipline) : dump('Innexistant discipline : ' . $value);
                        }
                       
                        continue;
                    }

                    if(str_starts_with($field, 'near_parcs')) {
                        $value = $this->getStringValueOfCell($value);

                        if($value === null) continue;

                        $nearParc = $this->entityManager->getRepository(Parc::class)->findOneBy(['name' => $value]);

                        if($nearParc !== null) {
                            $structure->addNearParc($nearParc);
                        } else {
                            dump('Innexistant parc : ' . $value);
                        
                            if(!in_array($value, $newParcs)) {
                                $newParcs[] = $value;

                                $parc = (new Parc())->setName($value);
                                $this->entityManager->persist($parc);
                                $structure->addNearParc($parc);
                            }
                        }
                    }

                    if($field === 'structure_type.name') {
                        $value = match($value) {
                            'x' => null,
                            default => $value,
                        };

                        if($value === null) continue;

                        $structureType = $this->entityManager->getRepository(StructureType::class)->findOneBy(['name' => $value]);

                        if($structureType !== null) {
                            $structure->setStructureType($structureType);
                        } else {
                            dump('Innexistant structure type : ' . $value);
                        }
                        continue;
                    }

                    if(str_starts_with($field, 'structure_type_specializations')) {
                        for($i = 0; $i <= 2; $i++)
                        {
                            $column = $columnMapping[$index];
                            $value = $columns[$column];

                            $value = trim($value);

                            $value = match($value) {
                                'x' => null,
                                '' => null,
                                default => ucwords($value),
                            };           

                            if($value === null || empty($value)) continue;

                            $structureTypeSpecialization = $this->entityManager->getRepository(StructureTypeSpecialization::class)->findOneBy(['name' => $value]);

                            if($structureTypeSpecialization !== null) {
                                $structure->addStructureTypeSpecialization($structureTypeSpecialization);
                            } else {
                                dump('Innexistant structure type specialization : ' . $value);

                                if(!in_array($value, $newStructureTypes)) {
                                    $newStructureTypes[] = $value;

                                    $structureTypeSpecialization = (new StructureTypeSpecialization())->setName($value);
                                    $structure->addStructureTypeSpecialization($structureTypeSpecialization);
                                }
                            }

                            $index++;
                        }
                    }

                    if(str_starts_with($field, 'newsletter_types')) {
                        $column = $columnMapping[$index + 1];
                        $value = $columns[$column];

                        $value = trim($value);

                        $value = match($value) {
                            'x' => null,
                            '' => null,
                            default => $value,
                        };

                        if($value === null || empty($value)) continue;

                        $structure->setNewsletterEmail($value);

                        for($i = 0; $i <= 2; $i++)
                        {
                            $column = $columnMapping[$index];
                            $value = $columns[$column];

                            $value = match($value) {
                                'x' => null,
                                default => $value,
                            };

                            if($value === null) continue;
                            

                            $newsletterType = $this->entityManager->getRepository(NewsletterType::class)->findOneBy(['name' => $value]);

                            if($newsletterType !== null) {
                                $structure->addNewsletterType($newsletterType);
                            } else {
                                dump('Innexistant newsletter type : ' . $value);
                            }

                            $index = $index + 2;
                        }

                        $index = $index - 2;
                    }
                }
            }

            $structures[] = $structure;
            if(isset($structureNames[$structure->getName()])) {
                $duplicatedNames[] = $structure->getName();
            } else {
                $structureNames[$structure->getName()] = $structure->getName();
            }

            $this->entityManager->persist($structure);
        }

        $this->entityManager->flush();

        dump('Structures : ' . count($structures));
        dump('Structures dupliquées : ' . count($duplicatedNames));
        dump('Structures uniques : ' . count($structures) - count($duplicatedNames));
        
        $spreadsheetWarningFilepath = 'public/spreadsheets/spreedsheet-warnings.txt';
        if($filesystem->exists($spreadsheetWarningFilepath)) {
            $filesystem->remove($spreadsheetWarningFilepath);
        }
        $filesystem->appendToFile($spreadsheetWarningFilepath, 'Sous types de structures renseignés dans le fichier Excel "Structures" mais n\'étant pas lié à un type de Structure particulier : ');
        $filesystem->appendToFile($spreadsheetWarningFilepath, "\n\n");
        foreach($newStructureTypes as $structureType)
        {
            $filesystem->appendToFile($spreadsheetWarningFilepath, ' - ' . $structureType);
            $filesystem->appendToFile($spreadsheetWarningFilepath, "\n");
        }
        $filesystem->appendToFile($spreadsheetWarningFilepath, "\n");
        $filesystem->appendToFile($spreadsheetWarningFilepath, 'NB : ces sous-types ont été créés et rajoutés dans la base de données malgré tout.');

        return $structures;
    }

    private function getContactsFromExcel() : array
    {
        $filesystem = new Filesystem();
        $inputFileName = './public/spreadsheets/193soleil-dataset-contacts.xlsx';

        $fields = [
            'civility',
            'firstname',
            'lastname',
            'website',
            'contact_details[0].email',
            'contact_details[0].contact_details_phone_numbers[0].phone_number',
            'contact_details[0].contact_details_phone_numbers[1].phone_number',
            'contact_details[0].structure_function',
            'contact_details[0].structure',
            'contact_details[1].email',
            'contact_details[1].contact_details_phone_numbers[0].phone_number',
            'contact_details[1].contact_details_phone_numbers[1].phone_number',
            'contact_details[1].structure_function',
            'contact_details[1].structure',
            'profile_types[0].name',
            'profile_types[1].name',
            'profile_types[2].name',
            'is_workshop_artist',
            'disciplines[0].name',
            'disciplines[1].name',
            'disciplines[2].name',
            'formationParticipantTypes[0].name',
            'formationParticipantTypes[1].name',
            'formationParticipantTypes[2].name',
            'is_formation_speaker',
            'professional_notes',
            'personnal_email',
            'personnal_phone_number',
            'address_street',
            'address_adition',
            'address_code',
            'address_city',
            'address_country',
            'personnal_notes',
            'newsletter_types[0].name',
            'newsletter_email',
            'newsletter_types[1].name',
            'newsletter_email(duplicate)',
            'newsletter_types[2].name',
            'newsletter_email(duplicate)',
            'is_receiving_festival_program',
            'communication_notes',
            'is_festival_participant',
            'is_board_of_directors_member',
            'is_organization_participant',
        ];

        $columnsNames = [];
        $column = 'A';
        $index = '0';
        for($index; $index <= count($fields) - 1; $index++)
        {
            $columnsNames[$index] = $column;
            $column++;
        }

        $spreadsheet = IOFactory::load($inputFileName);

        $rows = $spreadsheet->getActiveSheet()->rangeToArray(
            'A3:AS1131',     // The worksheet range that we want to retrieve
            null,        // Value that should be returned for empty cells
            false,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
            false,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
            TRUE         // Should the array be indexed by cell row and cell column
        );

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $innexistantStructures = [];
        $duplicatedStructures = [];
        $emptyContactDetails = [];
        $contacts = [];
        foreach($rows as $columns)
        {
            $contact = new Contact();

            for($index = 0; $index <= count($columns) - 1; $index++)
            {
                $getColumnNameAtColIndex = function(int $index) use ($columnsNames) { return $columnsNames[$index]; };
                $getCellValueAtColIndex = function(int $index) use ($columns, $getColumnNameAtColIndex) { return $columns[$getColumnNameAtColIndex($index)]; };
                $column = $columnsNames[$index];
                $value = $getCellValueAtColIndex($index);
                $field = $fields[$index];

                $value = (str_starts_with($field, 'is_')) ? $this->getBooleanValueOfCell($value) : $this->getStringValueOfCell($value);

                if($propertyAccessor->isWritable($contact, $field)) {
                    $value = match($field) {
                        'address_code' => ($value !== null) ? (int) $value : null,
                        'firstname' => ($value === null) ? '' : $value,
                        default => $value,
                    };          
                    
                    if($field === 'civility') {
                        $value = match($value) {
                            'madame' => 'F',
                            'Madame' => 'F',
                            'monsieur' => 'M',
                            'Monsieur' => 'M',
                            default => dump('Undefined civility : ' . $value),
                        };
                    }

                    if($value === null) continue;

                    $propertyAccessor->setValue($contact, $field, $value);
                } else {

                    // Coordonnées contact
                    if(str_starts_with($field, 'contact_details')) {
                        $contactDetail = new ContactDetail();
    
                        // Email
                        if($value !== null) $contactDetail->setEmail($value);
                        $index++;
                        
                        // Numéros de téléphone
                        for($i = 0; $i < 2; $i++) {
                            $value = $getCellValueAtColIndex($index);
                            $phoneNumber = $this->getStringValueOfCell($value);

                            if($phoneNumber !== null) {
                                $contactDetailPhoneNumber = (new ContactDetailPhoneNumber())->setPhoneNumber($phoneNumber);
                                $contactDetail->addContactDetailPhoneNumber($contactDetailPhoneNumber);
                            }
    
                            $index++;
                        }

                        // Structure function
                        $value = $getCellValueAtColIndex($index);
                        $structureFunction = $this->getStringValueOfCell($value);

                        if($structureFunction !== null) {
                            $contactDetail->setStructureFunction($structureFunction);
                        }
                        $index++;
                        
                        // Structure 

                        // A définir avec le tableau excel des structures
                        $value = $getCellValueAtColIndex($index);
                        $structureName = $this->getStringValueOfCell($value);


                        $structures = $this->entityManager->getRepository(Structure::class)->findBy(['name' => $structureName]);

                        if($structureName !== null) {
                            if(count($structures) === 0 && !in_array($structureName, $innexistantStructures)) {
                                $innexistantStructures[] = $structureName;
                                $structure = (new Structure())
                                    ->setName($structureName)
                                    ->setIsFestivalOrganizer(false)
                                    ->setIsCompanyProgrammedInFestival(false)
                                    ->setIsWorkshopPartner(false)
                                    ->setIsReceivingFestivalProgram(false)
                                    ->setIsFestivalPartner(false);
                                $contactDetail->setStructure($structure);
                            } else if(count($structures) > 1) {
                                $duplicatedStructures[] = $structureName;
                            } else if(count($structures) === 1) {
                                $contactDetail->setStructure($structures[0]);
                            }
                        }

                        if(
                            $contactDetail->getEmail() === null &&
                            $contactDetail->getContactDetailPhoneNumbers()->count() === 0 &&
                            $contactDetail->getStructureFunction() === null &&
                            $contactDetail->getStructure() === null
                        ) {
                            $emptyContactDetails[] = $contact->getFirstname() . ' ' . $contact->getLastname();
                        } else {
                            $contact->addContactDetail($contactDetail);
                        }

                        continue;
                    }

                    if(str_starts_with($field, 'profile_types')) {
                        $value = match($value) {
                            'Artistes' => 'Artiste',
                            default => $value,
                        };

                        if($value !== null) {
                            $profileType = $this->entityManager->getRepository(ProfileType::class)->findOneBy(['name' => $value]);
                        
                            ($profileType !== null) ? $contact->addProfileType($profileType) : dump('Innexistant profile type : ' . $value);
                        }
                       
                        continue;
                    }


                    if(str_starts_with($field, 'disciplines')) {
                        $profileType = $this->entityManager->getRepository(ProfileType::class)->findOneBy(['name' => 'Artiste']);
                        $discipline = $this->entityManager->getRepository(Discipline::class)->findOneBy(['name' => $value]);

                        if($value !== null) {
                            ($discipline !== null) ? $contact->addDiscipline($discipline) : dump('Innexistant discipline : ' . $value);
                        }

                        continue;
                    }

                    if(str_starts_with($field, 'formationParticipantTypes')) {
                        if($value === null) continue;

                        $formationParticipantType = $this->entityManager->getRepository(FormationParticipantType::class)->findOneBy(['name' => $value]);
                        if($formationParticipantType !== null) {
                            $contact->addFormationParticipantType($formationParticipantType);
                        } else {
                            $formationParticipantType = (new FormationParticipantType())->setName($value);
                            $contact->addFormationParticipantType($formationParticipantType);
                        }

                        continue;
                    }


                    if(str_starts_with($field, 'newsletter_types[0]') || str_starts_with($field, 'newsletter_types[1]')) {
                        $newsletterEmail = $getCellValueAtColIndex($index + 1);
                        $newsletterEmail = $this->getStringValueOfCell($newsletterEmail);
                        $contact->setNewsletterEmail($newsletterEmail);

                        for($i = 0; $i <= 2; $i++)
                        {
                            $value = $getCellValueAtColIndex($index);
                            $newsletterTypeName = $this->getStringValueOfCell($value);

                            if($newsletterTypeName !== null) {
                                $newsletterType = $this->entityManager->getRepository(NewsletterType::class)->findOneBy(['name' => $newsletterTypeName]);
                        
                                ($newsletterType !== null) ? $contact->addNewsletterType($newsletterType) : dump('Innexistant newsletter type : ' . $newsletterTypeName);
                            }

                            $index = $index + 2;
                        }

                        $index--;
                        
                        continue;
                    }                    
                }
            }

            $contacts[] = $contact;

            $this->entityManager->persist($contact);
        }

        $this->entityManager->flush();

        dump('Contacts dont une coordonnées est entièrement vide : ' . count($emptyContactDetails));
        dump('Structures innexistantes et crées à la volée : ' . count($innexistantStructures));
        dump('Structures dont le nom a été référencé plusieurs fois dans le fichier Structures : ' . count($duplicatedStructures));

        $spreadsheetWarningFilepath = 'public/spreadsheets/spreedsheet-warnings.txt';
        $filesystem->appendToFile($spreadsheetWarningFilepath, "\n\n");
        $filesystem->appendToFile($spreadsheetWarningFilepath, 'Export fichier "Contacts" : Structures dont le nom est dupliqué dans la base de données (impossible de savoir à quelle structure le contact est relié) : ');
        foreach($duplicatedStructures as $structureName)
        {
            $filesystem->appendToFile($spreadsheetWarningFilepath, ' - ' . $structureName);
            $filesystem->appendToFile($spreadsheetWarningFilepath, "\n");
        } 
        $filesystem->appendToFile($spreadsheetWarningFilepath, 'Export fichier "Contacts" : Structures dont le nom indiqué ne fait référence à aucune Structure présente dans le fichier "Structures" : ');
        $filesystem->appendToFile($spreadsheetWarningFilepath, 'NB : ces structures ont été créés à la volée et ont été liées aux contacts. Vous pourrez donc modifier ces structures directement depuis l\'application pour renseigner les données manquantes.');
        $filesystem->appendToFile($spreadsheetWarningFilepath, "\n\n");
        foreach($innexistantStructures as $structureName)
        {
            $filesystem->appendToFile($spreadsheetWarningFilepath, ' - ' . $structureName);
            $filesystem->appendToFile($spreadsheetWarningFilepath, "\n");
        } 
        return $contacts;
    }


    public function getBooleanValueOfCell(?string $cell) : bool
    {
        $value = trim($cell);
        return match($value) {
            'Oui' => true,
            'Non' => false,
            'non' => false,
            'oui' => true,
            'x' => false,
            '' => false,
            null => false,
        };
    }

    public function getStringValueOfCell(?string $cell) : ?string
    {
        $value = trim($cell);
        return match($value) {
            '' => null,
            'x' => null,
            default => $value,
        };
    }
}