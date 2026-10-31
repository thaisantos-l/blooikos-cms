# Contact REST API

`POST /wp-json/blooikos/v1/contact` accepts an anonymous UTF-8 JSON object, at most 32768 bytes. No authentication cookies, Authorization header or build-time nonce is needed. GET/HEAD do not submit; OPTIONS performs only preflight. This module is independent of GraphQL and the rebuild queue.

| Field | Rules after trimming |
| --- | --- |
| `name` | Required, plain text, 2–120 Unicode characters |
| `email` | Required, one valid address, at most 254; no CR/LF or recipient lists |
| `phone` | Required, at most 40 characters; 8–15 digits, spaces, parentheses, hyphens, optional initial `+` |
| `company` | Optional plain text, at most 120 characters |
| `message` | Required plain text, 10–3000 characters; line breaks preserved |
| `website` | Empty string (honeypot) |

Unknown fields and non-string values are rejected. Text is validated before and after WordPress sanitization. No message or visitor PII is stored. Validation never silently truncates text.

Responses contain `{ok, code, requestId}`, optionally `fieldErrors` or `retryAfter`. Field codes are `REQUIRED`, `INVALID`, `TOO_SHORT`, `TOO_LONG`. Responses never echo input. Codes/statuses:

- 200 `CONTACT_ACCEPTED`: `wp_mail` returned true, indicating transport acceptance, **not verified delivery**.
- 400 `INVALID_REQUEST` / `VALIDATION_ERROR`.
- 403 `ORIGIN_NOT_ALLOWED`.
- 413 `PAYLOAD_TOO_LARGE`; 415 `UNSUPPORTED_MEDIA_TYPE`.
- 429 `RATE_LIMITED`, with matching `Retry-After` header and `retryAfter` seconds.
- 503 `CONTACT_UNAVAILABLE` (recipient/storage) / `MAIL_UNAVAILABLE` (false/exception).

The current `options_blooikos_site_email` is read at each POST. It must be one valid address; there is no `admin_email` fallback. The subject is fixed, the message is plain text, and the visitor address is only a validated `Reply-To`. The configured WordPress sender remains responsible for `From`. No autoresponse, SMTP setup, attachments or marketing subscription is added.

## Instance configuration

`BLOOIKOS_CONTACT_ALLOWED_ORIGINS` is a server-side array of exact origins, without paths or trailing slashes. Default: `['https://blooikos.com.br']`. HTTPS is required, except explicitly listed HTTP loopback origins when `WP_ENVIRONMENT_TYPE` is `local`. For an isolated local review, for example, `['http://127.0.0.1:3002']`. Configuring this constant is an operational action; deploying the code does not edit wp-config or environment settings.

Missing, `null`, malformed and unlisted Origin values are rejected. Valid origins receive exact ACAO, `Vary: Origin`, methods POST/OPTIONS, allowed header Content-Type and exposed Retry-After. No credential allowance. The late `rest_pre_serve_request` filter replaces core CORS only for this route, including errors. All contact responses use `Cache-Control: no-store` and `X-Robots-Tag: noindex`. Origin is not bot authentication.

## Shared quotas and retention

Two private InnoDB tables use the site's WordPress prefix: `blooikos_contact_quota` and `blooikos_contact_events`. Activation installs them idempotently; the first valid processing attempt after an already-active plugin upgrade also installs them. GET, OPTIONS and ordinary editorial reads do not install schema. Database DDL, InnoDB transactions and MySQL/MariaDB advisory locks must be available. Storage/lock failure returns 503 before transport.

A database advisory lock covers checking and reserving both fixed windows in one transaction: five valid processing attempts per connection client in 15 minutes, and 60 transport attempts globally in one hour. Each window starts with its first reservation. Failed mail attempts count. Exhausted windows return the longest applicable remaining duration. Counters are not refunded after transport failure. A denied reservation changes neither counter.

Only normalized `REMOTE_ADDR`, HMAC-SHA256 with WordPress `wp_salt('auth')`, identifies the client. Forwarded-IP headers are ignored. Behind a proxy that exposes one connection address, visitors share a quota; trusted proxy handling requires a separate explicit operational decision. No raw IP is persisted or returned. A salt change changes client identifiers, but does not reset the global budget.

Expired quota rows are logically invalid immediately and up to 100 are cleaned on the next valid reservation. Capacity is bounded below 2048 rows; capacity exhaustion fails closed. Diagnostic metadata is stored in 64 hash-selected slots, overwriting older occupants: server requestId, outcome code, duration and Unix timestamp only. Entries older than 24 hours are removed at the next logged POST (no background cron). This is a bounded diagnostic sample, not an exhaustive audit history. Before schema exists or when storage fails, the `blooikos_core_contact_result` action still emits those four safe values in memory; no PII/error dumps are written to PHP logs. Physical expired rows can remain until subsequent traffic, within the hard limits. Deactivation does not erase content or tables.

No test bypass is included. Tests must use a separate WordPress instance with a `pre_wp_mail` sink intercepting all transport. Publish/configure the CMS first, verify availability and CORS without sending, then publish the frontend. A real delivery test requires separate authorization and verification at the recipient.
