<?php

namespace App\Service;

class PasswordGenerator
{
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const NUMBERS = '0123456789';
    private const SPECIAL = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    public function generate(int $length = 12): string
    {
        $password = '';
        
        // Assurer au moins un caractère de chaque type
        $password .= self::LOWERCASE[random_int(0, strlen(self::LOWERCASE) - 1)];
        $password .= self::UPPERCASE[random_int(0, strlen(self::UPPERCASE) - 1)];
        $password .= self::NUMBERS[random_int(0, strlen(self::NUMBERS) - 1)];
        $password .= self::SPECIAL[random_int(0, strlen(self::SPECIAL) - 1)];

        // Compléter avec des caractères aléatoires
        $allChars = self::LOWERCASE . self::UPPERCASE . self::NUMBERS . self::SPECIAL;
        $remainingLength = $length - strlen($password);

        for ($i = 0; $i < $remainingLength; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Mélanger tous les caractères
        return str_shuffle($password);
    }
}
