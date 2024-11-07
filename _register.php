<?php

declare(strict_types=1);

use Riviera\Contrib\Instrumentation\LiipImagine\AutoMessengerInstrumentation;
use Riviera\Contrib\Instrumentation\LiipImagine\AutoFilterServiceInstrumentation;
use Riviera\Contrib\Instrumentation\LiipImagine\AutoFilterLoaderInstrumentation;
use OpenTelemetry\SDK\Sdk;

if (class_exists(Sdk::class) && Sdk::isInstrumentationDisabled(AutoMessengerInstrumentation::NAME) === true) {
    return;
}

if (extension_loaded('opentelemetry') === false) {
    trigger_error('The opentelemetry extension must be loaded in order to autoload the OpenTelemetry AutoMapper auto-instrumentation', E_USER_WARNING);

    return;
}

AutoMessengerInstrumentation::register();
AutoFilterServiceInstrumentation::register();
AutoFilterLoaderInstrumentation::register();
