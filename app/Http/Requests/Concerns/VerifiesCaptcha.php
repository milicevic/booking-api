<?php

namespace App\Http\Requests\Concerns;

use App\Services\Contracts\CaptchaVerifierInterface;
use Illuminate\Validation\Validator;

/**
 * Za javne forme: token iz captcha widgeta (`captcha_token`) mora biti ispravan kad je zaštita uključena.
 */
trait VerifiesCaptcha
{
    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $captcha = app(CaptchaVerifierInterface::class);
                $token = $this->input('captcha_token');

                if (! $captcha->verify(is_string($token) ? $token : null, $this->ip())) {
                    $validator->errors()->add('captcha_token', __('messages.captcha_failed'));
                }
            },
        ];
    }
}
