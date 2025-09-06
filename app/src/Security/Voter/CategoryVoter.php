<?php

/**
 * @license MIT
 */

namespace App\Security\Voter;

use App\Entity\Category;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter deciding access rights for {@see Category} entities.
 *
 * Supported attributes:
 *  - self::VIEW
 *  - self::EDIT
 *  - self::DELETE
 */
class CategoryVoter extends Voter
{
    public const VIEW   = 'CATEGORY_VIEW';
    public const EDIT   = 'CATEGORY_EDIT';
    public const DELETE = 'CATEGORY_DELETE';

    /**
     * Checks whether this voter supports the given attribute and subject.
     *
     * @param string $attribute One of the CATEGORY_* attributes
     * @param mixed  $subject   The subject to secure (expected: Category)
     *
     * @return bool True if supported, false otherwise
     */
    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Category;
    }

    /**
     * Performs the access decision for a supported attribute/subject pair.
     *
     * @param string         $attribute The attribute being checked
     * @param mixed          $subject   The subject (Category)
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

        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /** @var Category $category */
        $category = $subject;

        $owner = method_exists($category, 'getUser')
            ? $category->getUser()
            : (method_exists($category, 'getOwner') ? $category->getOwner() : null);

        if (null === $owner) {
            return false;
        }

        return match ($attribute) {
            self::VIEW, self::EDIT, self::DELETE => $owner === $user,
            default => false,
        };
    }
}
