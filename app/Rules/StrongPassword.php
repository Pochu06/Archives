<?php

namespace App\Rules;

use Illuminate\Validation\Rules\Password;

class StrongPassword
{
    public static function rules(): array
    {
        return [
            'required',
            'string',
            Password::min(8)->mixedCase()->numbers()->symbols(),
            'confirmed',
        ];
    }

    public static function optionalRules(): array
    {
        return [
            'nullable',
            'string',
            Password::min(8)->mixedCase()->numbers()->symbols(),
            'confirmed',
        ];
    }
}
