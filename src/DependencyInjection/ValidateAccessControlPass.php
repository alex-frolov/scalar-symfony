<?php

declare(strict_types=1);

namespace FrolovGuru\ScalarSymfony\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Fails fast at container compile time when access_control.mode is "attribute"
 * but no security.authorization_checker service is available (Symfony Security
 * not installed or not enabled), instead of surfacing an HTTP 500 at request time.
 */
final class ValidateAccessControlPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('scalar_symfony.config')) {
            return;
        }

        /** @var array{access_control: array{mode: 'public'|'attribute'}} $config */
        $config = $container->getParameter('scalar_symfony.config');

        if ('public' === $config['access_control']['mode']) {
            return;
        }

        if (!$container->hasDefinition('security.authorization_checker') && !$container->hasAlias('security.authorization_checker')) {
            throw new InvalidConfigurationException('The "attribute" access control mode requires an enabled "security.authorization_checker" service. Install and enable symfony/security-bundle, or set scalar_symfony.access_control.mode to "public".');
        }
    }
}
