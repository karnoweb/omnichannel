# Live validation matrix

Default CI/package tests do **not** call live APIs.

| Channel | Status | Notes |
| --- | --- | --- |
| demo | Mock Verified | In-package driver tests |
| telegram | Mock Verified | |
| bale | Mock Verified | |
| instagram | Mock Verified | Token refresh/live container needs test account |
| basalam | Mock Verified | |
| whatsapp | Mock Verified | Catalog batch auto-reconcile **disabled** (`whatsapp_catalog.auto_reconcile=false`) |
| rubika | Mock Verified | |
| gap | Mock Verified | |
| eitaa | Mock Verified | |

Run live probes only with explicit credentials and `@group live` tests when added.