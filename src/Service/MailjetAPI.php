<?php

namespace App\Service;

use App\Dto\MailjetContactDto;
use App\Enums\MailjetAction;
use App\Enums\NewsletterType as EnumsNewsletterType;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\CurlHttpClient;

class MailjetAPI
{
    private CurlHttpClient $client;

    public function __construct(ParameterBagInterface $params)
    {
        $this->initClient($params);
    }

    public function areContactListsIdsDefined() : bool
    {
        return (!in_array(null, EnumsNewsletterType::contactListIds()));
    }

    public function areCredentialsDefined() : bool
    {
        return (isset($_ENV['MAILJET_KEY'], $_ENV['MAILJET_SECRET']));
    }
    
    private function initClient(ParameterBagInterface $params) : void
    {
        $this->client = new CurlHttpClient();
    }

    private function refuseMethodCall(string $method) : void
    {
        throw new Exception('Cannot use method : ' . $method . ' because API key/secret have not been defined in environement variables. Please add your API credentials in a "MAILJET_KEY" and "MAILJET_SECRET" environement variable');
    }

    public function getContactByEmail(string $email) : ?MailjetContactDto
    {
        if(!$this->areCredentialsDefined()) $this->refuseMethodCall(__METHOD__);

        $response = $this->client->request(
            'GET',
            'https://api.mailjet.com/v3/REST/contact/' . urlencode($email),
            ['auth_basic' => [$_ENV['MAILJET_KEY'], $_ENV['MAILJET_SECRET']]],
        );

        $statusCode = $response->getStatusCode();

        if($statusCode === 200 && $response->toArray()['Count'] > 0) {
            $contact = $response->toArray()['Data'][0];
            return new MailjetContactDto($contact['Email'], $contact['ID']);
        }

        return null;
    }

    public function isContactRegistered(string $email) : bool
    {
        if(!$this->areCredentialsDefined()) $this->refuseMethodCall(__METHOD__);

        $response = $this->client->request(
            'GET',
            'https://api.mailjet.com/v3/REST/contact/' . urlencode($email),
            ['auth_basic' => [$_ENV['MAILJET_KEY'], $_ENV['MAILJET_SECRET']]],
        );

        $statusCode = $response->getStatusCode();

        return ($statusCode === 200 && $response->toArray()['Count'] > 0);
    }

    public function registerContactInList(MailjetContactDto $mailjetContactDto, string $listId) : void
    {
        if(!$this->areCredentialsDefined()) $this->refuseMethodCall(__METHOD__);

        $response = $this->client->request(
            'POST',
            'https://api.mailjet.com/v3/REST/contactslist/' . $listId . '/managecontact',
            [
                'body' => [
                    'Email' => $mailjetContactDto->getEmail(),
                    'Action' => 'addforce',
                ],
                'auth_basic' => [$_ENV['MAILJET_KEY'], $_ENV['MAILJET_SECRET']]
            ],
        );
    }

    public function removeContactById(int $id) : void
    {
        if(!$this->areCredentialsDefined()) $this->refuseMethodCall(__METHOD__);

        $response = $this->client->request(
            'DELETE',
            'https://api.mailjet.com/v4/contacts/' . $id,
            [
                'auth_basic' => [$_ENV['MAILJET_KEY'], $_ENV['MAILJET_SECRET']]
            ],
        );
    }

    public function removeContactByEmail(string $email) : void
    {
        if(!$this->areCredentialsDefined()) $this->refuseMethodCall(__METHOD__);

        $mailjetContactDto = $this->getContactByEmail($email);

        $response = $this->client->request(
            'DELETE',
            'https://api.mailjet.com/v4/contacts/' . $mailjetContactDto->getId(),
            [
                'auth_basic' => [$_ENV['MAILJET_KEY'], $_ENV['MAILJET_SECRET']]
            ],
        );
    }

    public function getListRecipientsByEmail(string $email) : array
    {
        if(!$this->areCredentialsDefined()) $this->refuseMethodCall(__METHOD__);

        $response = $this->client->request(
            'GET',
            'https://api.mailjet.com/v3/REST/listrecipient?ContactEmail=' . urlencode($email),
            [
                'auth_basic' => [$_ENV['MAILJET_KEY'], $_ENV['MAILJET_SECRET']]
            ],
        );

        return $response->toArray()['Data'];
    }

    public function addContactToContactList(MailjetContactDto $mailjetContactDto, int $contactListId) : void
    {
        if(!$this->areCredentialsDefined()) $this->refuseMethodCall(__METHOD__);

        $response = $this->client->request(
            'POST',
            'https://api.mailjet.com/v3/REST/contactslist/' . $contactListId . '/managecontact',
            [
                'body' => [
                    'Action' => MailjetAction::ADD_FORCE->value,
                    'Email' => $mailjetContactDto->getEmail()
                ],
                'auth_basic' => [$_ENV['MAILJET_KEY'], $_ENV['MAILJET_SECRET']]
            ],
        );
    }

    public function removeContactToContactList(MailjetContactDto $mailjetContactDto, int $contactListId) : void
    {
        if(!$this->areCredentialsDefined()) $this->refuseMethodCall(__METHOD__);

        $response = $this->client->request(
            'POST',
            'https://api.mailjet.com/v3/REST/contactslist/' . $contactListId . '/managecontact',
            [
                'body' => [
                    'Action' => MailjetAction::REMOVE->value,
                    'Email' => $mailjetContactDto->getEmail()
                ],
                'auth_basic' => [$_ENV['MAILJET_KEY'], $_ENV['MAILJET_SECRET']]
            ],
        );
    }

    public function getContactListsByContact(MailjetContactDto $mailjetContactDto) : array
    {
        if(!$this->areCredentialsDefined()) $this->refuseMethodCall(__METHOD__);

        $response = $this->client->request(
            'GET',
            'https://api.mailjet.com/v3/REST/contact/' . $mailjetContactDto->getId() . '/getcontactslists',
            [
                'auth_basic' => [$_ENV['MAILJET_KEY'], $_ENV['MAILJET_SECRET']]
            ],
        );

        return $response->toArray()['Data'];
    }
}