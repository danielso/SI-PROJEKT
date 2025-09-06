<?php

/**
 * @license MIT
 */

namespace App\Security\Voter;

use App\Entity\Tag;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter deciding access rights for {@see Tag} entities.
 *
 * Supported attributes:
 *  - self::VIEW
 *  - self::EDIT
 *  - self::DELETE
 */
class TagVoter extends Voter
{
    public const VIEW   = 'TAG_VIEW';
    public const EDIT   = 'TAG_EDIT';
    public const DELETE = 'TAG_DELETE';

    /**
     * Checks whether this voter supports the given attribute and subject.
     *
     * @param string $attribute One of the TAG_* attributes
     * @param mixed  $subject   The subject to secure (expected: Tag)
     *
     * @return bool True if supported, false otherwise
     */
    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Tag;
    }

    /**
     * Performs the access decision for a supported attribute/subject pair.
     *
     * @param string         $attribute The attribute being checked
     * @param mixed          $subject   The subject (Tag)
     * @param TokenInterface $token     The security token holding the user
     *
     * @return bool True when access is granted, false otherwise
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $isAdmin = \in_array('ROLE_ADMIN', $user->getRoles(), true);

        /** @var Tag $tag */
        $tag = $subject;

        if (null === $tag->getUser()) {
            return $isAdmin && self::VIEW !== $attribute ? true : true;
        }

        $isOwner = $tag->getUser()->getId() === $user->getId();

        return match ($attribute) {
            self::VIEW   => $isOwner || $isAdmin,
            self::EDIT   => $isOwner || $isAdmin,
            self::DELETE => $isOwner || $isAdmin,
            default      => false,
        };
    }
}
