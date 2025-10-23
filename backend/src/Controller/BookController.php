<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Author;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/books')]
class BookController extends AbstractController
{
    // GET /api/v1/books - список книг с пагинацией
    #[Route('', methods: ['GET'])]
    public function index(Request $request, BookRepository $repository): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));     // минимум 1
        $limit = max(1, min(100, (int)$request->query->get('limit', 10))); // от 1 до 100
        
        $books = $repository->findAllWithPagination($page, $limit);
        $total = $repository->count([]);

        
        $data = [];
        foreach ($books as $book) {
            $data[] = $this->bookToArray($book);
        }
        
        return $this->json([
            'data' => $data,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ]
        ]);
    }

    // GET /api/v1/books/by-id - книга по ID
    #[Route('/{id}', methods: ['GET'])]
    public function getById(int $id, BookRepository $repository): JsonResponse
    {
        $book = $repository->find($id);

        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        return $this->json($this->bookToArray($book));
    }

    // POST /api/v1/books - создать книгу
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $author = $em->getRepository(Author::class)->find($data['authorId']);
        if (!$author) {
            return $this->json(['error' => 'Author not found'], 404);
        }
        
        $book = new Book();
        $book->setTitle($data['title']);
        $book->setDescription($data['description'] ?? null);
        $book->setPublicationDate(new \DateTime($data['publicationDate']));
        $book->setAuthor($author);
        $book->setCreateAt(new \DateTimeImmutable());
        $book->setUpdateAt(new \DateTimeImmutable());
        
        $em->persist($book);
        $em->flush();
        
        return $this->json([
            'id' => $book->getId(),
            'message' => 'Book created successfully'
        ], 201);
    }

    // PUT /api/v1/books/{id} - обновить книгу
    #[Route('/{id}', methods: ['PUT'])]
    public function update(Book $book, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $book->setTitle($data['title'] ?? $book->getTitle());
        $book->setDescription($data['description'] ?? $book->getDescription());
        
        if (isset($data['publicationDate'])) {
            $book->setPublicationDate(new \DateTime($data['publicationDate']));
        }
        
        if (isset($data['authorId'])) {
            $author = $em->getRepository(Author::class)->find($data['authorId']);
            if ($author) {
                $book->setAuthor($author);
            }
        }
        
        $book->setUpdateAt(new \DateTimeImmutable());
        $em->flush();
        
        return $this->json(['message' => 'Book updated successfully']);
    }

    // DELETE /api/v1/books/{id} - удалить книгу
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Book $book, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($book);
        $em->flush();
        
        return $this->json(['message' => 'Book deleted successfully']);
    }

    // Вспомогательный метод для преобразования книги в массив
    private function bookToArray(Book $book): array
    {
        return [
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'description' => $book->getDescription(),
            'publicationDate' => $book->getPublicationDate()->format('Y-m-d'),
            'author' => [
                'id' => $book->getAuthor()->getId(),
                'name' => $book->getAuthor()->getFirstName() . ' ' . $book->getAuthor()->getLastName(),
            ],
            'createdAt' => $book->getCreateAt()->format('Y-m-d H:i:s'),
            'updateAt' => $book->getUpdateAt()->format('Y-m-d H:i:s'),
        ];
    }
}