<?php
namespace  App\DataFixtures;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class UserSeeder extends Fixture implements ORMFixtureInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function load(ObjectManager $manager): void
    {
        // Call the seed method here
        $this->seed();
    }

    public function seed(): void
    {
        $this->createSuperAdmin();
        $this->createCompany();
    }

    private function createSuperAdmin(): void
    {
        $superAdminName = 'super_user'; // Change this to the desired name of the super admin
        $superAdminRole = 'ROLE_SUPER_ADMIN';

        $superAdmin = new User();
        $superAdmin->setName($superAdminName);
        $superAdmin->setRole($superAdminRole);

        // Persist the super admin object here
        $this->entityManager->persist($superAdmin);
        $this->entityManager->flush();
    }

    private function createCompany(): void
    {
        $companyName = 'Example Company'; // Change this to the desired name of the company

        $company = new Company();
        $company->setName($companyName);

        // Persist the company object here
        $this->entityManager->persist($company);
        $this->entityManager->flush();
    }
}
