<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user for Carpe Diem',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🎯 Create Admin User - Carpe Diem');

        $email = 'admin@carpediem.com';
        $password = 'admin123';
        $firstName = 'Admin';
        $lastName = 'User';
        $username = 'admin';

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if ($existingUser) {
            $io->error('❌ Admin user already exists!');
            $io->note([
                'If you want to reset the password, run:',
                'php bin/console app:reset-password'
            ]);
            return Command::FAILURE;
        }

        // Create new user
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setUsername($username);
        $user->setRoles(['ROLE_ADMIN']);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('✅ Admin user created successfully!');
        $io->text([
            '📧 Email: ' . $email,
            '🔑 Password: ' . $password,
            '👤 Name: ' . $firstName . ' ' . $lastName,
            '🆔 Username: ' . $username,
        ]);
        $io->newLine();
        $io->warning('⚠️  IMPORTANT: Change the password immediately after first login!');
        $io->note('Login at: /login');

        return Command::SUCCESS;
    }
}