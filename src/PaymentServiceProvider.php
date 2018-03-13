<?php

namespace Appnings\Payment;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

class PaymentServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */

    public function boot()
    {
        $gateway = Config::get('payment.gateway');

        $this->app->bind('Payment', 'Appnings\Payment\Payment');

        $this->app->bind('Appnings\Payment\Gateways\PaymentGatewayInterface', 'Appnings\Payment\Gateways\\' . $gateway . 'Gateway');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->publishes([
            __DIR__ . '/Config/config.php' => base_path('config/payment.php'),
            __DIR__ . '/Views/middleware.blade.php' => base_path('app/Http/Middleware/VerifyCsrfMiddleware.php'),
            __DIR__ . '/Views/ccavenue.blade.php' => base_path('resources/views/vendor/payment/ccavenue.blade.php'),

        ]);

        $this->loadViewsFrom(__DIR__ . '/views', 'payment');
    }
}
