<?php

namespace App\Support;

/**
 * Jednokratni kodovi po vremenu (TOTP, RFC 6238), kakve prave aplikacije
 * poput Google Authenticator-a: HMAC-SHA1 tajne i broja 30-sekundnih
 * intervala od 1. 1. 1970, skraćen na 6 cifara.
 */
final class Totp
{
    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Nova nasumična tajna od 160 bita, zapisana u base32. */
    public static function secret(): string
    {
        $bits = '';
        foreach (str_split(random_bytes(20)) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(
            fn (string $chunk) => self::BASE32[bindec($chunk)],
            str_split($bits, 5),
        ));
    }

    public static function code(string $secret, ?int $time = null): string
    {
        $counter = intdiv($time ?? time(), 30);
        $hash = hash_hmac('sha1', pack('J', $counter), self::decode($secret), true);

        // Dinamičko skraćivanje: poslednja 4 bita biraju 4 bajta iz heša.
        $offset = ord($hash[19]) & 0x0F;
        $number = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($number % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Prihvata i kod iz prethodnog i iz sledećeg intervala, jer satovi
     * telefona i servera nikad nisu potpuno usklađeni.
     */
    public static function verify(string $secret, string $code, ?int $time = null): bool
    {
        $time ??= time();
        foreach ([-30, 0, 30] as $shift) {
            if (hash_equals(self::code($secret, $time + $shift), $code)) {
                return true;
            }
        }

        return false;
    }

    /** Adresa za QR kod koji aplikacija za autentifikaciju skenira. */
    public static function uri(string $secret, string $account, string $issuer): string
    {
        return sprintf('otpauth://totp/%s:%s?%s',
            rawurlencode($issuer), rawurlencode($account),
            http_build_query(['secret' => $secret, 'issuer' => $issuer]));
    }

    private static function decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper($secret)) as $char) {
            $bits .= str_pad(decbin(strpos(self::BASE32, $char)), 5, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(
            fn (string $byte) => chr(bindec($byte)),
            str_split(substr($bits, 0, intdiv(strlen($bits), 8) * 8), 8),
        ));
    }
}
