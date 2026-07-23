<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;

/**
 * The AI provider key is written by the mother app (btc-check) and read here,
 * so it cannot be encrypted with either app's APP_KEY. Both sides derive the
 * same cipher from a shared secret in their .env instead.
 *
 * The mother app has an identical copy of this class.
 */
class AiKeyCipher
{
    public static function available(): bool
    {
        return filled(config('anisystem.ai_key_secret'));
    }

    public static function encrypt(string $plain): string
    {
        return self::cipher()->encryptString($plain);
    }

    /** Null rather than an exception: a key we cannot read is a key we do not have. */
    public static function decrypt(?string $payload): ?string
    {
        if (! $payload || ! self::available()) {
            return null;
        }

        try {
            return self::cipher()->decryptString($payload);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function cipher(): Encrypter
    {
        $secret = (string) config('anisystem.ai_key_secret');
        if ($secret === '') {
            throw new \RuntimeException('ANISYSTEM_AI_KEY_SECRET is not configured.');
        }

        // Hashed to a fixed 32 bytes so any length of secret is usable.
        return new Encrypter(hash('sha256', $secret, true), 'AES-256-CBC');
    }
}
