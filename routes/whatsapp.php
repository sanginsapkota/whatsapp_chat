<?php

use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\VerifyWhatsAppSignature;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WhatsApp Cloud API webhook
|--------------------------------------------------------------------------
|
| Registered in bootstrap/app.php under the "webhooks/whatsapp" prefix with
| no session / CSRF middleware. Meta calls GET to verify the endpoint and
| POST to deliver messages and status updates.
|
*/

Route::get('/', [WhatsAppWebhookController::class, 'verify'])->name('verify');
Route::post('/', [WhatsAppWebhookController::class, 'receive'])
    ->middleware(VerifyWhatsAppSignature::class)
    ->name('receive');
