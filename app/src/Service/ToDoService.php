<?php
/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\ToDo;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use App\Repository\ToDoRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Service layer for ToDo domain operations (listing, CRUD, permissions, helpers).
 */
final class ToDoService implements ToDoServiceInterface
{
    /**
     * ToDoService constructor.
     *
     * @param ToDoRepository         $toDoRepo     Repository for ToDo entities.
     * @param CategoryRepository     $categoryRepo Repository for Category entities.
     * @param TagRepository          $tagRepo      Repository for Tag entities.
     * @param UserRepository         $userRepo     Repository for User entities.
     * @param EntityManagerInterface $em           Entity manager for persistence operations.
     */
    public function __construct(private readonly ToDoRepository $toDoRepo, private readonly CategoryRepository $categoryRepo, private readonly TagRepository $tagRepo, private readonly UserRepository $userRepo, private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Builds a query for listing ToDo items visible to the given user with optional filters.
     *
     * @param User  $user    The user for whom to list items.
     * @param array $filters Optional filters: category, tag, search, scope.
     *
     * @return QueryBuilder
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder
    {
        return $this->toDoRepo->queryListForUser($user, $filters);
    }

    /**
     * Finds a ToDo by its share token.
     *
     * @param string $token The share token.
     *
     * @return ToDo|null
     */
    public function findOneByShareToken(string $token): ?ToDo
    {
        return $this->toDoRepo->findOneBy(['shareToken' => $token]);
    }

    /**
     * Creates a new ToDo for the given owner and persists it.
     *
     * @param ToDo        $toDo            The ToDo entity to populate.
     * @param User        $owner           The owner of the ToDo.
     * @param int|null    $categoryId      Existing category ID to assign (optional).
     * @param string|null $newCategoryName Name of a new category to create and assign (optional).
     * @param string|null $tagsCsv         Comma-separated tag names (optional).
     *
     * @return ToDo The persisted ToDo.
     */
    public function create(ToDo $toDo, User $owner, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv): ToDo
    {
        $now = new \DateTimeImmutable();
        $toDo->setUser($owner);
        $toDo->setCreatedAt($now);
        $toDo->setUpdatedAt($now);
        if (null === $toDo->getShareToken()) {
            $toDo->setShareToken(bin2hex(random_bytes(16)));
        }

        $this->applyCategory($toDo, $owner, $categoryId, $newCategoryName);
        $this->applyTags($toDo, $tagsCsv);

        $this->em->persist($toDo);
        $this->em->flush();

        return $toDo;
    }

    /**
     * Updates an existing ToDo and persists changes (category/tags).
     *
     * @param ToDo        $toDo            The ToDo to update.
     * @param User        $actingUser      The user performing the update (must be allowed to edit).
     * @param int|null    $categoryId      Existing category ID to assign (optional).
     * @param string|null $newCategoryName Name of a new category to create and assign (optional).
     * @param string|null $tagsCsv         Comma-separated tag names (optional).
     *
     * @return ToDo The updated ToDo.
     */
    public function update(ToDo $toDo, User $actingUser, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv): ToDo
    {
        $this->assertCanEdit($toDo, $actingUser);

        $toDo->setUpdatedAt(new \DateTimeImmutable());
        $this->applyCategory($toDo, $toDo->getUser(), $categoryId, $newCategoryName);
        $this->applyTags($toDo, $tagsCsv);

        $this->em->flush();

        return $toDo;
    }

    /**
     * Deletes a ToDo (ownership required).
     *
     * @param ToDo $toDo       The ToDo to delete.
     * @param User $actingUser The acting user (must be the owner).
     *
     * @return void
     */
    public function delete(ToDo $toDo, User $actingUser): void
    {
        $this->assertOwner($toDo, $actingUser);
        $this->em->remove($toDo);
        $this->em->flush();
    }

    /**
     * Returns whether the user can view the ToDo (owner or collaborator).
     *
     * @param ToDo      $toDo The ToDo.
     * @param User|null $user The user (nullable).
     *
     * @return bool
     */
    public function canView(ToDo $toDo, ?User $user): bool
    {
        if (null === $user) {
            return false;
        }
        if ($toDo->getUser()?->getId() === $user->getId()) {
            return true;
        }

        return $toDo->getCollaborators()->exists(fn($i, User $u) => $u->getId() === $user->getId());
    }

    /**
     * Returns whether the user can edit the ToDo (same rule as view).
     *
     * @param ToDo      $toDo
     * @param User|null $user
     *
     * @return bool
     */
    public function canEdit(ToDo $toDo, ?User $user): bool
    {
        return $this->canView($toDo, $user);
    }

    /**
     * Returns whether the user can delete the ToDo (owner only).
     *
     * @param ToDo      $toDo
     * @param User|null $user
     *
     * @return bool
     */
    public function canDelete(ToDo $toDo, ?User $user): bool
    {
        return (null !== $user) && ($toDo->getUser()?->getId() === $user->getId());
    }

    /**
     * Returns categories for the given user ordered by name.
     *
     * @param User $user
     *
     * @return array<int, Category>
     */
    public function getCategoriesFor(User $user): array
    {
        return $this->categoryRepo->findBy(['user' => $user], ['name' => 'ASC']);
    }

    /**
     * Returns all tags ordered by name.
     *
     * @return array<int, Tag>
     */
    public function getAllTags(): array
    {
        return $this->tagRepo->findBy([], ['name' => 'ASC']);
    }

    /**
     * Adds a collaborator to the ToDo using their email (owner-only action).
     *
     * @param ToDo   $toDo       The target ToDo.
     * @param string $email      Email of the user to add.
     * @param User   $actingUser Must be the owner.
     *
     * @return void
     *
     * @throws \InvalidArgumentException For invalid input or conditions.
     * @throws \LogicException           For authorization errors (wrapped by assertOwner()).
     */
    public function addCollaboratorByEmail(ToDo $toDo, string $email, User $actingUser): void
    {
        $this->assertOwner($toDo, $actingUser);

        $email = trim($email);
        if ('' === $email) {
            throw new \InvalidArgumentException();
        }

        $target = $this->userRepo->findOneBy(['email' => $email]);
        if (!$target) {
            throw new \InvalidArgumentException();
        }
        if ($target->getId() === $toDo->getUser()?->getId()) {
            throw new \InvalidArgumentException();
        }
        if ($toDo->getCollaborators()->contains($target)) {
            return;
        }

        $toDo->addCollaborator($target);
        $this->em->flush();
    }

    /**
     * Removes a collaborator from the ToDo by their user ID (owner-only action).
     *
     * @param ToDo $toDo
     * @param int  $userId
     * @param User $actingUser
     *
     * @return void
     */
    public function removeCollaboratorById(ToDo $toDo, int $userId, User $actingUser): void
    {
        $this->assertOwner($toDo, $actingUser);

        $target = $this->userRepo->find($userId);
        if ($target && $toDo->getCollaborators()->contains($target)) {
            $toDo->removeCollaborator($target);
            $this->em->flush();
        }
    }

    /**
     * Toggles the "done" flag of a ToDo (edit permission required).
     *
     * @param ToDo $toDo
     * @param User $actingUser
     *
     * @return ToDo
     */
    public function toggleDone(ToDo $toDo, User $actingUser): ToDo
    {
        $this->assertCanEdit($toDo, $actingUser);
        $toDo->setIsDone(!$toDo->isDone());
        $toDo->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $toDo;
    }

    /**
     * Assigns a category to the ToDo: by existing ID or by creating a new one.
     *
     * @param ToDo        $toDo
     * @param User        $owner
     * @param int|null    $categoryId
     * @param string|null $newCategoryName
     *
     * @return void
     *
     * @throws \LogicException When an existing category belongs to another user.
     */
    private function applyCategory(ToDo $toDo, User $owner, ?int $categoryId, ?string $newCategoryName): void
    {
        $newCategoryName = $newCategoryName ? trim($newCategoryName) : null;

        if ($categoryId) {
            $category = $this->categoryRepo->find((int) $categoryId);
            if ($category && $category->getUser()?->getId() === $owner->getId()) {
                $toDo->setCategory($category);

                return;
            }
            if ($category) {
                throw new \LogicException();
            }
        }

        if ($newCategoryName) {
            $category = (new Category())
                ->setName($newCategoryName)
                ->setUser($owner);
            $this->em->persist($category);
            $this->em->flush();
            $toDo->setCategory($category);
        }
    }

    /**
     * Replaces ToDo tags with those provided in CSV (creating missing tags).
     *
     * @param ToDo        $toDo
     * @param string|null $tagsCsv Comma-separated tag names; when null, tags remain unchanged.
     *
     * @return void
     */
    private function applyTags(ToDo $toDo, ?string $tagsCsv): void
    {
        if (null === $tagsCsv) {
            return;
        }

        foreach ($toDo->getTags() as $existing) {
            $toDo->removeTag($existing);
        }

        $names = array_filter(array_map('trim', explode(',', (string) $tagsCsv)));
        if (!$names) {
            return;
        }

        foreach ($names as $name) {
            $tag = $this->tagRepo->findOneBy(['name' => $name]) ?? (new Tag())->setName($name);
            if (null === $tag->getId()) {
                $this->em->persist($tag);
            }
            $toDo->addTag($tag);
        }
    }

    /**
     * Ensures the given user is the owner of the ToDo.
     *
     * @param ToDo $toDo
     * @param User $user
     *
     * @return void
     *
     * @throws \LogicException When the user is not the owner.
     */
    private function assertOwner(ToDo $toDo, User $user): void
    {
        if ($toDo->getUser()?->getId() !== $user->getId()) {
            throw new \LogicException();
        }
    }

    /**
     * Ensures the given user can edit the ToDo.
     *
     * @param ToDo $toDo
     * @param User $user
     *
     * @return void
     *
     * @throws \LogicException When the user cannot edit.
     */
    private function assertCanEdit(ToDo $toDo, User $user): void
    {
        if (!$this->canEdit($toDo, $user)) {
            throw new \LogicException('Brak uprawnień do edycji.');
        }
    }
}
