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

    public static function fetchAll(): array
    {
        $repository = new UpgradesRepository();
        $rows = $repository->getDefinitions();
        return array_map(static fn(array $row): self => self::fromArray($row), $rows);
    }

    public static function findById(int $upgradeId): ?self
    {
        foreach (self::fetchAll() as $definition) {
            if ($definition->id === $upgradeId) {
                return $definition;
            }
        }

        return null;
    }

    public function nextCost(int $currentLevel): int
    {
        return $this->baseCost * ($currentLevel + 1);
    }

    public function totalCostForLevel(int $level): int
    {
        if ($level <= 0) {
            return 0;
        }

        return (int) ($this->baseCost * ($level * ($level + 1) / 2));
    }

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
