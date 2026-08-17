<?php

declare(strict_types=1);

namespace FrolovGuru\ScalarSymfony\Tests\Functional;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class FakeAuthorizationChecker implements AuthorizationCheckerInterface
{
    public function __construct(private readonly bool $allowed = true)
    {
    }

    public function isGranted(mixed $attribute, mixed $subject = null, mixed $accessDecision = null): bool
    {
        return $this->allowed;
    }
}
