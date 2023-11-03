<?php

namespace App\Controller\Api;

use App\Entity\Test;
use App\Repository\TestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api')]
class TestController extends AbstractController
{
    #[Route('/test', name: 'api_test', methods: ['GET'])]
    public function getTests(TestRepository $TestRepository, SerializerInterface $serializer): JsonResponse
    {

        $testList = $TestRepository->findAll();
        $jsonTestList = $serializer->serialize($testList,'json');

        return new JsonResponse($jsonTestList, Response::HTTP_OK, [], true);
    }

    #[Route('/test/{id}', name: 'api_detailTest', methods: ['GET'])]
    public function getDetailTest(Test $test, SerializerInterface $serializer): JsonResponse
    {
        $jsonTest = $serializer->serialize($test, 'json');
        return new JsonResponse($jsonTest, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
