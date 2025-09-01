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

    /**
     * Gets the ID of the tag.
     *
     * @return int|null the ID of the tag
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets the name of the tag.
     *
     * @return string|null the name of the tag
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name of the tag.
     *
     * @param string $name the name of the tag
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
