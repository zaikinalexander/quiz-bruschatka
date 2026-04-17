<?php

namespace App\Controller\Api;

use App\Controller\BaseAbstractController;
use App\Service\QuizConfigStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizConfigApiController extends BaseAbstractController
{
    #[Route('/api/quiz-config', methods: ['GET'])]
    public function config(QuizConfigStorage $quizConfigStorage): Response
    {
        return $this->success($quizConfigStorage->getConfig());
    }

    #[Route('/api/quiz-config', methods: ['POST'])]
    public function saveConfig(Request $request, QuizConfigStorage $quizConfigStorage): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->failed(['config' => 'Invalid config payload.']);
        }

        return $this->success($quizConfigStorage->saveConfig($payload));
    }

    #[Route('/api/slides', methods: ['GET'])]
    public function slides(QuizConfigStorage $quizConfigStorage): Response
    {
        $config = $quizConfigStorage->getConfig();
        $desktopSlides = array_map(
            static fn (array $slide): array => array_merge(['flow' => 'desktop'], $slide),
            $config['slides'] ?? [],
        );
        $mobileSlides = array_map(
            static fn (array $slide): array => array_merge(['flow' => 'mobile'], $slide),
            $config['mobileSlides'] ?? [],
        );

        return $this->success([
            'slides' => array_merge($desktopSlides, $mobileSlides),
        ]);
    }

    #[Route('/api/slide/{id}', methods: ['GET'])]
    public function slide(string $id, QuizConfigStorage $quizConfigStorage): Response
    {
        $slide = $quizConfigStorage->getSlide($id);

        if (!$slide) {
            return $this->failed(['slide' => 'Slide not found.'], code: 404);
        }

        return $this->success([
            'slide' => $slide,
        ]);
    }

    #[Route('/api/slide/{id}', methods: ['POST'])]
    public function saveSlide(string $id, Request $request, QuizConfigStorage $quizConfigStorage): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->failed(['slide' => 'Invalid slide payload.']);
        }

        $config = $quizConfigStorage->saveSlide($id, $payload);

        return $this->success($config);
    }

    #[Route('/api/quiz-settings', methods: ['GET'])]
    public function settings(QuizConfigStorage $quizConfigStorage): Response
    {
        $config = $quizConfigStorage->getConfig();

        return $this->success([
            'general' => $config['general'] ?? [],
        ]);
    }

    #[Route('/api/quiz-settings', methods: ['POST'])]
    public function saveSettings(Request $request, QuizConfigStorage $quizConfigStorage): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->failed(['general' => 'Invalid settings payload.']);
        }

        if (array_key_exists('leadEmails', $payload)) {
            $payload['leadEmails'] = $this->normalizeLeadEmails($payload['leadEmails']);
        }

        $config = $quizConfigStorage->saveGeneral($payload);

        return $this->success($config);
    }

    /**
     * @param mixed $leadEmails
     *
     * @return string[]
     */
    private function normalizeLeadEmails(mixed $leadEmails): array
    {
        if (is_array($leadEmails)) {
            $items = $leadEmails;
        } else {
            $items = preg_split('/[\n,;]+/', (string) $leadEmails) ?: [];
        }

        $emails = [];

        foreach ($items as $item) {
            $email = trim((string) $item);

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $emails[] = mb_strtolower($email);
        }

        return array_values(array_unique($emails));
    }
}
