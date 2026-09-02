<?php

namespace App\Providers;

use App\Services\CompanyVerification\CompanyVerifier;
use App\Services\CompanyVerification\FmcsaCompanyVerifier;
use App\Services\CompanyVerification\NullCompanyVerifier;
use App\Services\Payments\NullGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\StripeGateway;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Sms\TwilioSmsSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyVerifier::class, function () {
            $webKey = config('services.fmcsa.webkey');

            return $webKey
                ? new FmcsaCompanyVerifier($webKey)
                : new NullCompanyVerifier;
        });

        $this->app->bind(PaymentGateway::class, function () {
            $secret = config('services.stripe.secret');

            return $secret ? new StripeGateway($secret) : new NullGateway;
        });

        $this->app->bind(SmsSender::class, function () {
            $twilio = config('services.twilio');

            if (($twilio['sid'] ?? null) && ($twilio['token'] ?? null) && ($twilio['from'] ?? null)) {
                return new TwilioSmsSender($twilio['sid'], $twilio['token'], $twilio['from']);
            }

            return new LogSmsSender;
        });
    }

    public function boot(): void
    {
        //
    }
}
