<?php

declare(strict_types=1);

namespace App\Support;

class ProxyTrust
{
    public static function clientIp(array $server): string
    {
        $remoteAddress = self::remoteAddress($server);

        if (!self::shouldTrustForwardedHeaders($remoteAddress)) {
            return $remoteAddress;
        }

        $forwardedFor = (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwardedFor !== '') {
            foreach (explode(',', $forwardedFor) as $candidate) {
                $candidate = trim($candidate);
                if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                    return $candidate;
                }
            }
        }

        $realIp = trim((string) ($server['HTTP_X_REAL_IP'] ?? ''));
        if ($realIp !== '' && filter_var($realIp, FILTER_VALIDATE_IP) !== false) {
            return $realIp;
        }

        return $remoteAddress;
    }

    public static function isSecureRequest(array $server): bool
    {
        $https = strtolower((string) ($server['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return true;
        }

        if ((string) ($server['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        $remoteAddress = self::remoteAddress($server);
        if (!self::shouldTrustForwardedHeaders($remoteAddress)) {
            return false;
        }

        $forwardedProto = strtolower(trim(explode(',', (string) ($server['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
        if ($forwardedProto === 'https') {
            return true;
        }

        $forwardedSsl = strtolower(trim((string) ($server['HTTP_X_FORWARDED_SSL'] ?? '')));
        if ($forwardedSsl === 'on') {
            return true;
        }

        return (string) ($server['HTTP_X_FORWARDED_PORT'] ?? '') === '443';
    }

    private static function remoteAddress(array $server): string
    {
        $remoteAddress = trim((string) ($server['REMOTE_ADDR'] ?? '127.0.0.1'));

        return $remoteAddress !== '' ? $remoteAddress : '127.0.0.1';
    }

    private static function shouldTrustForwardedHeaders(string $remoteAddress): bool
    {
        if (self::isTrustedProxy($remoteAddress)) {
            return true;
        }

        return self::isPrivateOrReserved($remoteAddress);
    }

    private static function isTrustedProxy(string $remoteAddress): bool
    {
        $trustedProxies = array_values(array_filter(array_map('trim', explode(',', (string) ($_ENV['TRUSTED_PROXIES'] ?? '')))));
        if ($trustedProxies === []) {
            return false;
        }

        foreach ($trustedProxies as $proxy) {
            if ($proxy === $remoteAddress || self::matchesCidr($remoteAddress, $proxy)) {
                return true;
            }
        }

        return false;
    }

    private static function matchesCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return false;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false || filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        $maskBits = (int) $mask;
        if ($maskBits < 0 || $maskBits > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $bitmask = $maskBits === 0 ? 0 : (~((1 << (32 - $maskBits)) - 1));

        return ($ipLong & $bitmask) === ($subnetLong & $bitmask);
    }

    private static function isPrivateOrReserved(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
