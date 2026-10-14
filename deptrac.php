<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\Collector\DirectoryConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $config
        ->paths('app')
        ->layers(
            $catalogCore = Layer::withName('Catalog Core')->collectors(
                DirectoryConfig::create('app/Domain/Catalog/(?!Actions|Data).*'),
            ),
            $catalogData = Layer::withName('Catalog Data')->collectors(
                DirectoryConfig::create('app/Domain/Catalog/Data/.*'),
            ),
            $catalogActions = Layer::withName('Catalog Actions')->collectors(
                DirectoryConfig::create('app/Domain/Catalog/Actions/.*'),
            ),
            $ordersCore = Layer::withName('Orders Core')->collectors(
                DirectoryConfig::create('app/Domain/Orders/(?!Actions|Data).*'),
            ),
            $ordersData = Layer::withName('Orders Data')->collectors(
                DirectoryConfig::create('app/Domain/Orders/Data/.*'),
            ),
            $ordersActions = Layer::withName('Orders Actions')->collectors(
                DirectoryConfig::create('app/Domain/Orders/Actions/.*'),
            ),
            $identityCore = Layer::withName('Identity Core')->collectors(
                DirectoryConfig::create('app/Domain/Identity/(?!Actions|Data).*'),
            ),
            $identityData = Layer::withName('Identity Data')->collectors(
                DirectoryConfig::create('app/Domain/Identity/Data/.*'),
            ),
            $identityActions = Layer::withName('Identity Actions')->collectors(
                DirectoryConfig::create('app/Domain/Identity/Actions/.*'),
            ),
            $sharedCore = Layer::withName('Shared Core')->collectors(
                DirectoryConfig::create('app/Domain/Shared/(?![^/]+/(Actions|Data)/).*'),
            ),
            $sharedData = Layer::withName('Shared Data')->collectors(
                DirectoryConfig::create('app/Domain/Shared/[^/]+/Data/.*'),
            ),
            $sharedActions = Layer::withName('Shared Actions')->collectors(
                DirectoryConfig::create('app/Domain/Shared/[^/]+/Actions/.*'),
            ),
            $http = Layer::withName('HTTP Adapters')->collectors(
                DirectoryConfig::create('app/Http/.*'),
            ),
            $filament = Layer::withName('Filament Adapters')->collectors(
                DirectoryConfig::create('app/Filament/.*'),
            ),
        )
        ->rulesets(
            Ruleset::forLayer($catalogCore)->accesses($catalogCore, $sharedCore),
            Ruleset::forLayer($catalogData)->accesses($catalogCore, $catalogData),
            Ruleset::forLayer($catalogActions)->accesses($catalogCore, $catalogData, $catalogActions, $sharedCore, $sharedActions),
            Ruleset::forLayer($ordersCore)->accesses($catalogCore, $identityCore, $ordersCore),
            Ruleset::forLayer($ordersData)->accesses($ordersCore, $ordersData),
            Ruleset::forLayer($ordersActions)->accesses($catalogCore, $identityCore, $ordersCore, $ordersData, $ordersActions),
            Ruleset::forLayer($identityCore)->accesses($identityCore),
            Ruleset::forLayer($identityData)->accesses($identityCore, $identityData),
            Ruleset::forLayer($identityActions)->accesses($identityCore, $identityData, $identityActions),
            Ruleset::forLayer($sharedCore)->accesses($sharedCore),
            Ruleset::forLayer($sharedData)->accesses($sharedCore, $sharedData),
            Ruleset::forLayer($sharedActions)->accesses($catalogCore, $sharedCore, $sharedData, $sharedActions),
            Ruleset::forLayer($http)->accesses(
                $catalogCore,
                $catalogData,
                $catalogActions,
                $ordersCore,
                $ordersData,
                $ordersActions,
                $identityCore,
                $identityData,
                $identityActions,
                $sharedCore,
                $sharedData,
                $sharedActions,
            ),
            Ruleset::forLayer($filament)->accesses(
                $catalogCore,
                $catalogData,
                $catalogActions,
                $ordersCore,
                $ordersData,
                $ordersActions,
                $identityCore,
                $identityData,
                $identityActions,
                $sharedCore,
                $sharedData,
                $sharedActions,
            ),
        );
};
