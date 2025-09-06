<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;

/**
 * Service layer for Tag domain operations (listing with counters, CRUD, simple lookups)
 */
final class TagService implements TagServiceInterface
{
    /**
     * TagService constructor
     *
     * @param TagRepository $tags Repository for Tag entities
     */
    public function __construct(private readonly TagRepository $tags)
    {
    }

    /**
     * Returns all tags with usage counters (todoCount, noteCount) for a given user
     *
     * @param User $user User for whom counters are calculated
     *
     * @return array<int, array{0: Tag, todoCount: int|string, noteCount: int|string}>
     */
    public function getAllWithCountsForUser(User $user): array
    {
        return $this->tags->findAllWithCountsForUser($user);
    }

    /**
     * Creates a new tag (validates name and uniqueness)
     *
     * @param Tag $tag Tag to create
     *
     * @return Tag
     *
     * @throws \InvalidArgumentException when name is empty or tag already exists
     */
    public function create(Tag $tag): Tag
    {
        $name = trim((string) $tag->getName());
        if ('' === $name) {
            throw new \InvalidArgumentException('Nazwa tagu nie może być pusta.');
        }

        if ($this->findOneByName($name)) {
            throw new \InvalidArgumentException(sprintf('Tag „%s” już istnieje.', $name));
        }

        $tag->setName($name);
        $this->tags->save($tag, true);

        return $tag;
    }

    /**
     * Updates an existing tag (validates name and uniqueness across other tags)
     *
     * @param Tag $tag Tag to update
     *
     * @return Tag
     *
     * @throws \InvalidArgumentException when name is empty or would collide with another tag
     */
    public function update(Tag $tag): Tag
    {
        $name = trim((string) $tag->getName());
        if ('' === $name) {
            throw new \InvalidArgumentException('Nazwa tagu nie może być pusta.');
        }

        $existing = $this->findOneByName($name);
        if (null !== $existing && $existing->getId() !== $tag->getId()) {
            throw new \InvalidArgumentException(sprintf('Tag „%s” już istnieje.', $name));
        }

        $tag->setName($name);
        $this->tags->save($tag, true);

        return $tag;
    }

    /**
     * Deletes a tag
     *
     * @param Tag $tag Tag to delete
     */
    public function delete(Tag $tag): void
    {
        $this->tags->remove($tag, true);
    }

    /**
     * Finds a tag by its name
     *
     * @param string $name Tag name to search for
     *
     * @return Tag|null
     */
    public function findOneByName(string $name): ?Tag
    {
        return $this->tags->findOneBy(['name' => $name]);
    }
}
