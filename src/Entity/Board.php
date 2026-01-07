<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BoardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoardRepository::class)]
#[ORM\Table(name: 'boards')]
class Board implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\OneToMany(targetEntity: BoardList::class, mappedBy: 'board', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $lists;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->lists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, BoardList>
     */
    public function getLists(): Collection
    {
        return $this->lists;
    }

    public function addList(BoardList $list): self
    {
        if (!$this->lists->contains($list)) {
            $this->lists->add($list);
            $list->setBoard($this);
        }

        return $this;
    }

    public function removeList(BoardList $list): self
    {
        if ($this->lists->removeElement($list)) {
            // set the owning side to null (unless already changed)
            if ($list->getBoard() === $this) {
                $list->setBoard(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'position' => $this->position,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'lists' => $this->lists->map(fn($list) => $list->jsonSerialize())->toArray(),
        ];
    }
}