<?php

/**
 * @license MIT
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ToDoRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ToDoRepository::class)]
#[ORM\Table(
    name: 'to_do',
    indexes: [
        new ORM\Index(name: 'idx_todo_user_created', columns: ['user_id', 'created_at']),
        new ORM\Index(name: 'idx_todo_category', columns: ['category_id']),
        new ORM\Index(name: 'idx_todo_updated', columns: ['updated_at']),
    ]
)]
/**
 * Class ToDo.
 *
 * Represents a to-do task, which can be categorized and tagged, and associated with a user.
 */
class ToDo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?bool $isDone = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Category::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true, name: 'share_token')]
    #[Assert\Length(max: 255)]
    private ?string $shareToken = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinTable(
        name: 'todo_tags',
        joinColumns: [
            new ORM\JoinColumn(name: 'to_do_id', referencedColumnName: 'id', onDelete: 'CASCADE'),
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id', onDelete: 'CASCADE'),
        ]
    )]
    private Collection $tags;

    #[ORM\ManyToMany(targetEntity: User::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinTable(
        name: 'todo_collab',
        joinColumns: [
            new ORM\JoinColumn(name: 'to_do_id', referencedColumnName: 'id', onDelete: 'CASCADE'),
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE'),
        ]
    )]
    private Collection $collaborators;

    /**
     * ToDo constructor.
     *
     * Initializes collection properties for tags and collaborators.
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->collaborators = new ArrayCollection();
    }

    /**
     * Gets the share token of the to-do task.
     *
     * @return string|null the share token
     */
    public function getShareToken(): ?string
    {
        return $this->shareToken;
    }

    /**
     * Sets the share token of the to-do task.
     *
     * @param string|null $shareToken the share token
     *
     * @return self
     */
    public function setShareToken(?string $shareToken): self
    {
        $this->shareToken = $shareToken;

        return $this;
    }

    /**
     * Gets the user associated with the to-do task.
     *
     * @return User|null the user
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Sets the user for the to-do task.
     *
     * @param User $user the user
     *
     * @return self
     */
    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Gets the ID of the to-do task.
     *
     * @return int|null the ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets the content of the to-do task.
     *
     * @return string|null the content
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the content of the to-do task.
     *
     * @param string $content the content
     *
     * @return self
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Checks if the to-do task is done.
     *
     * @return bool|null the status of the task
     */
    public function isDone(): ?bool
    {
        return $this->isDone;
    }

    /**
     * Sets the status of the to-do task.
     *
     * @param bool $isDone the status
     *
     * @return self
     */
    public function setIsDone(bool $isDone): static
    {
        $this->isDone = $isDone;

        return $this;
    }

    /**
     * Gets the title of the to-do task.
     *
     * @return string|null the title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Sets the title of the to-do task.
     *
     * @param string $title the title
     *
     * @return self
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the creation date of the to-do task.
     *
     * @return \DateTimeImmutable|null the creation date
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Sets the creation date of the to-do task.
     *
     * @param \DateTimeImmutable $createdAt the creation date
     *
     * @return self
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Gets the last updated date of the to-do task.
     *
     * @return \DateTimeImmutable|null the updated date
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Sets the last updated date of the to-do task.
     *
     * @param \DateTimeImmutable|null $updatedAt the updated date
     *
     * @return self
     */
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Gets the category of the to-do task.
     *
     * @return Category|null the category
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Sets the category of the to-do task.
     *
     * @param Category|null $category the category
     *
     * @return self
     */
    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Gets the tags associated with the to-do task.
     *
     * @return Collection the collection of tags
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * Sets the tags associated with the to-do task.
     *
     * @param Collection $tags the collection of tags
     *
     * @return self
     */
    public function setTags(Collection $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Adds a tag to the to-do task.
     *
     * @param Tag $tag the tag to add
     *
     * @return self
     */
    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * Removes a tag from the to-do task.
     *
     * @param Tag $tag the tag to remove
     *
     * @return self
     */
    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * Returns the collection of collaborators (users) assigned to this task.
     *
     * @return Collection<int, User>
     */
    public function getCollaborators(): Collection
    {
        return $this->collaborators;
    }

    /**
     * Adds a collaborator to this to-do.
     *
     * @param User $user the user to add as a collaborator
     *
     * @return self
     */
    public function addCollaborator(User $user): self
    {
        if (!$this->collaborators->contains($user)) {
            $this->collaborators->add($user);
        }

        return $this;
    }

    /**
     * Removes a collaborator from this to-do.
     *
     * @param User $user the user to remove from collaborators
     *
     * @return self
     */
    public function removeCollaborator(User $user): self
    {
        $this->collaborators->removeElement($user);

        return $this;
    }

    /**
     * Checks whether the given user is a collaborator of this to-do.
     *
     * @param User $user the user to check
     *
     * @return bool true if the user is a collaborator, false otherwise
     */
    public function isCollaborator(User $user): bool
    {
        return $this->collaborators->contains($user);
    }
}
