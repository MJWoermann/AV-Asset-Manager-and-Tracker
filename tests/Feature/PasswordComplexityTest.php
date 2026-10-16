<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordComplexityTest extends TestCase
{
    #[DataProvider('validPasswords')]
    public function test_accepts_passwords_that_meet_complexity_rules(string $password): void
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => [Password::defaults()]],
        );

        $this->assertTrue($validator->passes(), (string) $validator->errors());
    }

    #[DataProvider('invalidPasswords')]
    public function test_rejects_passwords_that_fail_complexity_rules(string $password): void
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => [Password::defaults()]],
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('password'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validPasswords(): array
    {
        return [
            'uppercase_and_number' => ['Password1'],
            'uppercase_and_symbol' => ['Password!'],
            'uppercase_number_and_symbol' => ['Secret1!'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidPasswords(): array
    {
        return [
            'too_short' => ['Pass1!'],
            'no_uppercase' => ['password1'],
            'no_number_or_symbol' => ['Password'],
            'lowercase_only' => ['password'],
        ];
    }
}
