<?php

require_once 'Repository.php';
class CardsRepository extends Repository
{
    public function getCardsByTitle(string $searchString)
    {
        $searchString = '%' . strtolower($searchString) . '%';

        $stmt = $this->database->connect()->prepare('
        SELECT * FROM cards
        WHERE LOWER(title) LIKE :search OR LOWER(description) LIKE :search
    ');
        $stmt->bindParam(':search', $searchString, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getCards(): array
    {
        $result = [];

        $stmt = $this->database->connect()->prepare('
        SELECT * FROM cards
    ');
        $stmt->execute();

        // Pobieramy surowe dane
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Przepakowujemy je na obiekty
        foreach ($cards as $card) {
            $result[] = new Card(
                $card['title'],
                $card['description'],
                $card['image'],
                $card['id'] // Zakładam, że w konstruktorze Card id jest ostatnie (jak w Twoim getCard)
            );
        }

        // Zwracamy tablicę OBIEKTÓW
        return $result;
    }
    public function getCard(int $id): ?Card
    {
        $stmt = $this->database->connect()->prepare('
        SELECT * FROM cards WHERE id = :id
    ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Card(
            $row['title'],
            $row['description'],
            $row['image'],
            $row['id']
        );
    }


}