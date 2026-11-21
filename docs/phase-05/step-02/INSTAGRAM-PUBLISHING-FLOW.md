# INSTAGRAM-PUBLISHING-FLOW

```
Publication → validate → container POST /media → poll status_code → media_publish → external_id
```

| نوع | مسیر داخلی | API |
|-----|------------|-----|
| Post | `PublicationType::Post` | media + media_publish |
| Carousel | children در content | چند container + carousel |
| Reel/Story | capability اعلام‌شده | طبق InstagramDriver |

سقف داخلی: `channels.instagram.rate_limit` — **جدا** از سهمیه ۱۰۰/۲۴h رسمی (DEC-001).