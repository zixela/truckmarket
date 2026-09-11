<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\VerificationCodeMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Admin-only mail delivery check: GET /{locale}/mail-test?to=you@example.com[&queue=1]
 *
 * Sends synchronously by default so SMTP errors show up in the response;
 * queue=1 pushes the real VerificationCodeMail through the queue worker instead.
 */
class MailTestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'to' => ['required', 'email'],
            'queue' => ['nullable', 'boolean'],
        ]);

        $mailer = config('mail.default');
        $info = [
            'to' => $data['to'],
            'mailer' => $mailer,
            'host' => config("mail.mailers.{$mailer}.host"),
            'port' => config("mail.mailers.{$mailer}.port"),
            'from' => config('mail.from.address'),
            'queue' => (bool) ($data['queue'] ?? false),
        ];

        $started = microtime(true);

        try {
            if ($info['queue']) {
                Mail::to($data['to'])->queue(new VerificationCodeMail('123456'));
            } else {
                Mail::raw('TruckMarket mail test from '.config('app.url'), fn ($message) => $message
                    ->to($data['to'])
                    ->subject('TruckMarket mail test'));
            }
        } catch (Throwable $e) {
            return response()->json($info + ['ok' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json($info + [
            'ok' => true,
            'ms' => (int) round((microtime(true) - $started) * 1000),
            'note' => $info['queue']
                ? 'Queued: check journalctl -u truckmarket-queue and the inbox.'
                : 'Sent synchronously: check the inbox (and the spam folder).',
        ]);
    }
}
