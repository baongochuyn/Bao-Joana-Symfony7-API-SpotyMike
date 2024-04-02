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

    // #[Route('/login', name: 'app_login', methods: ['GET'])]
    // public function index(): JsonResponse
    // {
    //     return $this->json([
    //         'message' => 'welcome !!! ',
    //         'path' => 'src/Controller/LoginController.php',
    //     ]);
    // }

    #[Route('/login', name: 'app_login_post', methods:['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = $request->request->all();

        if(!isset($data['Email']) || !isset($data['Password'])){
            return $this->json([
                'error'=>true,
                'message'=> "Email/Password manquants",
            ],400);
        }else{
            if(!filter_var($data['Email'], FILTER_VALIDATE_EMAIL)){
                return $this->json([
                    'error'=>true,
                    'message'=> "Email/Password incorrect",
                ],400);
            }
            $user = $this->repository->findOneBy(["email"=>$data['Email']]);
            if($user){
                if(password_verify($data['Password'],$user->getPassword())){
                    return $this->json([
                        'user' => $user->serializer()
                    ]);
                } 
            }
        }
        return $this->json([
            'error'=>true,
            'message'=> "Email/Password incorrect",
        ],400);
        
    }
}
