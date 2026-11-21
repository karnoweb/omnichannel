<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum Capability: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';

    case Post = 'post';
    case Carousel = 'carousel';
    case Reel = 'reel';
    case Story = 'story';
    case Product = 'product';
    case Message = 'message';
    case Album = 'album';

    case Update = 'update';
    case Delete = 'delete';
    case Deactivate = 'deactivate';

    case Scheduling = 'scheduling';
    case Hashtags = 'hashtags';
    case AltText = 'alt_text';
    case Thumbnail = 'thumbnail';
    case PriceAndStock = 'price_and_stock';
    case Pin = 'pin';
    case SilentDelivery = 'silent_delivery';
    case NativeScheduling = 'native_scheduling';

    public function label(): string
    {
        return trans('omnichannel::omnichannel.capability.' . $this->value);
    }

    public function group(): string
    {
        return match ($this) {
            self::Text, self::Image, self::Video => 'content',
            self::Post, self::Carousel, self::Reel, self::Story,
            self::Product, self::Message, self::Album => 'types',
            self::Update, self::Delete, self::Deactivate => 'operations',
            self::Scheduling, self::Hashtags, self::AltText, self::Thumbnail,
            self::PriceAndStock, self::Pin, self::SilentDelivery, self::NativeScheduling => 'features',
        };
    }
}
