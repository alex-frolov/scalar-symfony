<?php

declare(strict_types=1);

use FrolovGuru\ScalarSymfony\Controller\ScalarController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('scalar_symfony_reference', '%scalar_symfony.path%')
        ->controller(ScalarController::class)
        ->methods(['GET']);
};