<?php

namespace App\Service;

use RuntimeException;

class LeadStorage
{
    public function __construct(
        private readonly string $quizLeadsPath,
    ) {
    }

    public function getLeads(): array
    {
        $data = $this->readStorage();

        $items = $data['items'] ?? [];

        usort(
            $items,
            static fn (array $left, array $right): int => ($right['number'] ?? 0) <=> ($left['number'] ?? 0),
        );

        return $items;
    }

    public function createLead(array $leadPayload): array
    {
        $data = $this->readStorage();

        $number = (int) ($data['lastNumber'] ?? 0) + 1;
        $lead = array_merge(
            [
                'number' => $number,
                'createdAt' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
                'mailStatus' => 'pending',
                'mailError' => null,
            ],
            $leadPayload,
        );

        $data['lastNumber'] = $number;
        $data['items'] ??= [];
        $data['items'][] = $lead;

        $this->writeStorage($data);

        return $lead;
    }

    public function updateLead(int $number, array $patch): ?array
    {
        $data = $this->readStorage();
        $items = $data['items'] ?? [];

        foreach ($items as $index => $lead) {
            if ((int) ($lead['number'] ?? 0) !== $number) {
                continue;
            }

            $items[$index] = array_replace($lead, $patch);
            $data['items'] = $items;
            $this->writeStorage($data);

            return $items[$index];
        }

        return null;
    }

    private function readStorage(): array
    {
        if (!is_file($this->quizLeadsPath)) {
            return [
                'lastNumber' => 0,
                'items' => [],
            ];
        }

        $content = file_get_contents($this->quizLeadsPath);
        $data = json_decode($content ?: '{}', true);

        if (!is_array($data)) {
            throw new RuntimeException('Quiz leads storage is invalid JSON.');
        }

        $data['lastNumber'] ??= 0;
        $data['items'] ??= [];

        return $data;
    }

    private function writeStorage(array $data): void
    {
        $directory = dirname($this->quizLeadsPath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Failed to create lead storage directory: %s', $directory));
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Failed to encode quiz leads storage.');
        }

        $bytes = file_put_contents($this->quizLeadsPath, $json . PHP_EOL, LOCK_EX);

        if ($bytes === false) {
            throw new RuntimeException(sprintf('Failed to write quiz leads storage: %s', $this->quizLeadsPath));
        }
    }
}
