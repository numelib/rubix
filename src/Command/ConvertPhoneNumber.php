<?php

namespace App\Command;

use App\Service\ExcelValueConverter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:convert-phone-number')]
class ConvertPhoneNumber extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ExcelValueConverter $excelValueConverter
    ) {
        parent::__construct('app:convert-phone-number');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->entityManager->getConnection();

        // L'ordre d'execution est important !

        $this->weirdPhoneNumbersToNull($connection, 'structure', 'phone_number');
        $this->weirdPhoneNumbersToNull($connection, 'contact', 'personnal_phone_number');
        $this->weirdPhoneNumbersToNull($connection, 'contact_detail_phone_number', 'phone_number');

        $this->updateContactPersonnalPhoneFormat($connection);
        $this->updateContactDetailPhoneNumberFormat($connection);
        $this->updateStructurePhoneNumberFormat($connection);

        $sql = 'ALTER TABLE contact_detail_phone_number CHANGE `phone_number` `value` VARCHAR(255);';
        $connection->executeQuery($sql);

        $this->updateStructurePhoneNumbers($connection, $input, $output);

        return Command::SUCCESS;
    }

    private function weirdPhoneNumbersToNull(Connection $connection, string $table, string $field) : void
    {
        $sql = 'UPDATE ' . $table . ' SET ' .  $field . ' = NULL WHERE ' . $field . ' = "" OR LENGTH(' . $field . ') < 2';
        $connection->executeQuery($sql);
    }

    /**
     * Transfère les numéros de téléphone des Structure (champ 'phone_number') dans une table dédiée (StructurePhoneNumber)
     * puis, les formatte en numéro de téléphone suivant la norme E.164 et supprime les données "louches" comme certaines
     * données qui semblent vides mais qui ne le sont pas (caractères spéciaux)
     */
    private function updateStructurePhoneNumbers(Connection $connection, InputInterface $input, OutputInterface $output) : void
    {
        $connection = $this->entityManager->getConnection();

        // Obliger de vérifier la longueur des chaines de caractères car certaines chaines vides contiennent des caractères qui semblent invisibles parfois.
        $sql = "
            SELECT id AS structure_id, phone_number AS value FROM structure WHERE phone_number IS NOT NULL AND TRIM(phone_number) != '' AND LENGTH(phone_number) > 1;
        ";

        $result = $connection->executeQuery($sql);
        $results = $result->fetchAllAssociative();

        $command = new ArrayInput(['command' => 'doctrine:schema:update', '--force' => true]);
        $this->getApplication()->doRun($command, $output);

        for($i = 0; $i < count($results); $i++)
        {
            $numbers = $this->excelValueConverter->toPhoneNumbers($results[$i]['value'], asObject : false);
            foreach($numbers as $number)
            {
                $criteria = ['structure_id' => $results[$i]['structure_id'], 'value' => $number];
                $this->findOrCreateRow('structure_phone_number', $criteria, persist : true);
            }
        }
    }

    private function findOrCreateRow(string $table, array $criteria, bool $persist = false) : ?string
    {
        $connection = $this->entityManager->getConnection();

        $fields = array_keys($criteria);
        $parameters = array_map(fn($field) => sprintf(':%s', $field), $fields);

        $where = array_map(fn($field, $parameter) => sprintf('%s = %s', $field, $parameter), $fields, $parameters);
        $sql = 'SELECT * FROM ' . $table . ' WHERE ' . implode(' AND ', $where) . ';';

        $result = true;

        $stmt = $connection->executeQuery($sql, $criteria);
        $result = $stmt->fetchOne();

        if($result === false) {
            $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $parameters) . ');';

            if($persist === true) {
                $connection->executeQuery($sql, $criteria);
            }

            return $sql;
        }

        return null;
    }

    /**
     * Modifie le format des numéros de téléphone des contacts existants (personnal_phone_number)
     * pour qu'ils respectent la norme E.164
     */
    private function updateContactPersonnalPhoneFormat(Connection $connection) : void
    {
        $sql = "
            UPDATE `contact` SET `personnal_phone_number` = CASE
                WHEN `personnal_phone_number` NOT LIKE '+%' THEN REPLACE(REPLACE(CONCAT('+33', `personnal_phone_number`), ' ', ''), '.', '')
                ELSE REPLACE(`personnal_phone_number`, ' ', '')
            END
            WHERE `personnal_phone_number` IS NOT NULL;
        ";

        $connection->executeQuery($sql);
    }

    /**
     * Modifie le format des numéros de téléphone des coordonnées du contact existants (contact_detail_phone_number)
     * pour qu'ils respectent la norme E.164
     */
    private function updateContactDetailPhoneNumberFormat(Connection $connection) : void
    {
        $sql = "
            UPDATE `contact_detail_phone_number` SET `phone_number` = CASE
                WHEN `code` IS NOT NULL THEN REPLACE(REPLACE(CONCAT('+', `code`, `phone_number`), ' ', ''), '.', '')
                ELSE REPLACE(CONCAT('+', 33, `phone_number`), ' ', '')
            END
            WHERE `phone_number` not like '+%';
        ";

        $connection->executeQuery($sql);
    }
    
     /**
     * Modifie le format des numéros de téléphone des coordonnées des structures existantes (contact_detail_phone_number)
     * pour qu'ils respectent la norme E.164
     */
    private function updateStructurePhoneNumberFormat(Connection $connection) : void
    {
        $sql = "
            UPDATE `structure` SET `phone_number` = CASE
                WHEN `phone_number` NOT LIKE '+%' THEN REPLACE(REPLACE(CONCAT('+33', `phone_number`), ' ', ''), '.', '')
                ELSE REPLACE(`phone_number`, ' ', '')
            END
            WHERE `phone_number` IS NOT NULL;
        ";

        $connection->executeQuery($sql);
    }
}