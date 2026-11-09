<?php

namespace App\Services\Contracts;

interface PasswordResetServiceInterface
{
    /** Šalje link za novu lozinku, ako nalog postoji (pozivalac uvek vraća isti odgovor). */
    public function sendLink(string $email): void;

    /**
     * Postavlja novu lozinku ako je token ispravan; vraća status Password brokera (npr. passwords.reset).
     *
     * @param  array{token: string, email: string, password: string}  $credentials
     */
    public function reset(array $credentials): string;
}
