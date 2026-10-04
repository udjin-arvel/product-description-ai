# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-03-22

### Added

- Framework-agnostic core: DeepSeek Vision client, payload builder, response parser, caching, logging hooks.
- Image inputs: Base64 data URLs and external HTTPS URLs.
- Laravel service provider, config publish, and `DeepSeek` facade.
- Symfony bundle with DI extension and configuration tree.
- Yii 2 application component and PSR-16 cache bridge.
- PHPUnit test suite, PHPStan level 8, PHP-CS-Fixer, GitHub Actions CI.
- Opt-in live API smoke test via `DEEPSEEK_SMOKE_TEST=1`.

### Notes

- Default vision model: `deepseek-flash` (configurable).
- Files API image references are planned for a future release.
