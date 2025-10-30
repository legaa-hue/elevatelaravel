<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class RecaptchaRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('Please complete the reCAPTCHA verification.');
            return;
        }

        $secretKey = config('services.recaptcha.secret_key');
        
        if (empty($secretKey)) {
            // If reCAPTCHA is not configured, skip validation in development
            if (config('app.env') === 'local') {
                return;
            }
            $fail('reCAPTCHA is not properly configured.');
            return;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            $result = $response->json();

            if (!isset($result['success']) || !$result['success']) {
                $fail('reCAPTCHA verification failed. Please try again.');
            }
        } catch (\Exception $e) {
            // If reCAPTCHA verification fails due to network issues, allow in development
            if (config('app.env') === 'local') {
                return;
            }
            $fail('Failed to verify reCAPTCHA. Please try again.');
        }
    }
}
