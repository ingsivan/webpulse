<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a URL must not be requested: wrong scheme, unresolvable host,
 * or a host that points at a private, loopback or reserved address.
 */
class UnsafeUrlException extends Exception
{
    public static function invalidScheme(string $url): self
    {
        return new self("Solo se permiten URLs http o https: {$url}");
    }

    public static function missingHost(string $url): self
    {
        return new self("La URL no tiene un dominio válido: {$url}");
    }

    public static function unresolvable(string $host): self
    {
        return new self("No se pudo resolver el dominio {$host}.");
    }

    public static function privateAddress(string $host): self
    {
        return new self("El dominio {$host} apunta a una dirección interna y no se puede auditar.");
    }

    public static function tooManyRedirects(string $url): self
    {
        return new self("Demasiadas redirecciones al abrir {$url}.");
    }
}
