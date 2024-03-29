<?php /** @noinspection PhpUndefinedClassInspection */

namespace Puz\MailgunWebhooks\Tests;

use Exception;
use CreateWebhookCallsTable;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Puz\MailgunWebhooks\MailgunWebhooksServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        config(['mailgun-webhooks.webhook_secret' => 'test_webhook_secret']);
    }

    protected function setUpDatabase()
    {
        include_once __DIR__.'/../vendor/spatie/laravel-webhook-client/database/migrations/create_webhook_calls_table.php.stub';

        (new CreateWebhookCallsTable())->up();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            MailgunWebhooksServiceProvider::class,
        ];
    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler {
            public function __construct()
            {
            }

            public function report(Exception $e)
            {
            }

            public function render($request, Exception $exception)
            {
                throw $exception;
            }
        });
    }

    protected function determineWebhookSignature(array $payload, string $configKey = null): array
    {
        $secret = ($configKey) ?
            config("mailgun-webhooks.webhook_secret_{$configKey}") :
            config('mailgun-webhooks.webhook_secret');

        $timestamp = time();
        $token = md5(json_encode($payload));

        $timestampedPayload = $timestamp.$token;

        $signature = hash_hmac('sha256', $timestampedPayload, $secret);

        return compact('timestamp', 'token', 'signature');
    }
}
