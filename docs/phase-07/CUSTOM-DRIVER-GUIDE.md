# راهنمای درایور سفارشی (WP-07-05)

مرجع معماری: [DRIVER-ARCHITECTURE.md](../phase-04/step-05/DRIVER-ARCHITECTURE.md).

## مراحل

1. کلاس `ChannelDriver` با `key()` و `capabilities(): ChannelCapabilities`
2. متدهای `publish`, `update`, `validate`, `testConnection` و غیره مطابق قابلیت‌ها
3. ترجیح: `*ApiClient` + transformer جدا (الگوی `BotApiDriver`, `BasalamDriver`)
4. ثبت درایور در میزبان — یکی از دو روش زیر

### روش A — config

```php
// config/omnichannel.php (میزبان پس از publish)
'drivers' => [
    'my_channel' => \App\Omnichannel\MyChannelDriver::class,
],
```

رکورد `Channel` با `driver = my_channel`.

### روش B — `ChannelManager::extend`

در `AppServiceProvider::boot`:

```php
use Karnoweb\Omnichannel\Services\ChannelManager;

$this->app->make(ChannelManager::class)->extend('my_channel', fn ($app) => new MyChannelDriver());
```

## قابلیت‌ها

- فقط capabilityهایی که در `capabilities()->supported` هستند در `PublishingService` مجازند.
- `PublicationType::Product` نیازمند `Capability::Product`.

## تست

- الگوی [DriverContractTest](../../tests/Drivers/DriverContractTest.php)
- HTTP mock در `tests/Feature/` برای مسیر publish/update

## نمونه حداقلی

[docs/examples/driver/MinimalDemoDriver.php.example](../../examples/driver/MinimalDemoDriver.php.example) — **در config پیش‌فرض پکیج ثبت نشده**.

## وابستگی به karno-base

الزامی نیست. میزبان هر autoload و namespace خود را نگه می‌دارد؛ فقط قراردادهای پکیج را implement کند.