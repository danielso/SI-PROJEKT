<?php

// src/Security/BlockedUserChecker.php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

/**
 * Checks if the user is blocked during the authentication process.
 * Throws an exception if the user is blocked.
 */
class BlockedUserChecker implements UserCheckerInterface
{
    /**
     * {@inheritdoc}
     *
     * Checks if the user is blocked before authentication.
     * If the user is blocked, an exception is thrown.
     *
     * @param UserInterface $user The user to check
     *
     * @throws CustomUserMessageAuthenticationException if the user is blocked
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof User) {
            if ($user->getIsBlocked()) {
                throw new CustomUserMessageAuthenticationException('Twoje konto zostało zablokowane.');
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * This method can be used to add post-authentication checks, if necessary.
     * For now, it does not perform any actions.
     *
     * @param UserInterface $user The user to check
     */
    public function checkPostAuth(UserInterface $user): void
    {
        // Możesz dodać logikę, która wykona się po autentykacji, jeśli chcesz
    }
}
