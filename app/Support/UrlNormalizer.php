<?php

namespace App\Support;

/**
 * Gives every way of typing the same address one canonical form,
 * so "Example.com" and "https://example.com/" share their audit history.
 */
class UrlNormalizer
{
    public static function normalize(string $url): string
    {
        $url = trim($url);

        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $url = "https://{$url}";
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host'] ?? '');
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return "{$scheme}://{$host}{$port}{$path}{$query}";
    }

    public static function displayName(string $url): string
    {
        return (string) preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST));
    }
}
