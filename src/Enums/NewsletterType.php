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
            $this::FORMATION => (isset($_ENV['MAILJET_CONTACT_LIST_ID_FORMATION'])) ? (int) $_ENV['MAILJET_CONTACT_LIST_ID_FORMATION'] : null,
            $this::PROFESSIONNAL => (isset($_ENV['MAILJET_CONTACT_LIST_ID_PROFESSIONNAL'])) ? (int) $_ENV['MAILJET_CONTACT_LIST_ID_PROFESSIONNAL'] : null,
            $this::PUBLIC => (isset($_ENV['MAILJET_CONTACT_LIST_ID_PUBLIC'])) ? (int) $_ENV['MAILJET_CONTACT_LIST_ID_PUBLIC'] : null,
            default => null,
        };
    }

    public static function contactListIds() : array
    {
        return [
            (isset($_ENV['MAILJET_CONTACT_LIST_ID_FORMATION'])) ? (int) $_ENV['MAILJET_CONTACT_LIST_ID_FORMATION'] : null,
            (isset($_ENV['MAILJET_CONTACT_LIST_ID_PROFESSIONNAL'])) ? (int) $_ENV['MAILJET_CONTACT_LIST_ID_PROFESSIONNAL'] : null,
            (isset($_ENV['MAILJET_CONTACT_LIST_ID_PUBLIC'])) ? (int) $_ENV['MAILJET_CONTACT_LIST_ID_PUBLIC'] : null,
        ];
    }
}