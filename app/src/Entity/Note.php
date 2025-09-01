<?php

/**
 * @license MIT
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\NoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity representing a user note with title, content, category, tags and timestamps.
 */
#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Table(
    name: 'note',
    indexes: [
        new ORM\Index(name: 'idx_note_user_created', columns: ['user_id', 'created_at']),
        new ORM\Index(name: 'idx_note_category', columns: ['category_id']),
        new ORM\Index(name: 'idx_note_updated', columns: ['updated_at']),
    ]
)]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Category::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $image = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinTable(
        name: 'note_tags',
        joinColumns: [
            new ORM\JoinColumn(name: 'note_id', referencedColumnName: 'id', onDelete: 'CASCADE'),
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id', onDelete: 'CASCADE'),
        ]
    )]
    private Collection $tags;

    /**
     * Initializes the tags collection.
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * Get the image file name for the note.
     *
     * @return string|null the image file name
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Set the image file name for the note.
     *
     * @param string|null $image the image file name to set
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
     * @return int|null the note ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the title of the note.
     *
     * @return string|null the title of the note
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set the title of the note.
     *
     * @param string $title the title of the note
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
     * @return string|null the content of the note
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set the content of the note.
     *
     * @param string $content the content of the note
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
     * @return \DateTimeImmutable|null the creation date
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Set the creation date of the note.
     *
     * @param \DateTimeImmutable $createdAt the creation date to set
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
     * @return \DateTimeImmutable|null the update date
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Set the update date of the note.
     *
     * @param \DateTimeImmutable|null $updatedAt the update date to set
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
     * @return Category|null the category of the note
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Set the category for the note.
     *
     * @param Category|null $category the category to associate with the note
     *
     * @return $this
     */
    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get all the tags associated with the note.
     *
     * @return Collection the collection of tags
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * Adds a tag to the note.
     *
     * @param Tag $tag the tag to add
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
     * @param Tag $tag the tag to remove
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
     * @return User|null the user associated with the note
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the user for the note.
     *
     * @param User $user the user to associate with the note
     *
     * @return $this
     */
    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
