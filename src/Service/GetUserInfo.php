<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class GetUserInfo
{
    /** @var  TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param TokenStorageInterface  $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getUserLoginInfo(): ?User
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof TokenInterface) {
            /** @var User $user */
            return $token->getUser();
        }

        return null;
    }
}
