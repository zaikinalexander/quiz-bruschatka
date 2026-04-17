<?php

namespace App\Controller\Api;

use App\Controller\BaseAbstractController;
use App\Service\LeadStorage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LeadListApiController extends BaseAbstractController
{
    #[Route('/api/leads', methods: ['GET'])]
    public function list(LeadStorage $leadStorage): Response
    {
        return $this->success([
            'leads' => $leadStorage->getLeads(),
        ]);
    }
}
