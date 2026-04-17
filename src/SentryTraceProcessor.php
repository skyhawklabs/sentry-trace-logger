<?php
declare(strict_types=1);

namespace Fidello\SentryTraceLogger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;

/**
 * Monolog processor that stamps every log record with the active Sentry
 * trace_id and span_id (when present). Makes log lines greppable by trace ID
 * so you can correlate a Sentry issue with surrounding log context.
 */
class SentryTraceProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $hub = SentrySdk::getCurrentHub();
        $span = $hub->getSpan();

        $traceId = null;
        $spanId = null;

        if ($span !== null) {
            $traceId = (string)$span->getTraceId();
            $spanId = (string)$span->getSpanId();
        } else {
            // Fall back to propagation context (set on every request even without a span)
            $hub->configureScope(function (Scope $scope) use (&$traceId): void {
                $traceId = (string)$scope->getPropagationContext()->getTraceId();
            });
        }

        $extra = $record->extra;
        if ($traceId) {
            $extra['trace_id'] = $traceId;
        }
        if ($spanId) {
            $extra['span_id'] = $spanId;
        }

        return $record->with(extra: $extra);
    }
}
