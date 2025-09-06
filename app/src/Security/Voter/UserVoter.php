<?php

/**
 * @license MIT
 */

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter deciding access rights for {@see User} entities.
 *
 * Supported attributes:
 *  - self::VIEW
 *  - self::EDIT
 *  - self::DELETE
 */
class UserVoter extends Voter
{
    public const VIEW   = 'USER_VIEW';
    public const EDIT   = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';

    /**
     * Checks whether this voter supports the given attribute and subject.
     *
     * @param string $attribute One of the USER_* attributes
     * @param mixed  $subject   The subject to secure (expected: User)
     *
     * @return bool True if supported, false otherwise
     */
    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof User;
    }

    /**
     * Performs the access decision for a supported attribute/subject pair.
     *
     * Only administrators are allowed to operate on users.
     *
     * @param string         $attribute The attribute being checked
     * @param mixed          $subject   The subject (User)
     * @param TokenInterface $token     The security token holding the acting user
     *
     * @return bool True when access is granted, false otherwise
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $actor = $token->getUser();
        if (!$actor instanceof User) {
            return false;
        }

        $isAdmin = \in_array('ROLE_ADMIN', $actor->getRoles(), true);
        if (!$isAdmin) {
            return false;
        }

        return true;
    }
}
