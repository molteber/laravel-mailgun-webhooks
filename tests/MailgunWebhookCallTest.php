<?php

namespace Puz\MailgunWebhooks\Tests;

use Illuminate\Support\Facades\Event;
use Puz\MailgunWebhooks\Exceptions\WebhookFailed;
use Spatie\WebhookClient\Models\WebhookCall;
use Puz\MailgunWebhooks\ProcessMailgunWebhookJob;

class MailgunWebhookCallTest extends TestCase
{
    /** @var \Puz\MailgunWebhooks\ProcessMailgunWebhookJob */
    public $processStripeWebhookJob;

    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['mailgun-webhooks.jobs' => ['opened' => DummyJob::class]]);

        $this->webhookCall = WebhookCall::create([
            'name' => 'mailgun',
            'payload' => ['event-data' => ['event' => 'opened']],
        ]);

        $this->processStripeWebhookJob = new ProcessMailgunWebhookJob($this->webhookCall);
    }

    /** @test */
    public function it_will_fire_off_the_configured_job()
    {
        $this->processStripeWebhookJob->handle();

        $this->assertEquals($this->webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['mailgun-webhooks.jobs' => ['failed' => DummyJob::class]]);

        $this->processStripeWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['mailgun-webhooks.jobs' => []]);

        $this->processStripeWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured()
    {
        config(['mailgun-webhooks.jobs' => ['failed' => DummyJob::class]]);

        $this->processStripeWebhookJob->handle();

        $webhookCall = $this->webhookCall;

        Event::assertDispatched("mailgun-webhooks::{$webhookCall->payload['event-data']['event']}",
            function ($event, $eventPayload) use ($webhookCall) {
                $this->assertInstanceOf(WebhookCall::class, $eventPayload);
                $this->assertEquals($webhookCall->id, $eventPayload->id);

                return true;
            });

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_throw_when_job_class_does_not_exist()
    {
        config(['mailgun-webhooks.jobs' => ['opened' => '\InvalidClass']]);

        try {
            $this->processStripeWebhookJob->handle();
            self::fail('Should throw exception when job class does not exist');
        } catch (\Exception $exception) {
            $this->assertInstanceOf(WebhookFailed::class, $exception);
        }

        $this->assertNull(cache('dummyjob'));
    }
}
