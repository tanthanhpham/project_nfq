<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setName('User name');
        $user->setEmail('user@gmail.com');
        $user->setPhone('0888888888');
        $user->setAddress('Address user');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());
        $user->setPassword('password');
        $manager->persist($user);

        $user1 = new User();
        $user1->setName('User name');
        $user1->setEmail('user1@gmail.com');
        $user1->setPhone('0888888888');
        $user1->setAddress('Address user');
        $user1->setRoles(['ROLE_USER']);
        $user1->setCreatedAt(new \DateTime());
        $user1->setUpdatedAt(new \DateTime());
        $user1->setPassword('password');
        $manager->persist($user1);

        $manager->flush();
    }
}
