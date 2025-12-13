<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create ADMIN user
        $admin = new User();
        $admin->setEmail('admin@carpediem.com');
        $admin->setUsername('admin'); // ADD THIS
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setIsActive(true);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'Admin123!'  // Using EXCLAMATION mark, NOT number 1
        );
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Create STAFF user
        $staff = new User();
        $staff->setEmail('staff@carpediem.com');
        $staff->setUsername('staff');
        $staff->setFirstName('Staff');
        $staff->setLastName('Member');
        $staff->setRoles(['ROLE_STAFF', 'ROLE_USER']);
        $staff->setIsActive(true);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $staff,
            'Staff123!'  // Using EXCLAMATION mark
        );
        $staff->setPassword($hashedPassword);
        $manager->persist($staff);

        // Create CUSTOMER user
        $customer = new User();
        $customer->setEmail('customer@carpediem.com');
        $customer->setUsername('customer');
        $customer->setFirstName('Customer');
        $customer->setLastName('Guest');
        $customer->setRoles(['ROLE_CUSTOMER', 'ROLE_USER']);
        $customer->setIsActive(true);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $customer,
            'Customer123!'  // Using EXCLAMATION mark
        );
        $customer->setPassword($hashedPassword);
        $manager->persist($customer);

        $manager->flush();
    }
}