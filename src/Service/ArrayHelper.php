<?php

namespace App\Service;


class ArrayHelper
{
    /**
     * PHP equivalent of array.prototype.any in Javascript
     */
    public function containsSome(array $array, callable $callback) : bool
    {
        foreach ($array as $value) {
            if($callback($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * PHP equivalent of array.prototype.every in Javascript
     */
    public function containsOnly(array $array, callable $callback) : bool
    {
        foreach ($array as $value) {
            if(!$callback($value)) {
                return false;
            }
        }
        return true;
    }
}