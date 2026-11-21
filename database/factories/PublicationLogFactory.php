<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Karnoweb\Omnichannel\Enums\LogOperation;
use Karnoweb\Omnichannel\Enums\LogStatus;
use Karnoweb\Omnichannel\Models\PublicationLog;
use Karnoweb\Omnichannel\Omnichannel;

/**
 * @extends Factory<PublicationLog>
 */
class PublicationLogFactory extends Factory
{
    public function modelName(): string
    {
        return Omnichannel::model('publication_log');
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now()->subSeconds(2);

        return [
            'publication_id' => PublicationFactory::new(),
            'operation' => LogOperation::Publish,
            'status' => LogStatus::Succeeded,
            'attempt' => 1,
            'started_at' => $startedAt,
            'finished_at' => now(),
            'duration_ms' => 2000,
            'retryable' => false,
            'request' => ['endpoint' => 'POST /media'],
            'response' => ['status' => 200],
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => LogStatus::Failed,
            'failure_class' => 'transient',
            'error_code' => 'http.500',
            'error_message' => 'Upstream error.',
            'retryable' => true,
        ]);
    }
}
