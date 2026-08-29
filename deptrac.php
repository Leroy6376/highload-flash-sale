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
            $catalog = Layer::withName('Catalog')->collectors(
                DirectoryConfig::create('app/Domain/Catalog/(?!Actions|Contracts|Events).*'),
            ),
            $orders = Layer::withName('Orders')->collectors(
                DirectoryConfig::create('app/Domain/Orders/(?!Actions|Contracts|Events).*'),
            ),
            $promotions = Layer::withName('Promotions')->collectors(
                DirectoryConfig::create('app/Domain/Promotions/(?!Actions|Contracts|Events).*'),
            ),
            $actions = Layer::withName('Actions')->collectors(
                DirectoryConfig::create('app/Domain/[^/]+/Actions/.*'),
            ),
            $contracts = Layer::withName('Contracts')->collectors(
                DirectoryConfig::create('app/Domain/[^/]+/Contracts/.*'),
            ),
            $events = Layer::withName('Events')->collectors(
                DirectoryConfig::create('app/Domain/[^/]+/Events/.*'),
            ),
        )
        ->rulesets(
            Ruleset::forLayer($catalog)->accesses($catalog, $actions, $contracts, $events),
            Ruleset::forLayer($orders)->accesses($orders, $actions, $contracts, $events),
            Ruleset::forLayer($promotions)->accesses($promotions, $actions, $contracts, $events),
            Ruleset::forLayer($actions)->accesses($catalog, $orders, $promotions, $actions, $contracts, $events),
            Ruleset::forLayer($contracts)->accesses($catalog, $orders, $promotions, $actions, $contracts, $events),
            Ruleset::forLayer($events)->accesses($catalog, $orders, $promotions, $actions, $contracts, $events),
        );
};
