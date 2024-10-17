<?php

namespace App\Dto;

use App\Entity\Contact;

class MailjetContactDto
{
    private readonly string $email;
    private readonly ?string $name;
    private readonly ?int $id;
    private readonly ?array $parameters;

    public function __construct(string $email, ?int $id = null, ?string $name = null, array $parameters = [])
    {
        $this->email = $email;
        $this->id = $id;
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getParameters() : ?array
    {
        return $this->parameters;
    }

    public function getParameter(string $name) : ?string
    {
        return (isset($this->parameters[$name])) ? $this->parameters[$name] : null;
    }
}