<?php

/**
 * @license MIT
 */

namespace App\Security\Voter;

use App\Entity\ToDo;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter deciding access rights for {@see ToDo} entities.
 *
 * Supported attributes:
 *  - self::VIEW
 *  - self::EDIT
 *  - self::DELETE
 *  - self::COLLAB_MANAGE
 *  - self::LEAVE
 */
class ToDoVoter extends Voter
{
    public const VIEW = 'TODO_VIEW';
    public const EDIT = 'TODO_EDIT';
    public const DELETE = 'TODO_DELETE';
    public const COLLAB_MANAGE = 'TODO_COLLAB_MANAGE';
    public const LEAVE = 'TODO_LEAVE';

    /**
     * Checks whether this voter supports the given attribute and subject.
     *
     * @param string $attribute One of the TODO_* attributes
     * @param mixed  $subject   The subject to secure (expected: ToDo)
     *
     * @return bool True if supported, false otherwise
     */
    protected function supports(string $attribute, $subject): bool
    {
        return \in_array(
            $attribute,
            [
                self::VIEW,
                self::EDIT,
                self::DELETE,
                self::COLLAB_MANAGE,
                self::LEAVE,
            ],
            true
        ) && $subject instanceof ToDo;
    }

    /**
     * Performs the access decision for a supported attribute/subject pair.
     *
     * @param string         $attribute The attribute being checked
     * @param mixed          $subject   The subject (ToDo)
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

        /** @var ToDo $toDo */
        $toDo = $subject;

        $isOwner = $toDo->getUser()?->getId() === $user->getId();
        $isCollaborator = $toDo->getCollaborators()->exists(fn ($i, User $u) => $u->getId() === $user->getId());

        return match ($attribute) {
            self::VIEW, self::EDIT => $isOwner || $isCollaborator,
            self::DELETE, self::COLLAB_MANAGE => $isOwner,
            self::LEAVE => $isCollaborator && !$isOwner,
            default => false,
        };
    }
}
