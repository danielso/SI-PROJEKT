<?php

/**
 * @license MIT
 */

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity representing a tag that can be attached to notes or to-dos.
 */
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(
    name: 'tag',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_tag_name', columns: ['name']),
    ]
)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /**
     * Returns the owner user of this tag (if any).
     *
     * @return User|null Owner user or null when not assigned
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Sets (or unsets) the owner user of this tag.
     *
     * @param User|null $user Owner user or null to unassign
     *
     * @return self Fluent interface
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Gets the ID of the tag.
     *
     * @return int|null The tag ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets the name of the tag.
     *
     * @return string|null The tag name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name of the tag.
     *
     * @param string $name The tag name
     *
     * @return self Fluent interface
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
