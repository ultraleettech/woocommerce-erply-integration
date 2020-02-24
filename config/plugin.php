<?php

use Monolog\Logger;
use Ultraleet\WP\WP as ULWP;
use Monolog\Handler\StreamHandler;
use Ultraleet\WcErply\Components\WP;
use Monolog\Formatter\LineFormatter;
use Ultraleet\WP\Scheduler\Scheduler;
use Ultraleet\WcErply\Components\Cron;
use Ultraleet\WcErply\Components\ErplyApi;
use Ultraleet\WcErply\Components\Settings;
use Ultraleet\WcErply\Components\ViewRenderer;
use Ultraleet\WcErply\Components\Router\Router;
use Ultraleet\WcErply\Components\Synchronization;
use function DI\get;
use function DI\create;

return [
    // Ultraleet WP
    'ulwp' => create(ULWP::class)
        ->constructor(include 'ulwp.php'),

    // WP integration (hooks, etc)
    'wp' => create(WP::class),

    // View renderer
    'view' => create(ViewRenderer::class)
        ->constructor(ULTRALEET_WCERPLY_VIEW_PATH),

    // Logger for synchronizer output handling
    'logger' => function () {
        $level = WP_DEBUG ?
            Logger::DEBUG :
            Logger::INFO;

        $logger = new Logger('wcerply');
        $formatter = new LineFormatter("[%datetime%] %level_name%: %message% %context%\n", null, true);

        $date = date('Y-m-d');
        $handler = new StreamHandler(ULTRALEET_WCERPLY_VAR_PATH . "sync-$date.log", $level);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        if (!WP_DEBUG) {
            $date = date('Y-m-d');
            $handler = new StreamHandler(ULTRALEET_WCERPLY_VAR_PATH . "sync-debug-$date.log", Logger::DEBUG);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
        }

        return $logger;
    },

    // Erply API library
    'api' => create(ErplyApi::class),

    // Erply synchronization
    'sync' => create(Synchronization::class),

    // WP Cron integration
    'cron' => create(Cron::class),

    // Router
    'router' => create(Router::class),

    // Scheduler
    'scheduler' => create(Scheduler::class)
        ->constructor(ULTRALEET_WCERPLY_FILE)
        ->method('setLogger', get('logger')),

    // WP settings API integration
    'settings' => create(Settings::class),
];
