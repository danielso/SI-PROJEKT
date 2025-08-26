<?php
/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Service layer for Category domain operations (CRUD, lists with counters).
 */
final class CategoryService implements CategoryServiceInterface
{
    /**
     * CategoryService constructor.
     *
     * @param CategoryRepository     $categoryRepository Repository for Category entities.
     * @param EntityManagerInterface $em                 Entity manager for persistence operations.
     */
    public function __construct(private readonly CategoryRepository $categoryRepository, private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Returns the list of categories for the given user along with related counters.
     *
     * @param User $user The owner of the categories.
     *
     * @return array<int, array{0: Category, todoCount: int|string, noteCount: int|string}>
     */
    public function getListForUserWithCounts(User $user): array
    {
        return $this->categoryRepository->findAllForUserWithCounts($user);
    }

    /**
     * Creates a new category for the given user.
     *
     * @param Category $category The category to create.
     * @param User     $user     The owner of the category.
     *
     * @return Category The persisted category.
     */
    public function create(Category $category, User $user): Category
    {
        $category->setUser($user);

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    /**
     * Updates an existing category. Owner check is enforced.
     *
     * @param Category $category The category to update.
     * @param User     $user     The acting user (must be the owner).
     *
     * @return Category The updated category.
     */
    public function update(Category $category, User $user): Category
    {
        $this->assertOwner($category, $user);
        $this->em->flush();

        return $category;
    }

    /**
     * Deletes a category. Owner check is enforced.
     *
     * @param Category $category The category to delete.
     * @param User     $user     The acting user (must be the owner).
     *
     * @return void
     */
    public function delete(Category $category, User $user): void
    {
        $this->assertOwner($category, $user);

        $this->em->remove($category);
        $this->em->flush();
    }

    /**
     * Ensures the given user is the owner of the category.
     *
     * @param Category $category The category to check.
     * @param User     $user     The user to verify.
     *
     * @throws AccessDeniedException When the user is not the owner.
     *
     * @return void
     */
    private function assertOwner(Category $category, User $user): void
    {
        if ($category->getUser() !== $user) {
            throw new AccessDeniedException();
        }
    }
}
