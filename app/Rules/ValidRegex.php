<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidRegex implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        // Check format /pattern/flags
        if (!preg_match('/^\/(.+)\/([gimsuy]*)$/', $value)) {
            return false;
        }

        // Try to create the regex with the full string (including delimiters)
        // preg_match() requires delimiters, so we pass the full value
        $result = @preg_match($value, '');

        return $result !== false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $error = preg_last_error();
        $errorMessages = [
            PREG_NO_ERROR => 'No error',
            PREG_INTERNAL_ERROR => 'Internal PCRE error',
            PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit exhausted',
            PREG_RECURSION_LIMIT_ERROR => 'Recursion limit exhausted',
            PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
            PREG_BAD_UTF8_OFFSET_ERROR => 'Bad UTF-8 offset',
        ];

        $errorMsg = $errorMessages[$error] ?? 'Unknown error';

        return "The :attribute must be a valid regular expression in format /pattern/flags. Error: {$errorMsg}";
    }
}
