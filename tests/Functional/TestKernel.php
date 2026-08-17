<?php

declare(strict_types=1);

namespace FrolovGuru\ScalarSymfony\Tests\Functional;

use FrolovGuru\ScalarSymfony\ScalarSymfonyBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class TestKernel extends Kernel
{
    private readonly string $cacheId;

    /**
     * @param array<string, mixed> $scalarConfig
     */
    public function __construct(
        private readonly array $scalarConfig = ['url' => '/openapi.yaml'],
        private readonly ?bool $authorizationCheckerAllows = null,
        private readonly bool $withRealSecurity = false,
    ) {
        parent::__construct('test', false);

        // The compiled container is cached per kernel class + environment. Tests
        // use different bundle configurations, so give each configuration its own
        // cache key to avoid stale containers.
        $this->cacheId = hash('sha1', serialize([$this->scalarConfig, $this->authorizationCheckerAllows, $this->withRealSecurity]));
    }

    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            new TwigBundle(),
            new ScalarSymfonyBundle(),
        ];

        if ($this->withRealSecurity) {
            $bundles[] = new SecurityBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $scalarConfig = $this->scalarConfig;
        $authorizationCheckerAllows = $this->authorizationCheckerAllows;
        $withRealSecurity = $this->withRealSecurity;

        $loader->load(static function (ContainerBuilder $container) use ($scalarConfig, $authorizationCheckerAllows, $withRealSecurity): void {
            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'test-secret',
                'router' => [
                    'resource' => __DIR__.'/../../config/routes.php',
                ],
                // Functional tests need a real session for the security firewall
                // (loginUser stores the token in the session) and the mock_file
                // storage so multiple kernel boots share one PHP process. The
                // explicit cookie/handler options avoid framework-bundle 6.4
                // deprecations about unset session options.
                'session' => [
                    'enabled' => true,
                    'storage_factory_id' => 'session.storage.factory.mock_file',
                    'cookie_secure' => 'auto',
                    'cookie_samesite' => 'lax',
                    'handler_id' => null,
                ],
                'php_errors' => [
                    'log' => true,
                ],
            ]);
            $container->loadFromExtension('twig', []);
            $container->loadFromExtension('scalar_symfony', $scalarConfig);

            if ($withRealSecurity) {
                self::loadRealSecurity($container);
            }

            if (null !== $authorizationCheckerAllows) {
                $container->register('security.authorization_checker', FakeAuthorizationChecker::class)
                    ->setPublic(true)
                    ->setArguments([$authorizationCheckerAllows]);
            }
        });
    }

    private static function loadRealSecurity(ContainerBuilder $container): void
    {
        $container->loadFromExtension('security', [
            'password_hashers' => [
                InMemoryUser::class => 'plaintext',
            ],
            'providers' => [
                'test_users' => [
                    'memory' => [
                        'users' => [
                            'admin' => ['password' => 'test', 'roles' => ['ROLE_ADMIN']],
                            'api-docs' => ['password' => 'test', 'roles' => ['ROLE_API_DOCS']],
                        ],
                    ],
                ],
            ],
            'firewalls' => [
                'main' => [
                    'pattern' => '^/',
                    'lazy' => true,
                    'provider' => 'test_users',
                ],
            ],
        ]);
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
