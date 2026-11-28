<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Results;

final readonly class ValidationResult
{
    /**
     * @param  list<array{code: string, message: string, context: array<string, mixed>}>  $errors
     * @param  list<array{code: string, message: string, context: array<string, mixed>}>  $warnings
     */
    private function __construct(
        public array $errors = [],
        public array $warnings = [],
    ) {}

    public static function make(): self
    {
        return new self;
    }

    public static function valid(): self
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function error(string $code, string $message, array $context = []): self
    {
        return (new self)->withError($code, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function withError(string $code, string $message, array $context = []): self
    {
        return new self(
            [...$this->errors, ['code' => $code, 'message' => $message, 'context' => $context]],
            $this->warnings,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function withWarning(string $code, string $message, array $context = []): self
    {
        return new self(
            $this->errors,
            [...$this->warnings, ['code' => $code, 'message' => $message, 'context' => $context]],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function errorIf(bool $condition, string $code, string $message, array $context = []): self
    {
        return $condition ? $this->withError($code, $message, $context) : $this;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function warnIf(bool $condition, string $code, string $message, array $context = []): self
    {
        return $condition ? $this->withWarning($code, $message, $context) : $this;
    }

    public function merge(self $other): self
    {
        return new self(
            [...$this->errors, ...$other->errors],
            [...$this->warnings, ...$other->warnings],
        );
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return ! $this->passes();
    }

    /**
     * @return list<string>
     */
    public function errorMessages(): array
    {
        return array_map(static fn (array $e): string => $e['message'], $this->errors);
    }

    /**
     * @return list<string>
     */
    public function warningMessages(): array
    {
        return array_map(static fn (array $w): string => $w['message'], $this->warnings);
    }

    /**
     * @return list<string>
     */
    public function errorCodes(): array
    {
        return array_map(static fn (array $e): string => $e['code'], $this->errors);
    }

    public function hasErrorCode(string $code): bool
    {
        return in_array($code, $this->errorCodes(), true);
    }

    /**
     * @return list<string>
     */
    public function warningCodes(): array
    {
        return array_map(static fn (array $w): string => $w['code'], $this->warnings);
    }

    public function hasWarningFor(string $code): bool
    {
        return in_array($code, $this->warningCodes(), true);
    }

    public function firstErrorMessage(): ?string
    {
        return $this->errorMessages()[0] ?? null;
    }

    public function summary(): string
    {
        return implode(' ', $this->errorMessages());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['errors' => $this->errors, 'warnings' => $this->warnings];
    }
}
