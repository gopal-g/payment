# CCAvenue Payment Gateway integration for Laravel 5.x (Supports PHP 7.1)

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]


This package is built to integrate CCAvenue payment gateway into your laravel 
project, It is developed keeping in mind other payment gateways also but is limited to ccavenue for at the time of release.

## Important

The code is originially a fork of similar package by [softon/indipay](https://github.com/softon/indipay). But this package uses the latest SDK released by CCAvenue for ```php 7.1``` Since their old SDK didn't support that version.


## Installation

<b>Step 1:</b> Install package using composer

``` bash
$ composer require gopal-g/payment
```
<b>Step 2:</b> Add the service provider to the ``config/app.php`` file in Laravel

```
 Appnings\Payment\PaymentServiceProvider::class,
```
<b>Step 3:</b> Add an alias for the Facade to the ``config/app.php`` file in Laravel
```
 'Payment' => Appnings\Payment\Facades\Payment::class 
```

<b>Step 4:</b> Publish the Config, Middleware & Views by running in your terminal
```
  php artisan vendor:publish
```
This above step creates 
-  `` config/payment.php ``
- `` app/Http/Middlewares/VerifyCsrfToken.php ``
- `` resources/views/vendor/payment/ccavenue.blade.php ``

<b>Step 5:</b> Modify the ``app\Http\Kernel.php`` to use the new Middleware. 
This is required so as to avoid CSRF verification on the Response Url from the payment gateways.
<b>You may adjust the routes in the config file ``config/payment.php`` to disable CSRF on your gateways response routes.</b>
```
   'App\Http\Middleware\VerifyCsrfToken',
```
   to
```
   'App\Http\Middleware\VerifyCsrfMiddleware', 
```


## Usage

Edit the ``config/payment.php``. Set the appropriate Gateway and its parameters. Then in your code... <br>

``` 
    use Appnings\Payment\Facades\Payment;  
```

Initiate Purchase Request and Redirect using the default gateway:-

```php 
      /* All Required Parameters by your Gateway */
      
      $parameters = [
      
        'tid' => '1233221223322',
        
        'order_id' => '1232212',
        
        'amount' => '1200.00',
        
      ];
      
      $order = Payment::prepare($parameters);
      return Payment::process($order);
```

Initiate Purchase Request and Redirect using any of the configured gateway:-
```php 
      /* All Required Parameters by your Gateway */
      
      $parameters = [
      
        'tid' => '1233221223322',
        
        'order_id' => '1232212',
        
        'amount' => '1200.00',
        
      ];
      
      // gateway = CCAvenue / others
      
      $order = Payment::gateway('NameOfGateway')->prepare($parameters);
      return Payment::process($order);
```
Get the Response from the Gateway (Add the Code to the Redirect Url Set in the config file. 
Also add the response route to the `remove_csrf_check` config item to remove CSRF check on these routes.):-
<pre><code> 
    public function response(Request $request)
    
    {
        // For default Gateway
        $response = Payment::response($request);
        
        // For Otherthan Default Gateway
        $response = Payment::gateway('NameOfGatewayUsedDuringRequest')->response($request);

        dd($response);
    
    }  
</code></pre>

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email `c4gopal@gmail.com` instead of using the issue tracker.

## Credits
- [Appnings](http://www.appnings.com)
- [Shiburaj Pappu](https://github.com/softon/indipay)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/gopal-g/payment.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/gopal-g/payment/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/gopal-g/payment.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/gopal-g/payment.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/gopal-g/payment.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/gopal-g/payment
[link-travis]: https://travis-ci.org/gopal-g/payment
[link-scrutinizer]: https://scrutinizer-ci.com/g/gopal-g/payment/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/gopal-g/payment
[link-downloads]: https://packagist.org/packages/gopal-g/payment
[link-author]: https://github.com/gopal-g
[link-contributors]: ../../contributors
