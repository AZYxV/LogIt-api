<?php

namespace App\Controller;

use App\Entity\Log;
use App\Repository\LogRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/log')]
class LogController extends AbstractController
{
    #[Route('/new', name: 'api_log_new', methods: ['POST'])]
    public function newArticle(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        $user = $this->getUser();

        try {

            $log = new Log;
            $log->setContent($data['content'] ?? '');
            $log->setAuthor($user);
            $log->setCreatedAt(new DateTimeImmutable());

            $errors = $validator->validate($log);

            if (count($errors) > 0) {
                $errorMessages = [];

                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return new JsonResponse(['code' => Response::HTTP_BAD_REQUEST, 'status' => 'error', 'message' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $em->persist($log);
            $em->flush();

            return new JsonResponse(['code' => Response::HTTP_CREATED,'status' => 'success'],Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/', name: 'api_logs', methods: ['GET'])]
    public function getLog(LogRepository $LogRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache, Request $request): JsonResponse
    {

        try {

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 3);

            $idCache = "getLog-" . $page . "-" . $limit;

            $logs = $cache->get($idCache, function (ItemInterface $item) use ($LogRepository, $page, $limit) {
                $item->tag('logsCache');
                $item->expiresAfter(60);
                return $LogRepository->findAllWithPagination($page, $limit);
            });

            if(!$logs)
            {
                return new JsonResponse(['code' => Response::HTTP_NOT_FOUND, 'status' => 'error', 'message' => 'Aucun log trouvé'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($serializer->serialize(['code' => Response::HTTP_OK, 'status' => 'success', 'result' => $logs],'json'), Response::HTTP_OK, [], true);

        }
        catch(\Exception|InvalidArgumentException $e)
        {
            return new JsonResponse(['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_log', methods: ['GET'])]
    public function getLogById($id, LogRepository $logRepository, SerializerInterface $serializer): JsonResponse
    {
        try {
            $log = $logRepository->find($id);

            if (!$log) {
                return new JsonResponse(['code' => Response::HTTP_NOT_FOUND, 'status' => 'error', 'message' => 'Ce log n\'existe pas.'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($serializer->serialize(['code' => Response::HTTP_OK, 'status' => 'success', 'result' => $log],'json'), Response::HTTP_OK, ['accept' => 'json'], true);

        } catch (\Exception $e) {
            return new JsonResponse(['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/delete', name: 'api_log_delete', methods: ['DELETE'])]
    public function deleteLog($id, LogRepository $logRepository, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            $log = $logRepository->find($id);

            if (!$log) {
                return new JsonResponse(['code' => Response::HTTP_NOT_FOUND, 'status' => 'error', 'message' => 'Ce log n\'existe pas.'], Response::HTTP_NOT_FOUND);
            }

            if($cache->hasItem('logsCache'))
            {
                $cache->invalidateTags(['logsCache']);
            }

            $em->remove($log);
            $em->flush();

            return new JsonResponse(['code' => Response::HTTP_OK, 'status' => 'success'], Response::HTTP_OK);
        } catch (\Exception|InvalidArgumentException $e) {
            return new JsonResponse(['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}