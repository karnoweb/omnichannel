# Phase 02 — Baseline Audit

Date: 2026-11-20 (Asia/Tehran)  
Package: `karnoweb/omnichannel` 13.1.0  
Host commit: `991840b7` (`Release Omnichannel 13.1.0`)

## Scope and evidence

This is the Phase 01 verification snapshot required by Phase 02 STEP 00. Code discovery used the indexed `omnichannel` graph, then source snippets and targeted searches for implementation details. The package suite ran from `D:/my-packages/omnichannel`; the host check ran against the installed package in `C:/laragon/www/karno-base`. No live channel API, Redis worker, or production database was contacted.

## Current verification results

| Check | Result | Status |
| --- | --- | --- |
| Package feature suite | `OK (57 tests, 7421 assertions)` on PHP 8.4.22 / PHPUnit 13.4.7 | Verified |
| Previously reported host `HubDevelopmentRoadmapTest` | Current run: 10 tests, 139 assertions passed; earlier 8-versus-7 failure did not reproduce. The PowerShell launcher emitted `Import-Clixml` warnings before the test report. | Verified; prior report is stale; runner warning recorded |
| Whole host suite | Not run in this baseline step | Pending Verification |
| Host PHPStan quality hook | Previous commit attempt found 5 type errors in two Commerce payment actions; these files are outside the Omnichannel package. | Host Integration Required; rerun after those host changes are addressed |
| MySQL/PostgreSQL upgrade path | Not run; migration review found a blocker below | Integration Required |
| SQLite upgrade from an existing install | Migration returns without changing constraints | Incomplete Implementation |
| Live channel APIs, Redis, and production workers | Not configured or contacted | Live API Required / Integration Required |

## Phase 01 acceptance status

| Area | Current evidence | Baseline classification |
| --- | --- | --- |
| Secret handling, branch checks, media intake, idempotency, state ownership, safe content updates | Implemented tests remain in the passing package suite. Findings below show remaining edge cases not covered by that suite. | Completed with residual bugs listed below |
| Delete, album IDs, deactivation, retained history | Core paths and channel capability matrix are present; WhatsApp has a manual batch inspection command. | Incomplete Implementation for automatic batch reconciliation; otherwise implemented |
| Retry, scheduling, and token refresh | Fixed job deadline, retry metadata, dispatch reservation, schedule snapshot, and token refresh locks exist and their tests pass. | Integration Required for shared-cache behavior; Incomplete Implementation for per-request rate limits and cross-driver retry metadata |
| Phase 01 STEP 09 | Target database upgrades, whole-host integration, multi-process queue/cache behavior, failure injection, and final release verification were not performed. | Integrated into Phase 02 STEP 03, STEP 06–09, and STEP 11 |

## Findings to carry forward

### Confirmed Bug

| ID | Finding | Evidence and consequence | Phase 02 destination |
| --- | --- | --- | --- |
| BASE-01 | History-retention migration uses `$publications` in both `up()` and `down()` without assigning it. | `database/migrations_squashed/2026_11_20_000002_preserve_publication_history.php`; the named table variables currently define only connections and logs. Non-SQLite upgrade/rollback will fail when reaching that schema call. | STEP 01 and STEP 06 |
| BASE-02 | Legacy idempotency lookup does not validate the recovered row against branch, schedule, or the current intent. | `PublishingService::create()` queries by the legacy key and immediately returns the row. The legacy key omits branch and `scheduled_at`, so a prior unscheduled or differently scheduled row can satisfy a new request. | STEP 01 |
| BASE-03 | Scheduled publication is not included in the atomic claim condition. | `publish()` can be called directly for a Scheduled row; `claimForPublishing()` checks status but not `scheduled_at <= now()`. The job's earlier schedule check is separate from this database update and leaves a race if the schedule changes between check and claim. | STEP 01 |
| BASE-04 | An ambiguous content update releases the active mutation marker. | `finishContentUpdateFailure()` deletes `omnichannel.active_operation` for every supplied status, including `unknown_outcome`; the update path calls it for an unknown driver result. A later mutation may start before the remote state is reconciled. | STEP 01 and STEP 04 |
| BASE-05 | IPv6 DNS pinning is malformed. | `HttpMediaBytesResolver::fetch()` removes every colon from the resolved address when constructing `CURLOPT_RESOLVE`. IPv6 address syntax is therefore lost before the transfer. | STEP 01 and STEP 07 |

### Incomplete Implementation

