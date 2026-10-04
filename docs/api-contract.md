# DeepSeek API contract (verified for package defaults)

Source: [DeepSeek Vision guide](https://api-docs.deepseek.com/guides/vision), [Chat Completions API](https://api-docs.deepseek.com/api/create-chat-completion).

## Endpoint

- `POST https://api.deepseek.com/chat/completions`
- Auth: `Authorization: Bearer <API_KEY>`

## Default vision model

- `deepseek-flash` (configurable via `ClientConfig` / framework config)

## Image input (v1.0 scope)

| Method | Limits | Package support |
|--------|--------|-----------------|
| Base64 data URL | Shared request body limits | Yes (`Base64Image`) |
| External HTTPS URL | URL ≤ 8192 chars, file ≤ 32 MiB | Yes (`UrlImage`) |
| Files API `file_id` | File ≤ 64 MiB | Deferred post-1.0 |

## Supported formats

- JPEG, PNG, GIF, WebP

## `detail` parameter

- Values: `low`, `high`, `original`, `auto`
- `low`: downsample to 512×512 (cheaper/faster)

## Structured JSON output

- Use `response_format: { "type": "json_object" }`
- Prompt must mention **json** (API requirement)

## Compatibility matrix (package targets)

| Component | Versions |
|-----------|----------|
| PHP | 8.1 – 8.4 |
| Laravel | 10, 11, 12 |
| Symfony | 6.4, 7.x |
| Yii 2 | 2.0.49+ |
