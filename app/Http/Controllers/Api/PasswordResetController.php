<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\MessageResource;
use App\Services\Contracts\PasswordResetServiceInterface;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetServiceInterface $passwords) {}

    /** Odgovor je uvek isti, da se ne bi otkrivalo koje adrese imaju nalog. */
    public function forgot(ForgotPasswordRequest $request): MessageResource
    {
        $this->passwords->sendLink($request->string('email')->toString());

        return MessageResource::make(__('messages.password_reset_link_sent'));
    }

    public function reset(ResetPasswordRequest $request): MessageResource
    {
        /** @var array{token: string, email: string, password: string} $credentials */
        $credentials = $request->only('token', 'email', 'password');
        $status = $this->passwords->reset($credentials);

        if ($status !== Password::PASSWORD_RESET) {
            throw DomainException::unprocessable(__($status));
        }

        return MessageResource::make(__($status));
    }
}
