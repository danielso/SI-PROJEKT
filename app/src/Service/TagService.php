<?php
/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service layer for Tag domain operations (listing with counters, CRUD, simple lookups).
 */
final class TagService implements TagServiceInterface
{
    /**
     * TagService constructor.
     *
     * @param EntityManagerInterface $em   Entity manager for persistence operations.
     * @param TagRepository          $tags Repository for Tag entities.
     */
    public function __construct(private readonly EntityManagerInterface $em, private readonly TagRepository $tags)
    {
    }

    /**
     * Returns all tags with usage counters for the given user.
     *
     * @param User $user The user for whom counters are calculated.
     *
     * @return array<int, array{0: Tag, todoCount: int|string, noteCount: int|string}>
     */
    public function getAllWithCountsForUser(User $user): array
    {
        return $this->tags->findAllWithCountsForUser($user);
    }

    /**
     * Creates a new tag after validating the name and uniqueness.
     *
     * @param Tag $tag The tag to create (name is taken from the entity).
     *
     * @return Tag The persisted tag.
     *
     * @throws \InvalidArgumentException When the name is empty or already exists.
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
        $this->em->persist($tag);
        $this->em->flush();

        return $tag;
    }


    /**
     * Updates an existing tag's name with validation and uniqueness check.
     *
     * @param Tag $tag The tag to update.
     *
     * @return Tag The updated tag.
     *
     * @throws \InvalidArgumentException When the name is empty or collides with another tag.
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
        $this->em->flush();

        return $tag;
    }

    /**
     * Deletes the given tag.
     *
     * @param Tag $tag The tag to delete.
     *
     * @return void
     */
    public function delete(Tag $tag): void
    {
        $this->em->remove($tag);
        $this->em->flush();
    }

    /**
     * Finds a tag by its exact name.
     *
     * @param string $name The tag name to search for.
     *
     * @return Tag|null The found tag or null if none.
     */
    public function findOneByName(string $name): ?Tag
    {
        return $this->tags->findOneBy(['name' => $name]);
    }
}
