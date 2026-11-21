# Queue reliability — current implementation

Status: Pending Verification. Scope: SQLite automated tests. No Redis or live worker validation is claimed.

## Implemented and tested

- A scheduled claim compares database status and timestamp against the loaded snapshot and requires the due time to have arrived.
- The job passes its scheduled timestamp to the service; refreshing the model cannot erase the expected schedule. Changed schedules return `publication.schedule_changed` before a request.
- Dispatch failure releases the scheduler reservation and propagates the queue exception. A regression verifies the reservation is absent afterwards.
- Retry deadline is stored in the publication using a conditional update of a nullable integer timestamp. Competing/new jobs read that same deadline; constructing another job cannot extend the operation window. Scheduled jobs start their window no earlier than their due time.
- Expired jobs return before external publication. Existing records acquire a deadline on first new job construction; drain old serialized jobs during upgrade.
- `omnichannel:inspect-stuck` displays only IDs, status, time and operation; it does not release ownership, resend or expose credentials. Positive age and a limit of 1–1000 are enforced. Filters accept branch and channel IDs. This is a trusted CLI/system operation, not an untrusted HTTP entry point.

## Database upgrade

Apply all pending package migrations before restarting workers. `2026_11_20_000004_add_publication_retry_deadline.php` adds nullable `retry_deadline_timestamp`; no existing row deadline is invented during migration. Back up before rollback; dropping the column loses the persisted retry window. A new publication intent has a new deadline. Retrying an expired intent does not automatically authorize a new window.

The history repair migration reapplies constraints for previously recorded SQLite no-op migrations. Its down method intentionally retains safety constraints; rollback of the original retention migration remains explicit.

## Outstanding engineering

- Validate worker timeout < queue retry-after and lock lifetime against actual deployment configuration.
- Multi-worker Redis contention: run `RedisRateLimitIntegrationTest` and queue integration under production Redis.
- Optional `ReconcilableChannelDriver` implementations per channel for automated stuck recovery.

## External validation needed

Owner requested SQLite-only database testing. MySQL/PostgreSQL are unverified and excluded from this iteration. Redis/workers and live channel tests require the intended non-production environment; no production account should be used for destructive validation.
