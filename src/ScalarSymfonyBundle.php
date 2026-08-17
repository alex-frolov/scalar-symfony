<?php

declare(strict_types=1);

namespace FrolovGuru\ScalarSymfony;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class ScalarSymfonyBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    /**
     * @param array{
     *     url: string,
     *     cdn: string,
     *     path: string,
     *     configuration: array<string, mixed>,
     *     scalar_options: array<string, mixed>,
     *     access_control: array{mode: 'public'|'attribute', attribute: string|null}
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $container->parameters()
            ->set('scalar_symfony.config', $config)
            ->set('scalar_symfony.path', $config['path']);
    }
}
