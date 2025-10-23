<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:author-books',
    description: 'Generate 300k books using Native SQL',
)]
class AuthorBooksCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->entityManager->getConnection();

        $io->title('🚀 Native SQL Book Generator');

        // 1. Получаем или создаем авторов
        $authors = $this->getOrCreateAuthors();
        
        if (empty($authors)) {
            $io->error('No authors found');
            return Command::FAILURE;
        }

        $io->progressStart(300000);

        // 2. Данные для генерации
        $adjectives = ['Dark', 'Mysterious', 'Lost', 'Forgotten', 'Hidden', 'Secret', 'Ancient', 'Digital'];
        $nouns = ['World', 'Kingdom', 'Empire', 'City', 'Forest', 'Ocean', 'Mountain', 'Star'];
        $genres = ['Fantasy', 'Sci-Fi', 'Mystery', 'Romance', 'Thriller', 'Horror'];

        $totalBooks = 0;

        foreach ($authors as $author) {
            $io->note("Generating books for: {$author->getFirstName()} {$author->getLastName()}");

            $batchSize = 1000; // Уменьшили батч для надежности
            $batches = 100; // 100 батчей × 1000 = 100k книг

            for ($batch = 0; $batch < $batches; $batch++) {
                $values = [];

                for ($i = 0; $i < $batchSize; $i++) {
                    $bookNumber = $batch * $batchSize + $i;
                    
                    // Генерируем данные
                    $adjective = $adjectives[array_rand($adjectives)];
                    $noun = $nouns[array_rand($nouns)];
                    $genre = $genres[array_rand($genres)];
                    
                    $title = "{$adjective} {$noun} #{$bookNumber}";
                    $description = "A {$genre} novel by {$author->getFirstName()} {$author->getLastName()}";
                    $publicationDate = $this->randomDate('-10 years');
                    $now = date('Y-m-d H:i:s');

                    // ✅ ПРАВИЛЬНО: Экранируем значения
                    $values[] = sprintf(
                        '(%s, %s, %s, %d, %s, %s)',
                        $connection->quote($title),
                        $connection->quote($description),
                        $connection->quote($publicationDate),
                        $author->getId(),
                        $connection->quote($now),
                        $connection->quote($now)
                    );
                }

                // 3. Выполняем запрос
                $sql = "INSERT INTO book (title, description, publication_date, author_id, create_at, update_at) 
                        VALUES " . implode(',', $values);
                
                try {
                    $connection->executeStatement($sql);
                    $io->progressAdvance($batchSize);
                    $totalBooks += $batchSize;
                } catch (\Exception $e) {
                    $io->error('SQL Error: ' . $e->getMessage());
                    $io->text('Problematic SQL: ' . $sql);
                    return Command::FAILURE;
                }
            }
        }

        $io->progressFinish();
        $io->success("Generated {$totalBooks} books successfully!");

        return Command::SUCCESS;
    }

    private function getOrCreateAuthors(): array
    {
        $authors = $this->entityManager->getRepository(Author::class)->findAll();
        
        if (empty($authors)) {
            $authorData = [
                ['Stephen', 'King', new \DateTime('1947-09-21')],
                ['J.K.', 'Rowling', new \DateTime('1965-07-31')],
                ['George R.R.', 'Martin', new \DateTime('1948-09-20')]
            ];
            
            foreach ($authorData as $data) {
                $author = new Author();
                $author->setFirstName($data[0]);
                $author->setLastName($data[1]);
                $author->setBirthDate($data[2]);
                $author->setCreateAt(new \DateTimeImmutable());
                $author->setUpdateAt(new \DateTimeImmutable());
                
                $this->entityManager->persist($author);
            }
            
            $this->entityManager->flush();
            $authors = $this->entityManager->getRepository(Author::class)->findAll();
        }
        
        return $authors;
    }

    private function randomDate(string $start = '-10 years'): string
    {
        $timestamp = rand(strtotime($start), time());
        return date('Y-m-d', $timestamp);
    }
}
