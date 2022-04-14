<?php

namespace App\DataFixtures;

use App\Entity\Size;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SizeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $size = new Size();
        $size->setName('S');

        $manager->persist($size);
        $manager->flush();
    }
}
