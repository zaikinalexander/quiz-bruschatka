<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminAuthSubscriber implements EventSubscriberInterface
{
    private const PUBLIC_API_PATHS = [
        '/api/lead',
        '/api/admin/me',
        '/api/admin/login',
    ];

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!$this->requiresAdminAuth($path)) {
            return;
        }

        if ($request->getSession()->get('admin_authenticated') === true) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'success' => false,
            'errors' => [
                'auth' => 'Требуется вход в админку.',
            ],
        ], 401));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 12],
        ];
    }

    private function requiresAdminAuth(string $path): bool
    {
        if (!str_starts_with($path, '/api/')) {
            return false;
        }

        return !in_array($path, self::PUBLIC_API_PATHS, true);
    }
}
