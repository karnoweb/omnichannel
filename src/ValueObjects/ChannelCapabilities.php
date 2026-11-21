<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\ValueObjects;

use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\MediaType;
use Karnoweb\Omnichannel\Enums\PublicationType;

final readonly class ChannelCapabilities
{
    /**
     * @param  list<Capability>  $supported
     * @param  array<string, string>  $unsupportedReasons
     * @param  array<string, mixed>  $limits
     * @param  list<string>|null  $updateFields  Canonical PublicationContent fields accepted by Update. Null keeps legacy all-field behavior.
     * @param  list<string>|null  $clearUpdateFields  Fields that Update can explicitly clear. Null keeps legacy behavior.
     */
    private function __construct(
        public array $supported,
        public array $unsupportedReasons = [],
        public array $limits = [],
        public ?array $updateFields = null,
        public ?array $clearUpdateFields = null,
    ) {}

    /**
     * @param  list<Capability>  $supported
     * @param  array<string, string>  $unsupportedReasons
     * @param  array<string, mixed>  $limits
     */
    public static function make(
        array $supported,
        array $unsupportedReasons = [],
        array $limits = [],
        ?array $updateFields = null,
        ?array $clearUpdateFields = null,
    ): self {
        $seen = [];
        $unique = [];

        foreach ($supported as $capability) {
            if (! isset($seen[$capability->value])) {
                $seen[$capability->value] = true;
                $unique[] = $capability;
            }
        }

        return new self($unique, $unsupportedReasons, $limits, $updateFields, $clearUpdateFields);
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function supports(Capability $capability): bool
    {
        return in_array($capability, $this->supported, true);
    }

    public function supportsAll(Capability ...$capabilities): bool
    {
        foreach ($capabilities as $capability) {
            if (! $this->supports($capability)) {
                return false;
            }
        }

        return true;
    }

    public function supportsType(PublicationType $type): bool
    {
        return $this->supports($type->requiredCapability());
    }

    public function supportsMedia(MediaType $type): bool
    {
        return $this->supports($type->requiredCapability());
    }

    public function reasonFor(Capability $capability): ?string
    {
        return $this->unsupportedReasons[$capability->value] ?? null;
    }

    public function supportsUpdateField(string $field): bool
    {
        return $this->matchesUpdateField($this->updateFields, $field);
    }

    public function canClearUpdateField(string $field): bool
    {
        return $this->matchesUpdateField($this->clearUpdateFields, $field);
    }

    /** @param list<string>|null $fields */
    private function matchesUpdateField(?array $fields, string $field): bool
    {
        if ($fields === null) {
            return true;
        }

        foreach ($fields as $supportedField) {
            if ($supportedField === $field || $supportedField === '*') {
                return true;
            }

            if (str_ends_with($supportedField, '.*')
                && str_starts_with($field, substr($supportedField, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<PublicationType>
     */
    public function publicationTypes(): array
    {
        return array_values(array_filter(
            PublicationType::cases(),
            fn (PublicationType $type): bool => $this->supportsType($type)
        ));
    }

    /**
     * @return list<MediaType>
     */
    public function mediaTypes(): array
    {
        return array_values(array_filter(
            MediaType::cases(),
            fn (MediaType $type): bool => $this->supportsMedia($type)
        ));
    }

    public function limit(string $key, mixed $default = null): mixed
    {
        return data_get($this->limits, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'supported' => array_map(fn (Capability $c): string => $c->value, $this->supported),
            'unsupported_reasons' => $this->unsupportedReasons,
            'limits' => $this->limits,
            'update_fields' => $this->updateFields,
            'clear_update_fields' => $this->clearUpdateFields,
        ];
    }
}
