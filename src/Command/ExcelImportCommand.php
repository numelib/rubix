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
use App\Entity\StructurePhoneNumber;
use App\Entity\StructureTypeSpecialization;
use App\Service\ExcelValueConverter;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumber;
use Doctrine\ORM\Mapping\Entity;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use SplFixedArray;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Throwable;
use libphonenumber\PhoneNumberUtil;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:excel-import')]
class ExcelImportCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PhoneNumberUtil $phoneNumberUtil,
        private readonly ExcelValueConverter $excelValueConverter
    ) {
        parent::__construct('app:excel-import');
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
            'structure_phone_number',
        ];

        $truncateStatements = array_map(fn($tableName) => 'TRUNCATE ' . $tableName . ';', $truncateTables);
        $sql = 'START TRANSACTION;SET FOREIGN_KEY_CHECKS=0; ' . implode(' ', $truncateStatements) . ' SET FOREIGN_KEY_CHECKS=1; COMMIT;';

        $connection->executeQuery($sql);

        $importCallback = $this->getImportStructuresCallback();
        $this->importExcel('193soleil-dataset-structures', 'A3', 'AG1516', Structure::class, $importCallback);

        $importCallback = $this->getImportContactsCallback();
        $this->importExcel('193soleil-dataset-contacts', 'A3', 'AS1130', Contact::class, $importCallback);

        return Command::SUCCESS;
    }

    /**
     * @param string $filename - Le nom du fichier présent dans le dossier "public/spreadsheets", sans l'extension.
     * @param string $startCell - La coordonée de la cellule de départ du fichier (ex: A2).
     * @param string $endCell - La coordonée de la cellule de fin du fichier (ex: F32).
     * @param string $entityFqcn - Le FQCN de l'entité que vous souhaitez persister en base de données.
     * @param callable(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, int $col, int $row, \App\Entity $entity) : void $callback - La fonction de rappel appliquée pour chaque cellule.
     */
    public function importExcel(string $filename, string $startCell, string $endCell, string $entityFqcn, callable $callback) : void
    {
        $inputFileName = './public/spreadsheets/' . $filename . '.xlsx';
        $spreadsheet = IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();

        [$startCol, $startRow] = $this->getCoordinatedIndexes($startCell);
        [$endCol, $endRow] = $this->getCoordinatedIndexes($endCell);

        if(!class_exists($entityFqcn)) {
            throw new Exception('Class : ' . $entityFqcn . ' does not exists');
        }

        $unassignedValues = [];
        for ($row = $startRow; $row <= $endRow; ++$row) {
            $entity = new $entityFqcn();
            for ($col = $startCol; $col <= $endCol; ++$col) {
                $unassignedValue = $callback($worksheet, $col, $row, $entity);

                if(null !== $unassignedValue) $unassignedValues[] = $unassignedValue;
            }

            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        }

        // DEBUG
        echo 'Unassigned values : ' . PHP_EOL;
        for($i = 0; $i < count($unassignedValues); $i++)
        {
            echo '[champ : ' . $unassignedValues[$i][0] . ', valeur : ' . $unassignedValues[$i][1] . ']' . PHP_EOL;
        }
    }

    private function getCoordinatedIndexes(string $coordinates) : array
    {
        preg_match('/^([A-Z]+)([0-9]+)/', $coordinates, $matches);
        if(count($matches) !== 3) {
            throw new Exception('Argument #1 must match this expression : /^([A-Z]+)([0-9]+)/');
        }

        $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($matches[1]);

        return [$column, (int) $matches[2]];
    }

    private function convertCellValueTo(?string $value, ?string $castType) : string|bool|null
    {       
        if($castType === 'bool') {
            return match($value) {
                'Oui' => true,
                'Non' => false,
                'non' => false,
                'oui' => true,
                'x' => false,
                '' => false,
                '...' => false,
                null => false,
            };
        }

        if($castType === 'int') {
            return (intval($value) !== 0) ? intval($value) : $value ;
        }

        return match($value) {
            '' => null,
            '...' => null,
            'x' => null,
            'Madame' => 'F',
            'Monsieur' => 'M',
            default => $value
        };
    }

    private function getMainTypeOfProperty(ReflectionProperty $property) : ?string
    {
        $reflectionType = $property->getType();

        if($reflectionType instanceof ReflectionNamedType && $reflectionType->isBuiltIn()) {
            return $reflectionType->getName();
        }

        if($reflectionType instanceof ReflectionUnionType && $reflectionType?->getTypes()[1]->isBuiltIn()) {
            return $reflectionType?->getTypes()[1]->getName();
        }

        return null;
    }

    private function getImportStructuresCallback() : callable
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $structureReflection = new ReflectionClass(Structure::class);

        return function(Worksheet $worksheet, int $col, int $row, Structure $structure) use ($propertyAccessor, $structureReflection) : ?array {
            $colHeader = trim($worksheet->getCell([$col, 2])->getValue());
            $value = trim($worksheet->getCell([$col, $row])->getValue());
            $value = $this->convertCellValueTo($value, 'string');

            $propertyName = match($colHeader) {
                'Nom de la structure' => 'name',
                'Email générique' => 'email',
                'Téléphone générique' => 'phone_number',
                'Rue' => 'address_street',
                'Complément' => 'address_adition',
                'Code postal' => 'address_code',
                'Ville' => 'address_city',
                'Pays' => 'address_country',
                'Site internet' => 'website',
                'Organisation d’un festival' => 'is_festival_organizer',
                'Notes de structure' => 'structure_notes',
                'Envoi programme du festival' => 'is_receiving_festival_program',
                'Mail de réception' => 'newsletter_email',
                'Notes de communication' => 'communication_notes',
                'Partenaire du festival' => 'is_festival_partner',
                'Compagnie programmée dans le festival' => 'is_company_programmed_in_festival',
                'Structure partenaire ateliers' => 'is_workshop_partner',
                'Notes de l’association' => 'organization_notes',
                default => null
            };

            $property = ($propertyName) ? $structureReflection->getProperty($propertyName) : null;
            if($property !== null && ($type = $this->getMainTypeOfProperty($property)) !== null && $propertyAccessor->isWritable($structure, $property->getName())) {
                $value = $this->convertCellValueTo($value, $type);

                if($value !== null) {
                    $propertyAccessor->setValue($structure, $property->getName(), $value);
                    return null;
                }
            }

            switch(true) {
                case $propertyName === 'phone_number' && $value !== null:
                    $numbers = $this->excelValueConverter->toPhoneNumbers($value);
                    for($i = 0; $i < count($numbers); $i++)
                    {
                        $phoneNumber = $this->findOrCreateEntity(StructurePhoneNumber::class, $propertyAccessor, 'value', $numbers[$i]);
                        $structure->addPhoneNumber($phoneNumber);
                    }
                    break;
                case $colHeader === 'Type' && $value !== null: 
                    $type = $this->findOrCreateEntity(StructureType::class, $propertyAccessor, 'name', $value);
                    $structure->setStructureType($type);
                    break;

                case str_starts_with($colHeader, 'Sous type') && $value !== null: 
                    $specialization = $this->findOrCreateEntity(StructureTypeSpecialization::class, $propertyAccessor, 'name', $value);
                    $structure->addStructureTypeSpecialization($specialization);
                    break;

                case str_starts_with($colHeader, 'Discipline') && $value !== null: 
                    $discipline = $this->findOrCreateEntity(Discipline::class, $propertyAccessor, 'name', $value);
                    $structure->addDiscipline($discipline);
                    break;

                case str_starts_with($colHeader, 'Parc') && $value !== null: 
                    $parc = $this->findOrCreateEntity(Parc::class, $propertyAccessor, 'name', $value);
                    $structure->addNearParc($parc);
                    break;

                case str_starts_with($colHeader, 'Envoi newsletter') && $value !== null: 
                    $newsletter = $this->findOrCreateEntity(NewsletterType::class, $propertyAccessor, 'name', $value);
                    $structure->addNewsletterType($newsletter);
                    break;

                default :
                    if($value !== null) {
                        return [$colHeader, $value];
                    }
            }

            return null;
        };
    }

    private function getImportContactsCallback() : callable
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $contactReflection = new ReflectionClass(Contact::class);

        return function(Worksheet $worksheet, int $col, int $row, Contact $contact) use ($propertyAccessor, $contactReflection) : ?array {
            $colHeader = trim($worksheet->getCell([$col, 2])->getValue());
            $value = trim($worksheet->getCell([$col, $row])->getValue());
            $value = $this->convertCellValueTo($value, 'string');

            $propertyName = match($colHeader) {
                'Civilité' => 'civility',
                'Prénom' => 'firstname',
                'Nom' => 'lastname',
                'Site internet' => 'website',
                'Rue' => 'address_street',
                'Artiste ateliers' => 'is_workshop_artist',
                'Intervenant de formation' => 'is_formation_speaker',
                'Notes professionnelles' => 'professional_notes',
                'Email personnel' => 'personnal_email',
                'Numéro de téléphone personnel' => 'personnal_phone_number',
                'Rue' => 'address_street',
                'Complément d’adresse' => 'address_adition',
                'Code postal' => 'address_code',
                'Ville' => 'address_city',
                'Pays' => 'address_country',
                'Notes personnelles' => 'personnal_notes',
                'Mail de réception' => 'newsletter_email',
                'Envoi programme festival' => 'is_receiving_festival_program',
                'Bénévole au festival' => 'is_festival_participant',
                'Membre du CA' => 'is_board_of_directors_member',
                'Adhérent' => 'is_organization_participant',
                default => null
            };

            $property = ($propertyName) ? $contactReflection->getProperty($propertyName) : null;
            if($property !== null && ($type = $this->getMainTypeOfProperty($property)) !== null && $propertyAccessor->isWritable($contact, $property->getName())) {
                $value = $this->convertCellValueTo($value, $type);

                if($value !== null) {
                    $propertyAccessor->setValue($contact, $property->getName(), $value);
                    return null;
                }
            }

            switch(true) {
                case str_starts_with($colHeader, 'Numéro de téléphone personnel') && $value !== null : 
                    $numbers = $this->excelValueConverter->toPhoneNumbers($value);
                    if(count($numbers) > 1) throw new Exception('Contact should not have mutliple phone numbers : ' . implode(', ', $numbers));
                    foreach($numbers as $number)
                    {
                        $contact->setPersonnalPhoneNumber($number);
                    }
                    break;
                case str_starts_with($colHeader, 'Email') : 
                    $contactDetail = (new ContactDetail())->setEmail($value);
                    $contact->addContactDetail($contactDetail);
                    break;

                case str_starts_with($colHeader, 'Tél fixe') && $value !== null:
                case str_starts_with($colHeader, 'Tél mobile') && $value !== null:
                    /** @var \App\Entity\ContactDetail */
                    $contactDetail = $contact->getContactDetails()->last();
                    $numbers = $this->excelValueConverter->toPhoneNumbers($value);
                    foreach($numbers as $number)
                    {
                        $phoneNumber = (new ContactDetailPhoneNumber())->setPhoneNumber($number);
                        $contactDetail->addContactDetailPhoneNumber($phoneNumber);
                    }
                    break;

                case str_starts_with($colHeader, 'Fonction') && $value !== null: 
                    /** @var \App\Entity\ContactDetail */
                    $contactDetail = $contact->getContactDetails()->last();
                    $contactDetail->setStructureFunction($value);
                    break;

                case str_starts_with($colHeader, 'Structure') && $value !== null: 
                    /** @var \App\Entity\ContactDetail */
                    $contactDetail = $contact->getContactDetails()->last();
                    $structure = ($this->findOrCreateEntity(Structure::class, $propertyAccessor, 'name', $value, false))
                        ->setIsFestivalOrganizer(false)
                        ->setIsCompanyProgrammedInFestival(false)
                        ->setIsWorkshopPartner(false)
                        ->setIsReceivingFestivalProgram(false)
                        ->setIsFestivalPartner(false);

                    $this->entityManager->persist($structure);

                    $contactDetail->setStructure($structure);
                    break;

                case str_starts_with($colHeader, 'Profil') && $value !== null:
                    $profileType = $this->findOrCreateEntity(ProfileType::class, $propertyAccessor, 'name', $value);
                    $contact->addProfileType($profileType);
                    break;

                case str_starts_with($colHeader, 'Discipline') && $value !== null:
                    $discipline = $this->findOrCreateEntity(Discipline::class, $propertyAccessor, 'name', $value);
                    $contact->addDiscipline($discipline);
                    break;

                case str_starts_with($colHeader, 'Participant.e formation') && $value !== null:
                    $formationParticipantType = $this->findOrCreateEntity(FormationParticipantType::class, $propertyAccessor, 'name', $value);
                    $contact->addFormationParticipantType($formationParticipantType);
                    break;

                case str_starts_with($colHeader, 'Envoi newsletter') && $value !== null:
                    $newsletterType = $this->findOrCreateEntity(NewsletterType::class, $propertyAccessor, 'name', $value);
                    $contact->addNewsletterType($newsletterType);
                    break;

                default :
                    if($value !== null) {
                        return [$colHeader, $value];
                    };
            }

            return null;
        };
    } 

    private function findOrCreateEntity(string $entityFqcn, PropertyAccessor $propertyAccessor, string $field, mixed $value, bool $persistInDb = true) : object
    {
        $entity = $this->entityManager->getRepository($entityFqcn)->findOneBy([$field => $value]);

        if($entity === null) {
            $entity = new $entityFqcn();
            $propertyAccessor->setValue($entity, $field, $value);

            dump('New entity (' . $entityFqcn . ') instantiated with value ' . $value);

            if($persistInDb) {
                $this->entityManager->persist($entity);
            }
        }

        return $entity;
    }
}