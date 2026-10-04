# Release checklist (1.0)

1. Run `composer validate --strict`.
2. Run full CI locally: PHPUnit, PHPStan, PHP-CS-Fixer dry-run.
3. Run opt-in smoke test with `DEEPSEEK_SMOKE_TEST=1` and a valid API key.
4. Review public API surface (`ProductDescriptionService`, DTOs, adapters).
5. Update `CHANGELOG.md` and tag `v1.0.0`.
