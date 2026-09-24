<?php

namespace App\Imports\Erkap;

use Maatwebsite\Excel\Concerns\ToArray;

class Form1Import implements ToArray
{
    public function array(array $array): array
    {
        return $array;
    }
}