<?php

namespace App\Services\Audit;

/**
 * Turns links found in a page into absolute URLs (RFC 3986, section 5.2).
 */
class UrlResolver
{
    public static function resolve(string $baseUrl, string $reference): string
    {
        $reference = trim($reference);

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $reference)) {
            return $reference;
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';

        if (str_starts_with($reference, '//')) {
            return "{$scheme}:{$reference}";
        }

        $authority = $base['host'] ?? '';

        if (isset($base['port'])) {
            $authority .= ':'.$base['port'];
        }

        $origin = "{$scheme}://{$authority}";
        $basePath = $base['path'] ?? '/';

        if ($reference === '' || str_starts_with($reference, '#')) {
            return $origin.$basePath.(isset($base['query']) ? '?'.$base['query'] : '');
        }

        if (str_starts_with($reference, '?')) {
            return $origin.$basePath.$reference;
        }

        $path = str_starts_with($reference, '/')
            ? $reference
            : substr($basePath, 0, (int) strrpos($basePath, '/') + 1).$reference;

        return $origin.self::removeDotSegments($path);
    }

    private static function removeDotSegments(string $path): string
    {
        $suffix = '';

        if (preg_match('/[?#]/', $path, $match, PREG_OFFSET_CAPTURE)) {
            $suffix = substr($path, $match[0][1]);
            $path = substr($path, 0, $match[0][1]);
        }

        $output = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                if (count($output) > 1) {
                    array_pop($output);
                }
            } elseif ($segment !== '.') {
                $output[] = $segment;
            }
        }

        $normalized = implode('/', $output);

        if (preg_match('#/\.{1,2}$#', $path)) {
            $normalized .= '/';
        }

        return ($normalized === '' ? '/' : $normalized).$suffix;
    }
}
