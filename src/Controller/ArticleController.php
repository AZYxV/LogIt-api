<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Test;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class ArticleController extends AbstractController
{
    #[Route('/articles', name: 'api_articles', methods: ['GET'])]
    public function getArticles(ArticleRepository $ArticleRepository, SerializerInterface $serializer): JsonResponse
    {

        $articles = $ArticleRepository->findAll();
        $json = $serializer->serialize($articles,'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/article/{id}', name: 'api_article', methods: ['GET'])]
    public function getArticleById(Article $article, SerializerInterface $serializer): JsonResponse
    {
        $json = $serializer->serialize($article, 'json');
        return new JsonResponse($json, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
