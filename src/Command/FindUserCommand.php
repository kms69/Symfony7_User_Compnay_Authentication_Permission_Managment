<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:find-user',
    description: 'Find a user in the database by name',
)]
class FindUserCommand extends Command
{
    protected static $defaultName = 'app:find-user';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = 'ali'; // Replace with the name of the user you want to find

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['name' => $name]);

        if ($user) {
            $output->writeln(sprintf('User found: %s', $user->getName()));
        } else {
            $output->writeln('User not found.');
        }

        return Command::SUCCESS;
    }
}
