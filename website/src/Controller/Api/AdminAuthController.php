<?php

namespace App\Controller\Api;

use App\Controller\BaseAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminAuthController extends BaseAbstractController
{
    #[Route('/api/admin/me', methods: ['GET'])]
    public function me(Request $request): Response
    {
        return $this->success([
            'authenticated' => $request->getSession()->get('admin_authenticated') === true,
        ]);
    }

    #[Route('/api/admin/login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->failed(['auth' => 'Введите логин и пароль.'], code: 400);
        }

        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $expectedUsername = $this->getAdminUsername();
        $expectedPassword = $this->getAdminPassword();

        if ($expectedUsername === '' || $expectedPassword === '') {
            return $this->failed(['auth' => 'Доступ в админку не настроен.'], code: 500);
        }

        if (!hash_equals($expectedUsername, $username) || !hash_equals($expectedPassword, $password)) {
            return $this->failed(['auth' => 'Неверный логин или пароль.'], code: 401);
        }

        $session = $request->getSession();
        $session->migrate(true);
        $session->set('admin_authenticated', true);

        return $this->success([
            'authenticated' => true,
        ]);
    }

    #[Route('/api/admin/logout', methods: ['POST'])]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();

        return $this->success([
            'authenticated' => false,
        ]);
    }

    private function getAdminUsername(): string
    {
        return trim((string) ($_SERVER['ADMIN_USERNAME'] ?? $_ENV['ADMIN_USERNAME'] ?? getenv('ADMIN_USERNAME') ?: ''));
    }

    private function getAdminPassword(): string
    {
        return (string) ($_SERVER['ADMIN_PASSWORD'] ?? $_ENV['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?: '');
    }
}
