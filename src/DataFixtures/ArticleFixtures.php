<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $userRepositoy = $manager->getRepository(User::class);
        $user = $userRepositoy->find(1);

        for($i = 0; $i < 20; $i++)
        {

            $article = new Article;
            $article->setTitle("Article " . $i);
            $article->setContent("Ceci est l'article numéro " . $i);
            $article->setAuthor($user);
            $article->setCreatedAt(new \DateTimeImmutable());
            $article->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($article);
        }

        $manager->flush();
    }
}
