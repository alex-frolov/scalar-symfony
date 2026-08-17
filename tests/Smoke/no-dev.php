<?php

declare(strict_types=1);

use FrolovGuru\ScalarSymfony\ScalarSymfonyBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$kernel = new class extends Kernel {
    public function __construct()
    {
        parent::__construct('test', false);
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
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'no-dev-smoke-test',
                'router' => [
                    'resource' => __DIR__.'/../../config/routes.php',
                ],
            ]);
            $container->loadFromExtension('twig', []);
            $container->loadFromExtension('scalar_symfony', [
                'url' => '/openapi.yaml',
            ]);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/scalar-symfony-no-dev/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/scalar-symfony-no-dev/log';
    }
};

$response = $kernel->handle(Request::create('/scalar', 'GET'));

if (200 !== $response->getStatusCode()) {
    throw new RuntimeException(sprintf('Expected HTTP 200, got HTTP %d.', $response->getStatusCode()));
}

if (!str_contains((string) $response->getContent(), 'Scalar.createApiReference')) {
    throw new RuntimeException('Scalar API reference bootstrap was not rendered.');
}

$kernel->shutdown();

fwrite(STDOUT, "No-dev smoke test passed.\n");
