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

use Liip\ImagineBundle\Service\FilterService;

class AutoFilterServiceInstrumentation
{
    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation('uk.co.rivieratravel.contrib.LiipImagineBundle');
        
        hook(
            class: FilterService::class,
            function: 'warmUpCache',
            pre: static function (FilterService $handler, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                $path = $params[0] ?? null;
                $filter = $params[1] ?? null;
                $resolver = $params[2] ?? null;
                $forced = $params[3] ?? null;

                $builder = $instrumentation->tracer()
                    ->spanBuilder(
                        sprintf(
                            'warmUpCache %s',
                            $filter
                        )
                    )
                   ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                   ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                   ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                   ->setAttribute(TraceAttributes::CODE_LINENO, $lineno);
    
                $builder->setAttribute('liip.imagine.filter.path', $path);
                $builder->setAttribute('liip.imagine.filter.force', $forced);
                
                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));
            },
            post: static function (FilterService $handler, array $params, $return, ?Throwable $exception) {
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
