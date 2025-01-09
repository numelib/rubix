<?php

namespace App\Service;

use libphonenumber\PhoneNumberUtil;

class ExcelValueConverter
{
    public function __construct(
        private readonly PhoneNumberUtil $phoneNumberUtil
    ){}

    /**
     * @return \libphonenumber\PhoneNumber[]|string[]
     */
    public function toPhoneNumbers(string $value, bool $asObject = true) : array
    {
        $result = [];

        // Remplacement caractères encodés bizarrement dans Export Excel (pourquoi ? Aucune idée)
        $toReplace = [
            ".",
            " ",
            "‭",
            "‬",
            "\u{202C}",
            "\u{202D}",
            mb_chr(0x202C, 'UTF-8'),
            mb_chr(0x202D, 'UTF-8')
        ];

        $value = str_replace("\u{202F}", ' ', $value);
        $value = str_replace($toReplace, '', $value);
        $value = preg_replace('/\x{202D}/u', '', $value);
        $value = preg_replace('/[\p{C}]/u', '', $value);
        $value = preg_replace('/[^\p{L}\p{N}\+\/]/u', '', $value);

        $numbers = explode('/', $value);
        for($i = 0; $i < count($numbers); $i++)
        {
            if($i === 1 && strlen($numbers[1]) === 2) {
                $numbers[1] = substr_replace($numbers[0], $numbers[1], strlen($numbers[0]) - 2);
            }

            if(!str_starts_with($numbers[$i], '+')) {
                $numbers[$i] = '+33' . $numbers[$i];
            }

            $result[] = $this->phoneNumberUtil->parse($this->phoneNumberUtil::extractPossibleNumber($numbers[$i]));
        }

        return ($asObject) ? $result : $numbers;
    }
}