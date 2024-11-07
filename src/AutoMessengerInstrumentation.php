<?php

declare(strict_types=1);

namespace Riviera\Contrib\Instrumentation\LiipImagine;

use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use function OpenTelemetry\Instrumentation\hook;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

use Liip\ImagineBundle\Message\Handler\WarmupCacheHandler;

use Liip\ImagineBundle\Message\WarmupCache;

class AutoMessengerInstrumentation
{
    public const NAME = 'LiipImagineBundle';

    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation('uk.co.rivieratravel.contrib.LiipImagineBundle');
        
        hook(
            class: WarmupCacheHandler::class,
            function: '__invoke',
            pre: static function (WarmupCacheHandler $handler, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                $message = $params[0] ?? null;

                $builder = $instrumentation->tracer()
                   ->spanBuilder('WarmupCacheHandler')
                   ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                   ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                   ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                   ->setAttribute(TraceAttributes::CODE_LINENO, $lineno);
    
                if ($message && $message instanceof WarmupCache) {
                    $builder->setAttribute('liip.imagine.message.path', $message->getPath());
                    $builder->setAttribute('liip.imagine.message.force', $message->isForce());
                }
                
                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));
            },
            post: static function (WarmupCacheHandler $handler, array $params, $return, ?Throwable $exception) {
                $scope = Context::storage()->scope();
                if (!$scope) {
                    return;
                }

                $scope->detach();
                $span = Span::fromContext($scope->context());

                if ($exception) {
                    $span->recordException($exception, [TraceAttributes::EXCEPTION_ESCAPED => true]);
                    $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
                }

                $span->end();
            }
        );
    }
}
