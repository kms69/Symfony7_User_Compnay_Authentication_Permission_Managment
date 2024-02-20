<?php

namespace App\Entity;

use AllowDynamicProperties;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[AllowDynamicProperties] #[ORM\Entity]
#[ORM\Table(name: "app_user")]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    #[Assert\Regex(pattern: '/^[A-Z][A-Za-z ]*$/', message: "Name must start with an uppercase letter and contain only letters and spaces.")]
    private ?string $name;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\Choice(choices: ['ROLE_USER', 'ROLE_COMPANY_ADMIN', 'ROLE_SUPER_ADMIN'])]
    private ?string $role;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[Assert\NotBlank(groups: ['user', 'company_admin'])]
    private ?Company $company = null;
    private array $roles = [];

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
    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @inheritDoc
     */

        public function getRoles(): array
        {
        return $this->roles;
    }


    // Your other methods...
    public function setId(int $id): void
    {

        $this->id = $id;
    }

    private function isAdmin(): bool
    {
        // Implement logic to check if the user is an admin
        // Return true if the user is an admin, false otherwise
        // For example:
        return $this->role === 'admin'; // Assuming you have a 'role' property in your User entity
    }

    private function isSuperAdmin(): bool
    {
        // Implement logic to check if the user is a super admin
        // Return true if the user is a super admin, false otherwise
        // For example:
        return $this->role === 'super_admin'; // Assuming you have a 'role' property in your User entity
    }

    public function setRoles(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }
    public function getUsername(): string
    {
        // Return the unique identifier for the user (e.g., username or email)
        return $this->name;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return $this->name;
    }
    /**
     * Set the value of companyId
     *
     * @param int|null $companyId
     * @return self
     */
    public function setCompanyId(?int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }
}
