<?php



namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;


class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByRole(string $name): ?string
    {
        $user = $this->createQueryBuilder('u')
            ->select('u.role')
            ->where('u.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->setMaxResults(1) // Limit the result to one user
            ->getOneOrNullResult();

        return $user ? $user['role'] ?? null : null;
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        return $this->findOneBy(['name' => $identifier]);
    }
}
