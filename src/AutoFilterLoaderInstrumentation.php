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

use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;

class AutoFilterLoaderInstrumentation
{
    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation('uk.co.rivieratravel.contrib.LiipImagineBundle');
        
        hook(
            class: LoaderInterface::class,
            function: 'load',
            pre: static function (LoaderInterface $handler, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                $image = $params[0] ?? null;
                $options = $params[1] ?? null;
                
                $loaderClass = $handler::class;

                $builder = $instrumentation->tracer()
                    ->spanBuilder(
                        sprintf(
                            'Filter %s',
                            $class
                        )
                    )
                   ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                   ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                   ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                   ->setAttribute(TraceAttributes::CODE_LINENO, $lineno);
    
                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));
            },
            post: static function (LoaderInterface $handler, array $params, $return, ?Throwable $exception) {
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
