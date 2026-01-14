<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource(
    normalizationContext: ['groups' => ['review:read']],
    denormalizationContext: ['groups' => ['review:write']],
    collectionOperations: ['get', 'post'],
    itemOperations: ['get', 'put', 'delete']
)]
#[MongoDB\Document(collection: "reviews")]
class ReviewMongo
{
    #[MongoDB\Id]
    #[Groups(['review:read'])]
    private ?string $id = null;

    #[MongoDB\Field(type: "string")]
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    #[Groups(['review:read', 'review:write'])]
    private string $comment;

    #[MongoDB\Field(type: "int")]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['review:read', 'review:write'])]
    private int $note;

    #[MongoDB\Field(type: "date")]
    #[Groups(['review:read'])]
    private \DateTimeInterface $createdAt;

    #[MongoDB\Field(type: "int")]
    #[Groups(['review:read', 'review:write'])]
    private int $userId;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Getters / setters
    public function getId(): ?string { return $this->id; }

    public function getComment(): string { return $this->comment; }
    public function setComment(string $comment): self { $this->comment = $comment; return $this; }

    public function getNote(): int { return $this->note; }
    public function setNote(int $note): self { $this->note = $note; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $dt): self { $this->createdAt = $dt; return $this; }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }
}