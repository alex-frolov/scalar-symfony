<?php

declare(strict_types=1);

namespace FrolovGuru\ScalarSymfony\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class ScalarControllerTest extends TestCase
{
    private ?KernelBrowser $client = null;

    protected function tearDown(): void
    {
        $this->client?->getKernel()?->shutdown();
        $this->client = null;

        // FrameworkBundle::boot() registers Symfony's ErrorHandler (one exception
        // handler per boot, error handlers are balanced internally). Remove the
        // exception handler added by this test's kernel boot so PHPUnit's
        // exception-handler check stays clean.
        restore_exception_handler();
    }

    /**
     * @param array<string, mixed> $scalarConfig
     */
    private function createClient(array $scalarConfig = [], ?bool $authorizationCheckerAllows = null): KernelBrowser
    {
        $kernel = new TestKernel(
            array_merge(['url' => '/openapi.yaml'], $scalarConfig),
            $authorizationCheckerAllows,
        );

        return $this->client = new KernelBrowser($kernel);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractConfiguration(string $html): array
    {
        self::assertSame(1, preg_match(
            "/Scalar\.createApiReference\('#scalar-api-reference', (\{.*\})\)/s",
            $html,
            $matches,
        ));

        $configuration = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($configuration);

        return $configuration;
    }

    public function testPublicAccessRendersReferencePage(): void
    {
        $client = $this->createClient();
        $client->request('GET', '/scalar');

        $response = $client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/html; charset=UTF-8', $response->headers->get('content-type'));

        $html = $response->getContent();
        self::assertStringContainsString('<div id="scalar-api-reference"></div>', $html);
        self::assertStringContainsString('https://cdn.jsdelivr.net/npm/@scalar/api-reference@1.65.1', $html);
        self::assertStringContainsString('Scalar.createApiReference(\'#scalar-api-reference\'', $html);

        $configuration = $this->extractConfiguration($html);
        self::assertSame('/openapi.yaml', $configuration['url']);
        self::assertSame('symfony', $configuration['_integration']);
        self::assertSame('default', $configuration['theme']);
        self::assertSame(['title' => 'API Reference'], $configuration['metaData']);
    }

    public function testCustomPathAndConfiguration(): void
    {
        $client = $this->createClient([
            'url' => 'https://api.example.com/openapi.json',
            'path' => '/api-docs',
            'configuration' => [
                'theme' => 'alternate',
                'metaData' => ['title' => 'My API Reference'],
            ],
            'scalar_options' => [
                'darkMode' => true,
                'layout' => 'modern',
            ],
        ]);

        $client->request('GET', '/api-docs');

        $response = $client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<title>My API Reference</title>', $response->getContent());

        $configuration = $this->extractConfiguration($response->getContent());
        self::assertSame('https://api.example.com/openapi.json', $configuration['url']);
        self::assertSame('alternate', $configuration['theme']);
        self::assertSame('modern', $configuration['layout']);
        self::assertTrue($configuration['darkMode']);
        self::assertSame(['title' => 'My API Reference'], $configuration['metaData']);
    }

    public function testNestedConfigurationIsMerged(): void
    {
        $client = $this->createClient([
            'configuration' => [
                'metaData' => ['title' => 'My API Reference'],
            ],
            'scalar_options' => [
                'metaData' => ['description' => 'Public API'],
            ],
        ]);

        $client->request('GET', '/scalar');

        $configuration = $this->extractConfiguration($client->getResponse()->getContent());
        self::assertSame([
            'title' => 'My API Reference',
            'description' => 'Public API',
        ], $configuration['metaData']);
    }

    public function testHtmlInConfigurationIsEscapedForScriptContext(): void
    {
        $client = $this->createClient([
            'configuration' => [
                'metaData' => [
                    'title' => '</script><script>alert(1)</script>',
                ],
            ],
        ]);

        $client->request('GET', '/scalar');

        $html = $client->getResponse()->getContent();
        // The JSON blob inside <script> must not contain a raw closing script tag.
        self::assertStringNotContainsString('</script><script>alert(1)</script>', $html);
        self::assertStringContainsString('\u003C/script\u003E', $html);

        $configuration = $this->extractConfiguration($html);
        self::assertSame('</script><script>alert(1)</script>', $configuration['metaData']['title']);
    }

    public function testAttributeModeWithoutAttributeFailsConfiguration(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('An attribute is required when access_control.mode is "attribute"');

        $client = $this->createClient([
            'access_control' => ['mode' => 'attribute'],
        ], true);

        $client->request('GET', '/scalar');
    }

    public function testAttributeModeWithoutSecurityReturns500(): void
    {
        $client = $this->createClient([
            'access_control' => [
                'mode' => 'attribute',
                'attribute' => 'ROLE_API_DOCS',
            ],
        ]);

        $client->request('GET', '/scalar');

        self::assertSame(500, $client->getResponse()->getStatusCode());
    }

    public function testUnknownPathReturns404(): void
    {
        $client = $this->createClient();
        $client->request('GET', '/scalar-unknown');

        self::assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testAttributeModeDeniedReturns403(): void
    {
        $client = $this->createClient([
            'access_control' => [
                'mode' => 'attribute',
                'attribute' => 'ROLE_API_DOCS',
            ],
        ], false);

        $client->request('GET', '/scalar');

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAttributeModeAllowedReturns200(): void
    {
        $client = $this->createClient([
            'access_control' => [
                'mode' => 'attribute',
                'attribute' => 'ROLE_API_DOCS',
            ],
        ], true);

        $client->request('GET', '/scalar');

        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
