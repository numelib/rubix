<?php

namespace App\Enums;

enum MailjetAction : string
{
    case ADD_FORCE = 'addforce';
    case ADD_NO_FORCE = 'addnoforce';
    case REMOVE = 'remove';
    case UNSUB = 'unsub';
}