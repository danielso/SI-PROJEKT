<?php
/**
 * @license MIT
 */

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class representing a category that can contain notes and to-dos.
 */
#[UniqueEntity(fields: ['name', 'user'])]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(
    name: 'category',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_category_user_name', columns: ['user_id', 'name']),
    ],
)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Note>
     */

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Gets the ID of the category.
     *
     * @return int|null The category ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets the name of the category.
     *
     * @return string|null The category name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name of the category.
     *
     * @param string $name The name of the category.
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the user associated with the category.
     *
     * @return User|null The user who owns the category.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Sets the user for the category.
     *
     * @param User $user The user to associate with the category.
     *
     * @return $this
     */
    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
