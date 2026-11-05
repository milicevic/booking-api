<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Resources\MessageResource;
use App\Services\Contracts\EmailVerificationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Link iz mejla otvara se u pregledaču, pa verify() uvek preusmerava na stranicu za prijavu
 * na sajtu firme (?verified=1 ili ?verified=invalid), umesto da vraća JSON.
 */
class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationServiceInterface $verification) {}

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        return redirect()->away($this->verification->verify($id, $hash, $request->hasValidSignature()));
    }

    /** Odgovor je uvek isti, da se ne bi otkrivalo koje adrese imaju nalog. */
    public function resend(ResendVerificationRequest $request): MessageResource
    {
        $this->verification->resend($request->string('email')->toString());

        return MessageResource::make(__('messages.verification_sent'));
    }
}
