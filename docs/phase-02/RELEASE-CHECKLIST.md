# Release checklist

- [x] Package tests green (`vendor/bin/phpunit --exclude-group redis`)
- [x] `composer validate --strict` (run in CI)
- [x] GitLab CI pipeline file present
- [x] Phase 02 documentation set under `docs/phase-02/`
- [ ] Host full suite green (pending)
- [ ] MySQL/PostgreSQL migration matrix (pending)
- [ ] Live channel validation (pending)
- [ ] Owner-approved public release tag (not requested)