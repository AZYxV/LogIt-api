<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api')]
class ArticleController extends AbstractController
{

    #[Route('/article/new', name: 'api_article_new', methods: ['POST'])]
    public function newArticle(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        $user = $this->getUser();

        try
        {

            $article = new Article;
            $article->setTitle($data['title'] ?? '');
            $article->setContent($data['content'] ?? '');
            $article->setAuthor($user);
            $article->setCreatedAt(new DateTimeImmutable());
            $article->setUpdatedAt(new DateTimeImmutable());

            $errors = $validator->validate($article);

            if (count($errors) > 0) {
                $errorMessages = array_map(
                    fn ($error) => $error->getMessage(),
                    iterator_to_array($errors)
                );

                return new JsonResponse(['code' => Response::HTTP_BAD_REQUEST, 'status' => 'error', 'message' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $em->persist($article);
            $em->flush();

            return new JsonResponse(['code' => Response::HTTP_CREATED,'status' => 'success'],Response::HTTP_CREATED);
        }
        catch (\Exception $e)
        {
            return new JsonResponse(['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/article', name: 'api_articles', methods: ['GET'])]
    public function getArticle(ArticleRepository $ArticleRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache, Request $request): JsonResponse
    {

        try {

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 3);

            $idCache = "getArticle-" . $page . "-" . $limit;

            $articles = $cache->get($idCache, function (ItemInterface $item) use ($ArticleRepository, $page, $limit) {
                $item->tag('articlesCache');
                $item->expiresAfter(60);
                return $ArticleRepository->findAllWithPagination($page, $limit);
            });

            if(!$articles)
            {
                return new JsonResponse(['code' => Response::HTTP_NOT_FOUND, 'status' => 'error', 'message' => 'Aucun article trouvé'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($serializer->serialize(['code' => Response::HTTP_OK, 'status' => 'success', 'result' => $articles],'json'), Response::HTTP_OK, [], true);

        }
        catch(\Exception|InvalidArgumentException $e)
        {
            return new JsonResponse(['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/article/{id}', name: 'api_article', methods: ['GET'])]
    public function getArticleById($id, ArticleRepository $articleRepository, SerializerInterface $serializer): JsonResponse
    {
        try {
            $article = $articleRepository->find($id);

            if (!$article) {
                return new JsonResponse(['code' => Response::HTTP_NOT_FOUND, 'status' => 'error', 'message' => 'Cet article n\'existe pas.'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($serializer->serialize(['code' => Response::HTTP_OK, 'status' => 'success', 'result' => $article],'json'), Response::HTTP_OK, ['accept' => 'json'], true);

        } catch (\Exception $e) {
            return new JsonResponse(['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/article/{id}/edit', name: 'api_article_edit', methods: ['PUT'])]
    public function editArticle($id, Request $request, Article $article, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            if (!$article) {
                return new JsonResponse(['code' => Response::HTTP_NOT_FOUND, 'status' => 'error', 'message' => 'Cet article n\'existe pas.'], Response::HTTP_NOT_FOUND);
            }

            $user = $this->getUser();
            if (!$this->isGranted('ROLE_ADMIN') && !($this->isGranted('ROLE_USER') && $user === $article->getAuthor())) {
                return new JsonResponse(['code' => Response::HTTP_FORBIDDEN, 'status' => 'error', 'message' => 'Vous n\'avez pas les droits nécessaires pour modifier cet article.'], Response::HTTP_FORBIDDEN);
            }

            $data = $serializer->deserialize($request->getContent(), Article::class, 'json');

            $article->setTitle($data->getTitle() ?? $article->getTitle());
            $article->setContent($data->getContent() ?? $article->getContent());
            $article->setUpdatedAt(new DateTimeImmutable());

            $errors = $validator->validate($article);

            if (count($errors) > 0) {
                $errorMessages = array_map(
                    fn ($error) => $error->getMessage(),
                    iterator_to_array($errors)
                );

                return new JsonResponse(['code' => Response::HTTP_BAD_REQUEST, 'status' => 'error', 'message' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $em->flush();

            if($cache->hasItem('articlesCache'))
            {
                $cache->invalidateTags(['articlesCache']);
            }

            return new JsonResponse(['code' => Response::HTTP_OK, 'status' => 'success'], Response::HTTP_OK);
        } catch (\Exception|InvalidArgumentException $e) {
            return new JsonResponse(['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/article/{id}/delete', name: 'api_article_delete', methods: ['DELETE'])]
    public function deleteArticle($id, ArticleRepository $articleRepository, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            $article = $articleRepository->find($id);

            if (!$article) {
                return new JsonResponse(['code' => Response::HTTP_NOT_FOUND, 'status' => 'error', 'message' => 'Cet article n\'existe pas.'], Response::HTTP_NOT_FOUND);
            }

            $user = $this->getUser();
            if (!$this->isGranted('ROLE_ADMIN') && !($this->isGranted('ROLE_USER') && $user === $article->getAuthor())) {
                return new JsonResponse(['code' => Response::HTTP_FORBIDDEN, 'status' => 'error', 'message' => 'Vous n\'avez pas les droits nécessaires pour supprimer cet article.'], Response::HTTP_FORBIDDEN);
            }

            if($cache->hasItem('articlesCache'))
            {
                $cache->invalidateTags(['articlesCache']);
            }

            $em->remove($article);
            $em->flush();

            return new JsonResponse(['code' => Response::HTTP_OK, 'status' => 'success'], Response::HTTP_OK);
        } catch (\Exception|InvalidArgumentException $e) {
            return new JsonResponse(['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
