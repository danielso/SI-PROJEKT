<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\NoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a note entity that can have content, title, tags, and an associated category.
 */
#[ORM\Entity(repositoryClass: NoteRepository::class)]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(onDelete: "SET NULL", nullable: true)]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(nullable: false)] // Ustawienie, żeby użytkownik był wymagany
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    /**
     * Get the image file name for the note.
     *
     * @return string|null The image file name.
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Set the image file name for the note.
     *
     * @param string|null $image The image file name to set.
     *
     * @return $this
     */
    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get the ID of the note.
     *
     * @return int|null The note ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the title of the note.
     *
     * @return string|null The title of the note.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set the title of the note.
     *
     * @param string $title The title of the note.
     *
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the content of the note.
     *
     * @return string|null The content of the note.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set the content of the note.
     *
     * @param string $content The content of the note.
     *
     * @return $this
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the creation date of the note.
     *
     * @return \DateTimeImmutable|null The creation date.
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Set the creation date of the note.
     *
     * @param \DateTimeImmutable $createdAt The creation date to set.
     *
     * @return $this
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the update date of the note.
     *
     * @return \DateTimeImmutable|null The update date.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Set the update date of the note.
     *
     * @param \DateTimeImmutable|null $updatedAt The update date to set.
     *
     * @return $this
     */
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get the category associated with the note.
     *
     * @return Category|null The category of the note.
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Set the category for the note.
     *
     * @param Category|null $category The category to associate with the note.
     *
     * @return $this
     */
    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'notes')]
    #[ORM\JoinTable(name: 'note_tags')]
    private Collection $tags;

    /**
     * Initializes the tags collection.
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * Get all the tags associated with the note.
     *
     * @return Collection The collection of tags.
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * Adds a tag to the note.
     *
     * @param Tag $tag The tag to add.
     *
     * @return $this
     */
    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * Removes a tag from the note.
     *
     * @param Tag $tag The tag to remove.
     *
     * @return $this
     */
    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * Get the user associated with the note.
     *
     * @return User|null The user associated with the note.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the user for the note.
     *
     * @param User $user The user to associate with the note.
     *
     * @return $this
     */
    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
