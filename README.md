# Handle Mailgun Webhooks in a Laravel application
*A clone of [spatie/laravel-stripe-webhooks](https://github.com/spatie/laravel-stripe-webhooks)*

[![Latest Version on Packagist](https://img.shields.io/packagist/v/puz/laravel-mailgun-webhooks.svg?style=flat-square)](https://packagist.org/packages/puz/laravel-mailgun-webhooks)
[![Total Downloads](https://img.shields.io/packagist/dt/puz/laravel-mailgun-webhookss.svg?style=flat-square)](https://packagist.org/packages/puz/laravel-mailgun-webhooks)

[Mailgun](https://mailgun.com) can notify your application of events using webhooks. This package can help you handle those webhooks. Out of the box it will verify the Mailgun signature of all incoming requests. All valid calls will be logged to the database. You can easily define jobs or events that should be dispatched when specific events hit your app.

This package will not handle what should be done after the webhook request has been validated and the right job or event is called. You should still code up any work yourself.

## Installation

You can install the package via composer:

```bash
composer require puz/laravel-mailgun-webhooks
```

The service provider will automatically register itself.

You must publish the config file with:
```bash
php artisan vendor:publish --provider="Puz\MailgunWebhooks\MailgunWebhooksServiceProvider" --tag="config"
```

In the `webhook_secret` key of the config file you should add a valid webhook secret. You can find the secret used at [the API security settings on the Mailgun dashboard](https://app.mailgun.com/app/account/security/api_keys).

Next, you must publish the migration with:
```bash
php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="migrations"
```

After the migration has been published you can create the `webhook_calls` table by running the migrations:

```bash
php artisan migrate
```

Finally, take care of the routing: At each domain on your Mailgun account, you must add the callback URL as a webhook for each event you want to listen too. In the routes file of your app you must pass that route to `Route::mailgunWebhooks`:

```php
Route::mailgunWebhooks('webhook-route-configured-in-mailgun');
```

Behind the scenes this will register a `POST` route to a controller provided by this package. Because Mailgun has no way of getting a csrf-token, you must add that route to the `except` array of the `VerifyCsrfToken` middleware:

```php
protected $except = [
    'webhook-route-configured-in-mailgun',
];
```

## Usage

This package is a complete copy of [spatie/laravel-stripe-webhooks](https://github.com/spatie/laravel-stripe-webhooks#usage) and you can read more on how it works over there. There is some great code snippets to see how you can use it

## Testing

```bash
composer test
```

## Credits
- [Spatie: Laravel Stripe Webhooks](https://github.com/spatie/laravel-stripe-webhooks)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
