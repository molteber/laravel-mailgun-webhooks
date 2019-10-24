<?php

namespace Puz\MailgunWebhooks\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Spatie\WebhookClient\Models\WebhookCall;

class IntegrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        Route::mailgunWebhooks('mailgun-webhooks');
        Route::mailgunWebhooks('mailgun-webhooks/{configKey}');

        config(['mailgun-webhooks.jobs' => ['opened' => DummyJob::class]]);
        cache()->clear();
    }

    /** @test */
    public function it_can_handle_a_valid_request()
    {
        $payload = [
            'event' => 'opened',
        ];

        $webhookPayload = $this->webhookPayload($payload);

        $this->withoutExceptionHandling()
            ->postJson('mailgun-webhooks', $webhookPayload)
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertEquals('opened', $webhookCall->payload['event-data']['event']);
        $this->assertEquals($webhookPayload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('mailgun-webhooks::opened', function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function a_request_with_an_invalid_signature_wont_be_logged()
    {
        $payload = [
            'event' => 'opened',
        ];
        $webhookPayload = $this->webhookPayload($payload);
        $webhookPayload['signature']['signature'] = 'invalid';

        $this
            ->postJson('mailgun-webhooks', $webhookPayload)
            ->assertStatus(500);

        $this->assertCount(0, WebhookCall::get());

        Event::assertNotDispatched('mailgun-webhooks::opened');

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function a_request_with_an_invalid_payload_will_be_logged_but_events_and_jobs_will_not_be_dispatched()
    {
        $payload = ['invalid_payload'];
        $webhookPayload = $this->webhookPayload($payload);

        $this
            ->postJson('mailgun-webhooks', $webhookPayload)
            ->assertStatus(400);

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertFalse(isset($webhookCall->payload['event-data']['event']));
        $this->assertEquals(['invalid_payload'], $webhookCall->payload['event-data']);

        $this->assertEquals('Webhook call id `1` did not contain a type. Valid Stripe webhook calls should always contain a type.',
            $webhookCall->exception['message']);

        Event::assertNotDispatched('mailgun-webhooks::opened');

        $this->assertNull(cache('dummyjob'));
    }

    /** @test * */
    public function a_request_with_a_config_key_will_use_the_correct_signing_secret()
    {
        config()->set('mailgun-webhooks.signing_secret', 'secret1');
        config()->set('mailgun-webhooks.signing_secret_somekey', 'secret2');

        $payload = [
            'event' => 'opened',
        ];

        $webhookPayload = $this->webhookPayload($payload, 'somekey');

        $this->withExceptionHandling()
            ->postJson('mailgun-webhooks/somekey', $webhookPayload)
            ->assertSuccessful();
    }

    protected function webhookPayload(array $payload, $configKey = null): array
    {
        return [
            'signature' => $this->determineWebhookSignature($payload, $configKey),
            'event-data' => $payload,
        ];
    }
}
