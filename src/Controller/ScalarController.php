<?php

declare(strict_types=1);

namespace FrolovGuru\ScalarSymfony\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

final class ScalarController
{
    private const TEMPLATE = '@ScalarSymfony/reference.html.twig';

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
    public function __construct(
        private readonly array $config,
        private readonly Environment $twig,
        private readonly ?AuthorizationCheckerInterface $authorizationChecker = null,
    ) {
    }

    public function __invoke(): Response
    {
        $this->authorize();

        return new Response($this->twig->render(self::TEMPLATE, [
            'cdn' => $this->config['cdn'],
            'configuration' => $this->buildConfiguration(),
            'configurationJson' => $this->buildConfigurationJson(),
        ]));
    }

    private function authorize(): void
    {
        $accessControl = $this->config['access_control'];

        if ('public' === $accessControl['mode']) {
            return;
        }

        if (null === $this->authorizationChecker) {
            throw new \LogicException('The "attribute" access control mode requires symfony/security-core and a configured security.authorization_checker service (enable Symfony Security).');
        }

        if (false === $this->authorizationChecker->isGranted($accessControl['attribute'])) {
            throw new AccessDeniedHttpException('Access to the API reference is denied.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildConfiguration(): array
    {
        /** @var array<string, mixed> $configuration */
        $configuration = array_replace_recursive(
            $this->config['configuration'],
            $this->config['scalar_options'],
        );
        $configuration['url'] = $this->config['url'];

        return $configuration;
    }

    private function buildConfigurationJson(): string
    {
        return json_encode(
            $this->buildConfiguration(),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }
}
