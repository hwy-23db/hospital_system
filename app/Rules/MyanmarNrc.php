<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MyanmarNrc implements Rule
{
    protected array $codes;
    protected array $citizenships;

    public function __construct()
    {
        $this->codes = config('nrc_codes.codes', []);
        $this->citizenships = config('nrc_codes.citizenships', []);
    }

    public function passes($attribute, $value): bool
    {
        if ($value === null || $value === '') {
            return true; // allow null/empty
        }

        if (!is_string($value)) {
            return false;
        }

        // Format: {nrc_code}/{name_en}({citizenship}){6 digits}
        if (!preg_match('/^(\\d{1,2})\\/([A-Za-z]+)\\((N|F|P|TH|S)\\)(\\d{6})$/', $value, $m)) {
            return false;
        }

        [$full, $code, $township, $citizenship, $numbers] = $m;

        // Validate citizenship
        if (!in_array($citizenship, $this->citizenships, true)) {
            return false;
        }

        // Validate township against config
        if (empty($this->codes) || !isset($this->codes[$code])) {
            return true; // dataset incomplete; allow format-valid NRC
        }

        if (!in_array($township, $this->codes[$code], true)) {
            return false;
        }

        // numbers already matched 6 digits
        return true;
    }

    public function message(): string
    {
        return 'The :attribute must match NRC format {nrc_code}/{township}({citizenship})XXXXXX and use valid codes.';
    }
}
