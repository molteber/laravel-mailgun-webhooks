<?php

namespace Puz\MailgunWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;
use Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile;

class MailgunWebhooksController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param string|null $configKey
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Spatie\WebhookClient\Exceptions\InvalidConfig
     */
    public function __invoke(Request $request, ?string $configKey = null)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'mailgun',
            'signing_secret' => ($configKey)
                ? config('mailgun-webhooks.webhook_secret_' . $configKey)
                : config('mailgun-webhooks.webhook_secret'),
            'signature_validator' => MailgunSignatureValidator::class,
            'webhook_profile' => ProcessEverythingWebhookProfile::class,
            'webhook_model' => WebhookCall::class,
            'process_webhook_job' => config('mailgun-webhooks.model')
        ]);

        (new WebhookProcessor($request, $webhookConfig))->process();

        return response()->json(['message' => 'ok']);
    }
}
