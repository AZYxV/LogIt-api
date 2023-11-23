<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setPseudo('azyxv');
        $user->setEmail('nschpro@gmail.com');
        $user->setPassword($this->passwordHasher->hashPassword(
            $user,
            'ChangePasswordHere'
        ));
        $user->setBirthday(new \DateTime('2002-11-12'));
        $user->setFirstname('Nathan');
        $user->setLastname('Sichouc');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($user);

        $manager->flush();
    }
}
