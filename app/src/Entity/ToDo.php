<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ToDoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ToDo
 *
 * Represents a to-do task, which can be categorized and tagged, and associated with a user.
 */
#[ORM\Entity(repositoryClass: ToDoRepository::class)]
class ToDo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\Column]
    private ?bool $isDone = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'toDos')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(nullable: false)] // Ustawienie, żeby użytkownik był wymagany
    private ?User $user = null;

    #[ORM\Column(type: "string", length: 255, unique: true, nullable: true, name: "share_token")]
    #[Assert\Length(max: 255)]
    private ?string $shareToken = null;

    /**
     * Gets the share token of the to-do task.
     *
     * @return string|null The share token.
     */
    public function getShareToken(): ?string
    {
        return $this->shareToken;
    }

    /**
     * Sets the share token of the to-do task.
     *
     * @param string|null $shareToken The share token.
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
     * @return User|null The user.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Sets the user for the to-do task.
     *
     * @param User $user The user.
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
     * @return int|null The ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets the content of the to-do task.
     *
     * @return string|null The content.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the content of the to-do task.
     *
     * @param string $content The content.
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
     * @return bool|null The status of the task.
     */
    public function isDone(): ?bool
    {
        return $this->isDone;
    }

    /**
     * Sets the status of the to-do task.
     *
     * @param bool $isDone The status.
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
     * @return string|null The title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Sets the title of the to-do task.
     *
     * @param string $title The title.
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
     * @return \DateTimeImmutable|null The creation date.
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Sets the creation date of the to-do task.
     *
     * @param \DateTimeImmutable $createdAt The creation date.
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
     * @return \DateTimeImmutable|null The updated date.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Sets the last updated date of the to-do task.
     *
     * @param \DateTimeImmutable|null $updatedAt The updated date.
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
     * @return Category|null The category.
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Sets the category of the to-do task.
     *
     * @param Category|null $category The category.
     *
     * @return self
     */
    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'toDos')]
    #[ORM\JoinTable(name: 'todo_tags')] // Opcjonalnie, nazwa tabeli łączącej
    private Collection $tags;

    /**
     * ToDo constructor.
     *
     * Initializes the collection for tags.
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * Gets the tags associated with the to-do task.
     *
     * @return Collection The collection of tags.
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * Sets the tags associated with the to-do task.
     *
     * @param Collection $tags The collection of tags.
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
     * @param Tag $tag The tag to add.
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
     * @param Tag $tag The tag to remove.
     *
     * @return self
     */
    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
