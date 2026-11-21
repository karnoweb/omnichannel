<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum PublicationType: string
{
    case Post = 'post';
    case Carousel = 'carousel';
    case Reel = 'reel';
    case Story = 'story';
    case Product = 'product';
    case Message = 'message';
    case Album = 'album';

    public function label(): string
    {
        return trans('omnichannel::omnichannel.type.' . $this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Post => 'o-photo',
            self::Carousel => 'o-square-2-stack',
            self::Reel => 'o-film',
            self::Story => 'o-bolt',
            self::Product => 'o-shopping-bag',
            self::Message => 'o-chat-bubble-left-right',
            self::Album => 'o-photo',
        };
    }

    public function requiredCapability(): Capability
    {
        return match ($this) {
            self::Post => Capability::Post,
            self::Carousel => Capability::Carousel,
            self::Reel => Capability::Reel,
            self::Story => Capability::Story,
            self::Product => Capability::Product,
            self::Message => Capability::Message,
            self::Album => Capability::Album,
        };
    }

    public function requiresMedia(): bool
    {
        return ! in_array($this, [self::Product, self::Message], true);
    }

    public function allowsMultipleMedia(): bool
    {
        return in_array($this, [self::Carousel, self::Product, self::Album], true);
    }

    public function isConversational(): bool
    {
        return in_array($this, [self::Message, self::Album], true);
    }
}
