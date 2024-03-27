<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    
    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository =  $entityManager->getRepository(User::class);
    }

    #[Route('/login', name: 'app_login', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'welcome !!! ',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }

    #[Route('/login', name: 'app_login_post', methods:['POST', 'PUT'])]
    public function login(Request $request): JsonResponse
    {
        $user = $this->repository->findOneBy(["email"=>"bao@gmail.com"]);
        return $this->json([
            'user'=>json_encode($user),
            'data' => $request->getContent(),
            'message' => 'welcome !!! ',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }
}
