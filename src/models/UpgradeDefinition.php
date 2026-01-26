<?php

namespace App\Models;

require_once __DIR__ . '/../repository/UpgradesRepository.php';

use UpgradesRepository;

class UpgradeDefinition
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public int $baseCost,
        public int $maxLevel
    ) {}

    # funkcja statyczna tworząca obiekt z tablicy asocjacyjnej
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            title: (string) ($data['title'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            baseCost: (int) ($data['base_cost'] ?? 0),
            maxLevel: (int) ($data['max_level'] ?? 0)
        );
    }

    # funkcja statyczna pobierająca wszystkie definicje ulepszeń z repozytorium
    public static function fetchAll(): array
    {
        $repository = new UpgradesRepository(); // tworzenie instancji repozytorium
        $rows = $repository->getDefinitions(); // np.['id'=>1, 'title'=>'cos', 'base_cost'=>100, 'max_level'=>5],
        return array_map(static fn(array $row): self => self::fromArray($row), $rows); // mapowanie wierszy na obiekty UpgradeDefinition
        # bierzemy tablice z rows -> przekazujemy do fromArray -> tworzymy obiekt UpgradeDefinition
    }

    # funkcja statyczna wyszukująca definicję ulepszenia po ID
    public static function findById(int $upgradeId): ?self
    {
        foreach (self::fetchAll() as $definition) {
            if ($definition->id === $upgradeId) {
                return $definition;
            }
        }

        return null;
    }

    # funkcja obliczająca koszt następnego poziomu ulepszenia
    public function nextCost(int $currentLevel): int
    {
        return $this->baseCost * ($currentLevel + 1);
    }

    # funkcja obliczająca całkowity koszt do osiągnięcia określonego poziomu(statistics)
    public function totalCostForLevel(int $level): int
    {
        if ($level <= 0) {
            return 0;
        }

        return (int) ($this->baseCost * ($level * ($level + 1) / 2));
    }
    
    # funkcja konwertująca obiekt na tablicę asocjacyjną
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'baseCost' => $this->baseCost,
            'maxLevel' => $this->maxLevel
        ];
    }
}
