<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $customers = [
            ['Juan Dela Cruz', 'juan@test.com', '09123456789', 'Santander, Cebu'],
            ['Maria Clara', 'maria@test.com', '09234567890', 'Cebu City'],
            ['Walk-in Customer', 'walkin@test.com', '09999999999', 'N/A'],
        ];

        foreach ($customers as [$name, $email, $phone, $address]) {
            $customer = new Customer();
            $customer->setName($name);
            $customer->setEmail($email);
            $customer->setPhone($phone);
            $customer->setAddress($address); // ⭐ REQUIRED

            $manager->persist($customer);
        }

        $manager->flush();
    }
}
