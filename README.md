# OpenTelemetry auto-instrumentation for liip/imagine-bundle

Please read https://opentelemetry.io/docs/instrumentation/php/automatic/ for instructions on how to
install and configure the extension and SDK.

## Overview
Auto-instrumentation hooks are registered via composer, and spans will automatically be created for the
following functions:
- `WarmupCacheHandler::__invoke`
- `FilterService::warmUpCache`
- `LoaderInterface::load`


## Configuration

The extension can be disabled via [runtime configuration](https://opentelemetry.io/docs/instrumentation/php/sdk/#configuration):

```shell
OTEL_PHP_DISABLED_INSTRUMENTATIONS=LiipImagineBundle
```
