<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->toDos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function getToDos(): Collection
    {
        return $this->toDos;
    }
}
