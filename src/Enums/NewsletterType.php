<?php

namespace App\Enums;

enum NewsletterType : string
{
    case FORMATION = 'Abonnés newsletter formation';
    case PROFESSIONNAL = 'Abonnés newsletter professionnelle';
    case PUBLIC = 'Abonnés newsletter tout public';

    public function contactListId() : ?int
    {
        return match($this) {
            $this::FORMATION => (int) $_ENV['MAILJET_CONTACT_LIST_ID_FORMATION'],
            $this::PROFESSIONNAL => (int) $_ENV['MAILJET_CONTACT_LIST_ID_PROFESSIONNAL'],
            $this::PUBLIC => (int) $_ENV['MAILJET_CONTACT_LIST_ID_PUBLIC'],
            default => null,
        };
    }
}