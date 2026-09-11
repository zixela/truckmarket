<?php

use App\Mail\OrderMessageMail;
use App\Mail\VerificationCodeMail;
use App\Models\Order;

beforeEach(function () {
    config(['app.site_host' => 'usatruckers.com', 'app.name' => 'TruckMarket']);
});

it('brands the verification mail with the site host instead of the app name', function () {
    $mail = new VerificationCodeMail('123456');

    expect($mail->envelope()->subject)->toBe('Your usatruckers.com verification code');

    $html = $mail->render();
    expect($html)->toContain('usatruckers.com')
        ->toContain('123456')
        ->not->toContain('TruckMarket');
});

it('brands order mails with the site host instead of the app name', function () {
    $order = Order::factory()->create();
    $message = $order->messages()->create(['sender_id' => $order->customer_id, 'body' => 'hello']);

    $html = (new OrderMessageMail($message))->render();

    expect($html)->toContain('usatruckers.com')->not->toContain('TruckMarket');
});

it('brands the SMS code text with the site host', function () {
    expect(__('auth.sms_code_text', ['code' => '654321', 'site' => config('app.site_host')]))
        ->toBe('usatruckers.com verification code: 654321');
});
