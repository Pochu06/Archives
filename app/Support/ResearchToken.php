<?php

namespace App\Support;

class ResearchToken
{
    private const CIPHER = 'aes-256-gcm';

    private const IV_LENGTH = 12;

    private const TAG_LENGTH = 8;

    public static function encode(int|string $id): string
    {
        $iv = random_bytes(self::IV_LENGTH);
        $cipher = openssl_encrypt((string) $id, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LENGTH);

        return rtrim(strtr(base64_encode($iv.$tag.$cipher), '+/', '-_'), '=');
    }

    public static function decode(string $token): ?int
    {
        $raw = base64_decode(strtr($token, '-_', '+/'), true);

        if ($raw === false || strlen($raw) <= self::IV_LENGTH + self::TAG_LENGTH) {
            return null;
        }

        $value = openssl_decrypt(
            substr($raw, self::IV_LENGTH + self::TAG_LENGTH),
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            substr($raw, 0, self::IV_LENGTH),
            substr($raw, self::IV_LENGTH, self::TAG_LENGTH)
        );

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }

    private static function key(): string
    {
        return hash('sha256', 'research-token|'.config('app.key'), true);
    }
}