<?php

return [

    /*
     * Mailgun generate a signature with each webhook using a secret. You can find the used secret at Settings ->
     * API Keys -> API Security on your mailgun dashboard: https://app.mailgun.com/app/account/security/api_keys
     */
    'webhook_secret' => env('MAILGUN_WEBHOOK_SECRET'),

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Webhook event.
     *
     * List of available event names:
     * - complained
     * - opened
     * - delivered
     * - clicked
     * - failed
     */
    'jobs' => [
        // 'opened' => \App\Jobs\MailgunWebhooks\HandleOpened::class,
        // 'failed' => \App\Jobs\MailgunWebhooks\HandleFailed::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Puz\MailgunWebhooks\ProcessStripeWebhookJob.
     */
    'model' => \Puz\MailgunWebhooks\ProcessMailgunWebhookJob::class,
];
