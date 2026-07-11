<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendWebhookJob implements ShouldQueue
{
    use Queueable;

    public string $event;

    public array $data;

    public int $tries = 3;

    public function __construct(string $event, array $data)
    {
        $this->event = $event;
        $this->data = $data;
    }

    public function backoff(): array
    {
        return [5, 15];
    }

    public function handle(): void
    {
        Http::withToken(config('blog.webhook_secret'))
            ->timeout(30)
            ->post(config('blog.webhook_url'), [
                'event' => $this->event,
                'data' => $this->data,
            ])
            ->throw();
    }
}
