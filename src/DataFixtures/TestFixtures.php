<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Test;

class TestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
       
        for($i = 0; $i < 20; $i++)
        {
            $test = new Test;
            $test->setTitle("Test " . $i);
            $test->setContent("Ceci est le test numéro " . $i);
            $manager->persist($test);
        }

        $manager->flush();
    }
}
