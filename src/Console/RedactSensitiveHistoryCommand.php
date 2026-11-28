<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Services\SecretRedactor;

final class RedactSensitiveHistoryCommand extends Command
{
    protected $signature = 'omnichannel:redact-history {--apply : Write the redacted values to the database}';

    protected $description = 'Preview or redact sensitive values in Omnichannel logs and error fields';

    public function handle(SecretRedactor $redactor): int
    {
        $apply = (bool) $this->option('apply');
        $changed = 0;

        foreach ([
            ['publication_log', ['error_message', 'request', 'response', 'context']],
            ['publication', ['error_message']],
            ['connection', ['status_message', 'metadata']],
        ] as [$modelKey, $fields]) {
            $model = Omnichannel::model($modelKey);
            if (! is_subclass_of($model, Model::class)) {
                $this->warn("Skipped {$modelKey}: configured model is invalid.");

                continue;
            }

            $model::query()->orderBy($model::query()->getModel()->getQualifiedKeyName())->chunkById(100, function ($records) use ($fields, $redactor, $apply, &$changed, $modelKey): void {
                foreach ($records as $record) {
                    $updates = [];
                    foreach ($fields as $field) {
                        $original = $record->getAttribute($field);
                        $secrets = match ($modelKey) {
                            'connection' => $record->credentials ?? [],
                            'publication' => $record->connection?->credentials ?? [],
                            default => $record->connection?->credentials ?? $record->publication?->connection?->credentials ?? [],
                        };
                        if (is_string($original)) {
                            $clean = $redactor->redactString($original, $secrets);
                        } elseif (is_array($original)) {
                            $clean = $redactor->redact($original, 0, $secrets);
                        } else {
                            continue;
                        }
                        if ($clean !== $original) {
                            $updates[$field] = $clean;
                        }
                    }
                    if ($updates === []) {
                        continue;
                    }
                    $changed++;
                    if ($apply) {
                        $record->forceFill($updates)->save();
                    }
                }
            });
            $this->line("Scanned {$modelKey}.");
        }

        $this->info(($apply ? 'Updated' : 'Would update')." {$changed} records.");
        if (! $apply) {
            $this->comment('Dry run only. Add --apply to write redacted values.');
        }

        return self::SUCCESS;
    }
}
