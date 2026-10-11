<?php

namespace App\Services\Audit;

use App\Exceptions\UnsafeUrlException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client for user-supplied URLs. Every request, including each redirect
 * hop, is checked against private and reserved addresses, and the connection
 * is pinned to the checked IP so DNS cannot change between check and request.
 */
class SafeHttpClient
{
    /**
     * IPv4 ranges PHP's filter flags do not cover.
     *
     * @var list<string>
     */
    private const EXTRA_BLOCKED_RANGES = ['100.64.0.0/10', '192.0.0.0/24', '198.18.0.0/15'];

    public function __construct(private HostResolver $resolver) {}

    /**
     * @throws UnsafeUrlException
     */
    public function get(string $url): FetchResult
    {
        return $this->send('GET', $url);
    }

    /**
     * Sends a HEAD request, falling back to GET for servers that reject HEAD.
     *
     * @throws UnsafeUrlException
     */
    public function head(string $url): FetchResult
    {
        $result = $this->send('HEAD', $url);

        if (in_array($result->response->status(), [403, 405, 501], true)) {
            return $this->send('GET', $url);
        }

        return $result;
    }

    /**
     * Validates the URL and returns the IP address that requests will be pinned to.
     *
     * @throws UnsafeUrlException
     */
    public function assertSafe(string $url): string
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw UnsafeUrlException::invalidScheme($url);
        }

        $host = trim((string) parse_url($url, PHP_URL_HOST), '[]');

        if ($host === '') {
            throw UnsafeUrlException::missingHost($url);
        }

        $addresses = $this->resolver->resolve($host);

        if ($addresses === []) {
            throw UnsafeUrlException::unresolvable($host);
        }

        foreach ($addresses as $address) {
            if (! $this->isPublicAddress($address)) {
                throw UnsafeUrlException::privateAddress($host);
            }
        }

        return $addresses[0];
    }

    public function isPublicAddress(string $address): bool
    {
        $isPublic = filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;

        if (! $isPublic) {
            return false;
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return ! $this->isIpv6Blocked($address);
        }

        foreach (self::EXTRA_BLOCKED_RANGES as $range) {
            if ($this->ipv4InRange($address, $range)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws UnsafeUrlException
     */
    private function send(string $method, string $url): FetchResult
    {
        $maxRedirects = config('webpulse.max_redirects');
        $startedAt = hrtime(true);

        for ($redirects = 0; $redirects <= $maxRedirects; $redirects++) {
            $pinnedIp = $this->assertSafe($url);

            $response = $this->request($url, $pinnedIp)->send($method, $url);

            $location = $response->header('Location');

            if (! $response->redirect() || $location === '') {
                return new FetchResult(
                    response: $response,
                    finalUrl: $url,
                    durationMs: (int) ((hrtime(true) - $startedAt) / 1_000_000),
                    redirects: $redirects,
                );
            }

            $url = UrlResolver::resolve($url, $location);
        }

        throw UnsafeUrlException::tooManyRedirects($url);
    }

    private function request(string $url, string $pinnedIp): PendingRequest
    {
        $host = trim((string) parse_url($url, PHP_URL_HOST), '[]');
        $port = parse_url($url, PHP_URL_PORT)
            ?? (strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80);
        $ip = str_contains($pinnedIp, ':') ? "[{$pinnedIp}]" : $pinnedIp;

        return Http::withoutRedirecting()
            ->withUserAgent(config('webpulse.user_agent'))
            ->connectTimeout(config('webpulse.connect_timeout'))
            ->timeout(config('webpulse.timeout'))
            ->withOptions(['curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"]]]);
    }

    private function isIpv6Blocked(string $address): bool
    {
        $packed = inet_pton($address);

        // IPv4-mapped addresses (::ffff:a.b.c.d) are checked as IPv4.
        if (str_starts_with($packed, str_repeat("\0", 10)."\xff\xff")) {
            return ! $this->isPublicAddress(inet_ntop(substr($packed, 12)));
        }

        $firstByte = ord($packed[0]);
        $secondByte = ord($packed[1]);

        $isUniqueLocal = ($firstByte & 0xFE) === 0xFC;
        $isLinkLocal = $firstByte === 0xFE && ($secondByte & 0xC0) === 0x80;

        return $isUniqueLocal || $isLinkLocal || $address === '::1' || $address === '::';
    }

    private function ipv4InRange(string $address, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $mask = -1 << (32 - (int) $bits);

        return (ip2long($address) & $mask) === (ip2long($subnet) & $mask);
    }
}
