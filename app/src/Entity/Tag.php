<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Tag
 *
 * Represents a tag entity that can be associated with multiple notes and to-do tasks.
 */
#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Note::class, mappedBy: "tags")]
    private Collection $notes;

    #[ORM\ManyToMany(targetEntity: ToDo::class, mappedBy: "tags")]
    private Collection $toDos;

    /**
     * Tag constructor.
     *
     * Initializes the collections for notes and to-dos.
     */
    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->toDos = new ArrayCollection();
    }

    /**
     * Gets the ID of the tag.
     *
     * @return int|null The ID of the tag.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets the name of the tag.
     *
     * @return string|null The name of the tag.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name of the tag.
     *
     * @param string $name The name of the tag.
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the notes associated with this tag.
     *
     * @return Collection The collection of notes associated with the tag.
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    /**
     * Gets the to-dos associated with this tag.
     *
     * @return Collection The collection of to-dos associated with the tag.
     */
    public function getToDos(): Collection
    {
        return $this->toDos;
    }
}
