<?php

namespace App\Services\Contracts;

interface MediaStorageInterface
{
    /** Čuva fajl (logo, slika radnika) i vraća adresu na kojoj se prikazuje. */
    public function put(string $path, string $contents): string;

    public function deleteDirectory(string $directory): void;

    public function url(string $path): string;
}
