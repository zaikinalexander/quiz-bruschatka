<?php

namespace App\Controller\Web;

use App\Controller\BaseAbstractController;
use App\Service\QuizConfigStorage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizController extends BaseAbstractController
{
    #[Route('/', name: 'quiz_home', methods: ['GET'])]
    public function index(QuizConfigStorage $quizConfigStorage): Response
    {
        $config = $quizConfigStorage->getConfig();

        return $this->render('pages/quiz/index.html.twig', [
            'config' => $config,
            'slides' => $config['slides'] ?? [],
        ]);
    }
}

