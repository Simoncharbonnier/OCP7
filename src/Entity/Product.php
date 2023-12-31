<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "product",
 *          parameters = { "id" = "expr(object.getId())" }
 *      )
 * )
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /**
     * @var ?int $id product id
     */
    private ?int $id = null;

    #[ORM\Column(length: 128)]
    /**
     * @var ?string $name product name
     */
    private ?string $name = null;

    #[ORM\Column(length: 128)]
    /**
     * @var ?string $brand product brand
     */
    private ?string $brand = null;

    #[ORM\Column(type: Types::TEXT)]
    /**
     * @var ?string $description product description
     */
    private ?string $description = null;

    #[ORM\Column]
    /**
     * @var ?float $price product price
     */
    private ?float $price = null;

    #[ORM\Column]
    /**
     * @var ?\DateTime $released_at product release date
     */
    private ?\DateTime $released_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getReleasedAt(): ?\DateTime
    {
        return $this->released_at;
    }

    public function setReleasedAt(\DateTime $released_at): static
    {
        $this->released_at = $released_at;

        return $this;
    }
}
