<?php

/**
 * @license MIT
 */

namespace App\Entity;

use App\Repository\ToDoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reprezentuje zadanie to-do, które może mieć kategorię, tagi i współpracowników.
 */
#[ORM\Entity(repositoryClass: ToDoRepository::class)]
#[ORM\Table(
    name: 'to_do',
    indexes: [
        new ORM\Index(name: 'idx_todo_user_created', columns: ['user_id', 'created_at']),
        new ORM\Index(name: 'idx_todo_category', columns: ['category_id']),
        new ORM\Index(name: 'idx_todo_updated', columns: ['updated_at']),
    ]
)]
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
     * Inicjalizuje kolekcje tagów i współpracowników.
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->collaborators = new ArrayCollection();
    }

    /**
     * Zwraca token udostępniania zadania.
     *
     * @return string|null Token udostępniania lub null
     */
    public function getShareToken(): ?string
    {
        return $this->shareToken;
    }

    /**
     * Ustawia token udostępniania zadania.
     *
     * @param string|null $shareToken Token udostępniania
     *
     * @return self Fluent interface
     */
    public function setShareToken(?string $shareToken): self
    {
        $this->shareToken = $shareToken;

        return $this;
    }

    /**
     * Zwraca właściciela zadania.
     *
     * @return User|null Właściciel lub null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Ustawia właściciela zadania.
     *
     * @param User $user Użytkownik będący właścicielem
     *
     * @return self Fluent interface
     */
    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Zwraca identyfikator zadania.
     *
     * @return int|null Identyfikator zadania
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Zwraca treść zadania.
     *
     * @return string|null Treść zadania
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Ustawia treść zadania.
     *
     * @param string $content Treść
     *
     * @return self Fluent interface
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Sprawdza, czy zadanie jest ukończone.
     *
     * @return bool|null Flaga ukończenia
     */
    public function isDone(): ?bool
    {
        return $this->isDone;
    }

    /**
     * Ustawia status ukończenia zadania.
     *
     * @param bool $isDone Flaga ukończenia
     *
     * @return self Fluent interface
     */
    public function setIsDone(bool $isDone): static
    {
        $this->isDone = $isDone;

        return $this;
    }

    /**
     * Zwraca tytuł zadania
     *
     * @return string|null tytuł lub null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Ustawia tytuł zadania
     *
     * @param string $title tytuł zadania
     *
     * @return self fluent interface
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Zwraca datę utworzenia.
     *
     * @return \DateTimeImmutable|null Data utworzenia
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Ustawia datę utworzenia.
     *
     * @param \DateTimeImmutable $createdAt Data utworzenia
     *
     * @return self Fluent interface
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Zwraca datę ostatniej aktualizacji.
     *
     * @return \DateTimeImmutable|null Data aktualizacji
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Ustawia datę ostatniej aktualizacji.
     *
     * @param \DateTimeImmutable|null $updatedAt Data aktualizacji
     *
     * @return self Fluent interface
     */
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Zwraca kategorię zadania.
     *
     * @return Category|null Kategoria
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Ustawia kategorię zadania.
     *
     * @param Category|null $category Kategoria
     *
     * @return self Fluent interface
     */
    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Zwraca tagi przypisane do zadania
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Tag> kolekcja tagów
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * Ustawia pełną kolekcję tagów
     *
     * @param \Doctrine\Common\Collections\Collection<int, \App\Entity\Tag> $tags kolekcja tagów
     *
     * @return self fluent interface
     */
    public function setTags(Collection $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Dodaje tag do zadania
     *
     * @param \App\Entity\Tag $tag tag do dodania
     *
     * @return self fluent interface
     */
    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * Usuwa tag z zadania
     *
     * @param \App\Entity\Tag $tag tag do usunięcia
     *
     * @return self fluent interface
     */
    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * Zwraca współpracowników przypisanych do zadania.
     *
     * @return Collection<int, User>
     */
    public function getCollaborators(): Collection
    {
        return $this->collaborators;
    }

    /**
     * Dodaje współpracownika do zadania.
     *
     * @param User $user Współpracownik do dodania
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
     * Usuwa współpracownika z zadania.
     *
     * @param User $user Współpracownik do usunięcia
     *
     * @return self
     */
    public function removeCollaborator(User $user): self
    {
        $this->collaborators->removeElement($user);

        return $this;
    }

    /**
     * Sprawdza, czy użytkownik jest współpracownikiem
     *
     * @param \App\Entity\User $user użytkownik do sprawdzenia
     *
     * @return bool true gdy jest współpracownikiem
     */
    public function isCollaborator(User $user): bool
    {
        return $this->collaborators->contains($user);
    }
}
