<?php

declare(strict_types=1);

use FrolovGuru\ScalarSymfony\Controller\ScalarController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private();

    $services->set(ScalarController::class)
        ->public()
        ->arg('$config', param('scalar_symfony.config'))
        ->arg('$authorizationChecker', service('security.authorization_checker')->ignoreOnInvalid());
};