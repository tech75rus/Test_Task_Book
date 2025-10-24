<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/authors')]
class AuthorController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    // GET /api/v1/authors - список всех авторов
    #[Route('', methods: ['GET'])]
    public function index(AuthorRepository $repository): JsonResponse
    {
        $authors = $repository->findAllWithBookCount();

        $data = [];
        foreach ($authors as $author) {
            $data[] = [
                'id' => $author['id'],
                'firstName' => $author['firstName'],
                'lastName' => $author['lastName'],
                'birthDate' => $author['birthDate']->format('Y-m-d'),
                'bookCount' => $author['bookCount'],
            ];
        }

        return $this->json($data);
    }

    // GET /api/v1/authors/{id} - автор по ID
    #[Route('/{id}', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $author = $this->em->getRepository(Author::class)->find($id);
        if (!$author) {
            return $this->json(['message' => 'Author not found'], 404);
        }

        return $this->json([
            'id' => $author->getId(),
            'firstName' => $author->getFirstName(),
            'lastName' => $author->getLastName(),
            'birthDate' => $author->getBirthDate()->format('Y-m-d'),
        ]);
    }

    // POST /api/v1/authors - создать автора
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $author = new Author();
        $author->setFirstName($data['firstName']);
        $author->setLastName($data['lastName']);

        // Валидация даты рождения
        try {
            $birthDate = new \DateTime($data['birthDate']);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Error: ' . $e->getMessage(), 
                'Correct example' => '2005.06.18 || 18.06.2005 || 18-06-2005 || 18-06-05'
            ], 400);
        }

        $author->setBirthDate($birthDate);
        $author->setCreateAt(new \DateTimeImmutable());
        $author->setUpdateAt(new \DateTimeImmutable());
        
        $em->persist($author);
        $em->flush();
        
        return $this->json([
            'id' => $author->getId(),
            'message' => 'Author created successfully'
        ], 201);
    }

    // PUT /api/v1/authors/{id} - обновить автора
    #[Route('/{id}', methods: ['PUT'])]
    public function update($id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) { return $this->json(['message' => 'No data provided'], 400); }

        $author = $this->em->getRepository(Author::class)->find($id);

        if (!$author) { return $this->json(['message' => 'Author not found'], 404); }

        $author->setFirstName($data['firstName'] ?? $author->getFirstName());
        $author->setLastName($data['lastName'] ?? $author->getLastName());
        
        if (isset($data['birthDate'])) {
            $author->setBirthDate(new \DateTime($data['birthDate']));
        }
        
        $author->setUpdateAt(new \DateTimeImmutable());
        $this->em->flush();
        
        return $this->json(['message' => 'Author updated successfully']);
    }

    // DELETE /api/v1/authors/{id} - удалить автора
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Author $author, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($author);
        $em->flush();
        
        return $this->json(['message' => 'Author deleted successfully']);
    }
}