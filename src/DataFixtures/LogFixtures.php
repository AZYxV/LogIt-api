<?php

namespace App\DataFixtures;

use App\Entity\Log;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LogFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $userRepositoy = $manager->getRepository(User::class);
        $user = $userRepositoy->find(1);

        for ($i = 0; $i < 20; $i++) {
            $log = new Log();
            $log->setContent('Ceci est le log numéro ' . $i);
            $log->setAuthor($user);
            $log->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($log);
        }

        $manager->flush();
    }
}
