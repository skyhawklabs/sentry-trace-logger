# sentry-trace-logger

Monolog processor that stamps the active Sentry `trace_id` (and `span_id` when
available) into every log record's `extra` payload. Built for CakePHP 5 apps
using a Monolog-backed log engine, but the processor itself is plain Monolog 3
and works anywhere.

Why: lets you grep `logs/error.log` by Sentry trace ID to pull exactly the log
lines that belong to a given exception. Required for the morning triage system
to correlate Sentry issues with surrounding log context.

## Install

Public on GitHub but not on Packagist — add it as a VCS repository in each app's `composer.json` (no credentials needed):

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/skyhawklabs/sentry-trace-logger.git" }
  ],
  "require": {
    "fidello/sentry-trace-logger": "^1.0"
  }
}
```

Then:

```bash
composer require fidello/sentry-trace-logger
```

No `plugin load` step — this is a plain library, not a CakePHP plugin.

## Wire it up

1. **Sentry SDK initialized** before the first log call (usually
   `config/bootstrap.php`):
   ```php
   \Sentry\init([
       'dsn' => env('SENTRY_DSN'),
       'traces_sample_rate' => 1.0,  // needed so trace IDs exist
   ]);
   ```

2. **Attach the processor to your Monolog-backed log engine** in
   `config/app.php`. Example with a `cakephp/monolog`-style engine that accepts
   a `processors` array:

   ```php
   'Log' => [
       'error' => [
           'className' => \SomeVendor\Log\Engine\MonologLog::class,
           'processors' => [
               new \Fidello\SentryTraceLogger\SentryTraceProcessor(),
           ],
           // ...
       ],
   ],
   ```

   If your engine doesn't accept a `processors` array, push it onto the
   underlying Monolog logger wherever you construct it:

   ```php
   $logger->pushProcessor(new \Fidello\SentryTraceLogger\SentryTraceProcessor());
   ```

## Output format

Configure your Monolog formatter to include `extra`. `LineFormatter`'s default
`"[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"` already
does — you'll see entries like:

```
[2026-04-13T06:42:11+00:00] error.ERROR: Undefined array key "user_id" [] {"trace_id":"8a3b...","span_id":"f91c..."}
```

Now `grep 8a3b error.log` pulls every line tied to that request.

## Uninstall

```bash
composer remove fidello/sentry-trace-logger
```

Remove the processor entries from `config/app.php`.
