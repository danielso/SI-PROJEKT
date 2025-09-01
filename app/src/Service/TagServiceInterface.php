<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Tag;
use App\Entity\User;

/**
 * Contract for Tag service operations.
 */
interface TagServiceInterface
{
    /**
     * Returns all tags with usage counters for the given user.
     *
     * @param User $user the user for whom counters are calculated
     *
     * @return array<int, array{0: Tag, todoCount: int|string, noteCount: int|string}>
     */
    public function getAllWithCountsForUser(User $user): array;

    /**
     * Creates a new tag.
     *
     * @param Tag $tag the tag to create (name taken from the entity)
     *
     * @return Tag the persisted tag
     */
    public function create(Tag $tag): Tag;

    /**
     * Updates an existing tag.
     *
     * @param Tag $tag the tag to update
     *
     * @return Tag the updated tag
     */
    public function update(Tag $tag): Tag;

    /**
     * Deletes the given tag.
     *
     * @param Tag $tag the tag to delete
     */
    public function delete(Tag $tag): void;

    /**
     * Finds a tag by its exact name.
     *
     * @param string $name the tag name to search for
     *
     * @return Tag|null the found tag or null if none
     */
    public function findOneByName(string $name): ?Tag;
}
