<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsAppSignature
{
    /**
     * Validate the X-Hub-Signature-256 header Meta sends with every webhook POST.
     *
     * The signature is HMAC-SHA256 of the raw request body keyed with the Meta
     * app secret. When no app secret is configured (local dev) the check is
     * skipped so the endpoint stays testable.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.whatsapp.app_secret');

        if (blank($secret)) {
            return $next($request);
        }

        $header = $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (! is_string($header) || ! hash_equals($expected, $header)) {
            Log::channel('whatsapp')->warning('Rejected WhatsApp webhook with invalid signature', [
                'ip' => $request->ip(),
            ]);

            abort(Response::HTTP_FORBIDDEN, 'Invalid signature.');
        }

        return $next($request);
    }
}
