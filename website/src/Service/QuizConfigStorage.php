<?php

namespace App\Service;

use RuntimeException;

class QuizConfigStorage
{
    public function __construct(
        private readonly string $quizConfigPath,
    ) {
    }

    public function getConfig(): array
    {
        if (!is_file($this->quizConfigPath)) {
            throw new RuntimeException(sprintf('Quiz config not found: %s', $this->quizConfigPath));
        }

        $content = file_get_contents($this->quizConfigPath);
        $data = json_decode($content ?: '{}', true);

        if (!is_array($data)) {
            throw new RuntimeException('Quiz config is invalid JSON.');
        }

        return $data;
    }

    public function saveConfig(array $config): array
    {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Failed to encode quiz config.');
        }

        file_put_contents($this->quizConfigPath, $json . PHP_EOL);

        return $config;
    }

    public function getSlide(string $id): ?array
    {
        $config = $this->getConfig();

        foreach ($this->getAllSlides($config) as $slide) {
            if (($slide['id'] ?? null) === $id) {
                return $slide;
            }
        }

        return null;
    }

    public function saveSlide(string $id, array $slidePayload): array
    {
        $config = $this->getConfig();

        foreach (['slides', 'mobileSlides'] as $groupKey) {
            $slides = $config[$groupKey] ?? [];

            foreach ($slides as $index => $slide) {
                if (($slide['id'] ?? null) === $id) {
                    $slides[$index] = array_replace_recursive($slide, $slidePayload);
                    $config[$groupKey] = $slides;

                    return $this->saveConfig($config);
                }
            }
        }

        throw new RuntimeException(sprintf('Slide "%s" not found.', $id));
    }

    public function saveGeneral(array $generalPayload): array
    {
        $config = $this->getConfig();
        $config['general'] = array_replace_recursive($config['general'] ?? [], $generalPayload);

        return $this->saveConfig($config);
    }

    public function resolveOptionLabel(string $field, string $value): ?string
    {
        $config = $this->getConfig();

        foreach ($this->getAllSlides($config) as $slide) {
            if (($slide['field'] ?? null) !== $field) {
                continue;
            }

            foreach ($slide['options'] ?? [] as $option) {
                if (($option['value'] ?? null) === $value) {
                    return $option['label'] ?? $value;
                }
            }
        }

        return null;
    }

    public function resolveSlideTitle(string $field): ?string
    {
        $config = $this->getConfig();

        foreach ($this->getAllSlides($config) as $slide) {
            if (($slide['field'] ?? null) === $field) {
                return $slide['title'] ?? null;
            }
        }

        return null;
    }

    private function getAllSlides(array $config): array
    {
        return array_merge($config['slides'] ?? [], $config['mobileSlides'] ?? []);
    }
}
