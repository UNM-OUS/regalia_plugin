<?php

use DigraphCMS\Cache\CacheableState;
use DigraphCMS\Cache\CachedInitializer;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Plugins\Plugins;

require_once __DIR__ . '/vendor/autoload.php';

// run initial configuration
DB::addPhinxPath(realpath(__DIR__ . '/phinx'));
CachedInitializer::run(
    'initialization',
    function (CacheableState $state) {
        $state->mergeConfig(Config::parseJsonFile(__DIR__ . '/env.json'), true);
        $state->config('paths.base', __DIR__ . '/demo');
        $state->config('paths.web', __DIR__ . '/demo');
    }
);

// load composer plugins
Plugins::loadFromComposer(__DIR__ . '/composer.lock');

return
    [
        'paths' => [
            'migrations' => DB::migrationPaths(),
            'seeds' => DB::seedPaths(),
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'current',
            'current' => [
                'name' => 'Current environment',
                'connection' => DB::pdo()
            ]
        ],
        'version_order' => 'creation',
    ];
