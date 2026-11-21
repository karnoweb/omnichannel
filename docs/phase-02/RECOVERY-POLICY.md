# Operation recovery policy

## Principles

- Stuck records must not trigger unsafe automatic resend.
- Manual decisions require actor, reason, and evidence metadata.
- Confirmed success requires `external_id` evidence.
- Drivers may optionally implement `ReconcilableChannelDriver`.

## Tools

| Tool | Behavior |
| --- | --- |
| `omnichannel:inspect-stuck` | Read-only listing |
| `PublicationRecoveryService::recordManualDecision()` | Audited manual outcome |
| `PublicationRecoveryService::attemptDriverReconciliation()` | Optional driver hook |

## Outcomes

`ReconciliationResult` constants: `confirmed_success`, `confirmed_failure`, `pending`, `partial_success`, `unknown`, `unsupported`.