<?php

namespace Puz\MailgunWebhooks;

use Spatie\WebhookClient\ProcessWebhookJob;
use Puz\MailgunWebhooks\Exceptions\WebhookFailed;

class ProcessMailgunWebhookJob extends ProcessWebhookJob
{
    /**
     * @throws \Puz\MailgunWebhooks\Exceptions\WebhookFailed
     */
    public function handle()
    {
        $event = $this->getWebhookEvent($this->webhookCall->payload);

        event("mailgun-webhooks::{$event}", $this->webhookCall);

        $jobClass = $this->determineJobClass($event);

        if ($jobClass === '') {
            return;
        }

        if (!class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this->webhookCall);
        }

        dispatch(new $jobClass($this->webhookCall));
    }

    protected function determineJobClass(string $eventType): string
    {
        $jobConfigKey = str_replace('.', '_', $eventType);

        return config("mailgun-webhooks.jobs.{$jobConfigKey}", '');
    }

    /**
     * @param array $payload
     *
     * @return string
     * @throws \Puz\MailgunWebhooks\Exceptions\WebhookFailed
     */
    protected function getWebhookEvent(array $payload): string
    {
        if (!isset($payload['event-data'], $payload['event-data']['event']) || empty($payload['event-data']['event'])) {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        return $payload['event-data']['event'];
    }
}
