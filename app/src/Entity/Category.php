<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class representing a category that can contain notes and to-dos.
 */
#[UniqueEntity(fields: ['name', 'user'], message: 'Masz już kategorię o tej nazwie.')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
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
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'category')]
    private Collection $notes;

    /**
     * @var Collection<int, ToDo>
     */
    #[ORM\OneToMany(targetEntity: ToDo::class, mappedBy: 'category')]
    private Collection $toDos;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Initializes the Category entity with empty collections for notes and to-dos.
     */
    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->toDos = new ArrayCollection();
    }

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

    /**
     * Adds a note to the category.
     *
     * @param Note $note The note to add.
     *
     * @return $this
     */
    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setCategory($this);
        }

        return $this;
    }

    /**
     * Removes a note from the category.
     *
     * @param Note $note The note to remove.
     *
     * @return $this
     */
    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getCategory() === $this) {
                $note->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * Adds a to-do to the category.
     *
     * @param ToDo $toDo The to-do to add.
     *
     * @return $this
     */
    public function addToDo(ToDo $toDo): static
    {
        if (!$this->toDos->contains($toDo)) {
            $this->toDos->add($toDo);
            $toDo->setCategory($this);
        }

        return $this;
    }

    /**
     * Removes a to-do from the category.
     *
     * @param ToDo $toDo The to-do to remove.
     *
     * @return $this
     */
    public function removeToDo(ToDo $toDo): static
    {
        if ($this->toDos->removeElement($toDo)) {
            if ($toDo->getCategory() === $this) {
                $toDo->setCategory(null);
            }
        }

        return $this;
    }
}