| ID | Finding | Evidence and consequence | Phase 02 destination |
| --- | --- | --- | --- |
| BASE-06 | SQLite upgrade migration is a no-op. | `up()` and `down()` return immediately for SQLite. Fresh installs get retention constraints from the squashed schema, but existing SQLite installs do not receive the upgrade. | STEP 01 and STEP 06 |
| BASE-07 | `branch_mode=inherit_global` remains in configuration, while branch scoping is fail-closed and does not implement that mode. | `config/omnichannel.php` exposes the setting; `BranchScope` and `OmnichannelContext` enforce explicit branch/system context. The option is misleading unless deprecated or implemented. | STEP 01 |
| BASE-08 | Rate limiting counts jobs, not outbound API requests. | `ThrottleChannelRequests` is applied at the job boundary; multi-request operations and direct update/delete/test/refresh calls are not counted individually. | STEP 02 |
| BASE-09 | Retry-After is not uniformly carried from all channel HTTP responses into retry scheduling. | Current documented path handles Bot API response metadata; other drivers may return only a failure class. | STEP 02 |
| BASE-10 | WhatsApp batch reconciliation is inspection-only. | The inspector stores remote status without finalizing per-item publication state; there is no automatic poller/finalizer. | STEP 04 and STEP 05 |

### Integration Required

| ID | Finding | Required evidence | Phase 02 destination |
| --- | --- | --- | --- |
| BASE-11 | Shared cache durability and lock behavior are unverified. | Run duplicate scheduler and token-refresh contention tests against the intended shared cache with multiple processes/workers. | STEP 03 and STEP 09 |
| BASE-12 | Database constraint migration and rollback are unverified on target engines. | Fresh install and upgrade/rollback tests on MySQL, PostgreSQL, and SQLite with existing rows and retained logs. | STEP 06 |
| BASE-13 | Actual host behavior beyond the targeted roadmap test is unverified. | Run package-host integration suite, including branch access and connection/history retention, and resolve environment failures separately. | STEP 07 and STEP 09 |
| BASE-14 | Download limits and handler-specific transfer behavior are not proven by HTTP fakes. | Exercise real cURL streaming in a controlled local test server, including IPv4/IPv6 pinning, oversize streaming, truncated transfer, and temp-file cleanup. | STEP 01 and STEP 07 |

### Live API Required

| ID | Finding | Required evidence | Phase 02 destination |
| --- | --- | --- | --- |
| BASE-15 | Official quota limits and channel-specific Retry-After behavior are not confirmed for every account/API. | Obtain verified platform/account documentation or sandbox responses; keep package defaults explicitly internal meanwhile. | STEP 02 and STEP 10 |
| BASE-16 | WhatsApp final batch semantics and per-item error mapping are not verified against a real catalog. | Use a designated test catalog to observe pending/completed/error responses and safe ID reconciliation. | STEP 05 and STEP 10 |
| BASE-17 | Remaining channel-specific media, delete/update, token refresh, and recovery behavior has only mock coverage. | Run only supported, non-destructive operations against designated test accounts; never treat mock results as live verification. | STEP 10 |

### Host Integration Required

| ID | Finding | Required evidence | Phase 02 destination |
| --- | --- | --- | --- |
| BASE-18 | The package still depends on host-provided branch/actor resolvers and media/product adapters at runtime. | Verify the host binds these contracts and scopes loaded connections/products from fresh database state. | STEP 07 and STEP 09 |
| BASE-19 | The prior host roadmap test failure no longer reproduces, but the full host suite has not been run. | Current targeted test is green; run the whole host suite before release and record unrelated failures distinctly. | STEP 09 |
| BASE-20 | The host PHPStan hook reported five payment-model type errors in `CompleteCommerceOnlinePaymentAction` and `InitiateCommerceOnlinePaymentAction`. | These are host Commerce issues rather than Omnichannel package findings; they still block a clean host quality gate. | STEP 09 |

## Phase 01 STEP 09 integration map

The old STEP 09 exit work is not treated as one undifferentiated final task:

- **STEP 03 — Queue Reliability:** multi-worker scheduling, shared-cache atomicity, lock expiry, dispatch and retry behavior.
- **STEP 06 — Database Compatibility & Upgrade:** fresh installs, in-place upgrades, rollback, schema constraints and preserved history across database engines.
- **STEP 07 — Complete Testing Infrastructure:** package regression, architecture checks, streaming-transfer tests and host adapter coverage.
- **STEP 09 — Host Integration Verification:** full host suite, queue/cache configuration, bindings and end-to-end adapter behavior.
- **STEP 10 — Live Channel Validation:** account-specific quotas and behavior that cannot be established with mocks.
- **STEP 11 — Standalone Package Preparation:** independent install, Composer validation/audit, CI and release documentation.

## Document reconciliation

`docs/hardening/AUDIT.md` is the historical STEP 00 snapshot from version 13.0.0; it predates the Phase 01 implementation and must not be read as current status. `docs/hardening/IMPLEMENTATION-CHECKLIST.md` records later step reports but its opening summary was stale. This audit is the consolidated Phase 02 baseline; new findings are intentionally carried into their corresponding implementation steps rather than silently changing the historical reports.

## STEP 00 result

Phase 01 verification is complete. No implementation code was changed in this step. The critical upgrade, schedule-claim, legacy-idempotency, ambiguous-update ownership, and IPv6 pinning findings must be handled before infrastructure work proceeds. The next authorized step is Phase 02 STEP 01.
