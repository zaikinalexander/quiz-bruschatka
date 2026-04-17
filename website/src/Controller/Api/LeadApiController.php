<?php

namespace App\Controller\Api;

use App\Controller\BaseAbstractController;
use App\Service\LeadStorage;
use App\Service\QuizConfigStorage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class LeadApiController extends BaseAbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly QuizConfigStorage $quizConfigStorage,
        private readonly LeadStorage $leadStorage,
        private readonly string $quizLeadTo,
        private readonly string $quizMailFrom,
    ) {
    }

    #[Route('/api/lead', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->failed(['payload' => 'Invalid lead payload.']);
        }

        $phone = preg_replace('/\D+/', '', (string) ($payload['phone'] ?? ''));
        $flow = trim((string) ($payload['flow'] ?? 'desktop'));
        $answers = $this->normalizeAnswers($payload['answers'] ?? []);
        $pageUrl = trim((string) ($payload['pageUrl'] ?? ''));

        if (strlen($phone) !== 11) {
            return $this->failed(['phone' => 'Введите корректный телефон.']);
        }

        if ($answers === []) {
            return $this->failed(['quiz' => 'Не хватает ответов квиза.']);
        }

        $recipients = $this->resolveRecipients();

        if ($recipients === []) {
            return $this->failed(['mail' => 'Не настроены адреса для уведомлений.'], code: 500);
        }

        try {
            $lead = $this->leadStorage->createLead([
                'phone' => $phone,
                'flow' => $flow,
                'answers' => $answers,
                'pageUrl' => $pageUrl,
            ]);
        } catch (RuntimeException $exception) {
            return $this->failed(
                ['storage' => 'Не удалось сохранить заявку.'],
                message: $exception->getMessage(),
                code: 500,
            );
        }

        $subject = sprintf('Заявка #%d из квиза BRUSCHATKA.RU: %s', $lead['number'], $phone);
        $submittedAt = (new \DateTimeImmutable((string) $lead['createdAt']))->format('d.m.Y H:i:s');

        $textBody = implode(PHP_EOL, [
            'Новая заявка из квиза BRUSCHATKA.RU',
            '',
            sprintf('Номер заявки: #%d', $lead['number']),
            sprintf('Телефон: %s', $phone),
            ...array_map(
                static fn (array $answer): string => sprintf('%s: %s', $answer['title'], $answer['label']),
                $answers,
            ),
            sprintf('Страница: %s', $pageUrl !== '' ? $pageUrl : '-'),
            sprintf('Дата и время: %s', $submittedAt),
        ]);

        $answerRows = implode('', array_map(
            static fn (array $answer): string => sprintf(
                '<p><strong>%s:</strong> %s</p>',
                htmlspecialchars($answer['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($answer['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ),
            $answers,
        ));

        $htmlBody = sprintf(
            '<h2>Новая заявка из квиза BRUSCHATKA.RU</h2>
            <p><strong>Номер заявки:</strong> #%d</p>
            <p><strong>Телефон:</strong> %s</p>
            %s
            <p><strong>Страница:</strong> %s</p>
            <p><strong>Дата и время:</strong> %s</p>',
            $lead['number'],
            htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $answerRows,
            htmlspecialchars($pageUrl !== '' ? $pageUrl : '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($submittedAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );

        $email = (new Email())
            ->from(new Address($this->quizMailFrom, 'Quiz BRUSCHATKA.RU'))
            ->to(...$recipients)
            ->subject($subject)
            ->text($textBody)
            ->html($htmlBody);

        try {
            $this->mailer->send($email);
            $this->leadStorage->updateLead((int) $lead['number'], [
                'mailStatus' => 'sent',
                'mailError' => null,
            ]);
        } catch (TransportExceptionInterface|\Throwable $exception) {
            $this->leadStorage->updateLead((int) $lead['number'], [
                'mailStatus' => 'failed',
                'mailError' => $exception->getMessage(),
            ]);

            return $this->failed(
                ['mail' => 'Не удалось отправить заявку.'],
                message: $exception->getMessage(),
                code: 500,
            );
        }

        return $this->success([
            'number' => $lead['number'],
            'phone' => $phone,
        ]);
    }

    /**
     * @return Address[]
     */
    private function resolveRecipients(): array
    {
        $config = $this->quizConfigStorage->getConfig();
        $leadEmails = $config['general']['leadEmails'] ?? $this->quizLeadTo;

        if (is_array($leadEmails)) {
            $items = $leadEmails;
        } else {
            $items = preg_split('/[\n,;]+/', (string) $leadEmails) ?: [];
        }

        $recipients = [];

        foreach ($items as $item) {
            $email = trim((string) $item);

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $recipients[] = new Address($email);
        }

        return $recipients;
    }

    private function normalizeAnswers(mixed $rawAnswers): array
    {
        if (!is_array($rawAnswers)) {
            return [];
        }

        $answers = [];

        foreach ($rawAnswers as $rawAnswer) {
            if (!is_array($rawAnswer)) {
                continue;
            }

            $field = trim((string) ($rawAnswer['field'] ?? ''));
            $value = trim((string) ($rawAnswer['value'] ?? ''));
            $title = trim((string) ($rawAnswer['title'] ?? ''));

            if ($field === '' || $value === '') {
                continue;
            }

            $answers[] = [
                'field' => $field,
                'value' => $value,
                'title' => $title !== '' ? $title : ($this->quizConfigStorage->resolveSlideTitle($field) ?? $field),
                'label' => $this->quizConfigStorage->resolveOptionLabel($field, $value) ?? trim((string) ($rawAnswer['label'] ?? $value)),
            ];
        }

        return $answers;
    }
}
