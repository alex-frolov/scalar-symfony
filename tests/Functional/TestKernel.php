<?php

declare(strict_types=1);

namespace FrolovGuru\ScalarSymfony\Tests\Functional;

use FrolovGuru\ScalarSymfony\ScalarSymfonyBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    private readonly string $cacheId;

    /**
     * @param array<string, mixed> $scalarConfig
     */
    public function __construct(
        private readonly array $scalarConfig = ['url' => '/openapi.yaml'],
        private readonly ?bool $authorizationCheckerAllows = null,
    ) {
        parent::__construct('test', false);

        // The compiled container is cached per kernel class + environment. Tests
        // use different bundle configurations, so give each configuration its own
        // cache key to avoid stale containers.
        $this->cacheId = hash('sha1', serialize([$this->scalarConfig, $this->authorizationCheckerAllows]));
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new ScalarSymfonyBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $scalarConfig = $this->scalarConfig;
        $authorizationCheckerAllows = $this->authorizationCheckerAllows;

        $loader->load(static function (ContainerBuilder $container) use ($scalarConfig, $authorizationCheckerAllows): void {
            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'test-secret',
                'router' => [
                    'resource' => __DIR__.'/../../config/routes.php',
                ],
            ]);
            $container->loadFromExtension('twig', []);
            $container->loadFromExtension('scalar_symfony', $scalarConfig);

            if (null !== $authorizationCheckerAllows) {
                $container->register('security.authorization_checker', FakeAuthorizationChecker::class)
                    ->setPublic(true)
                    ->setArguments([$authorizationCheckerAllows]);
            }
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/scalar-symfony-test-'.$this->cacheId;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/scalar-symfony-test-'.$this->cacheId.'/log';
    }
}
