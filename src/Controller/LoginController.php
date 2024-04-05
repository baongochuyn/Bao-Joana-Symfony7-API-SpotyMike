<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
    public function login(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
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
                    'message'=> "Le format de l'email est invalide",
                ],400);
            }
            if(!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!\s).{8,16}$/", $data['Password']) || strlen($data['Password']) < 8){
                return $this->json([
                    'error'=>true,
                    'message'=> "Le mot de pass doit contenir au moins une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum",
                ],400);
            }
            $user = $this->repository->findOneBy(["email"=>$data['Email']]);
            if($user){
                if($user->getActive()){
                    if(password_verify($data['Password'],$user->getPassword())){
                        return $this->json([
                            'error'=>false,
                            'message'=> "L'utilisateur a été authentifié succès ",
                            'user' => $user->serializer(),
                            'token'=> $JWTManager->create($user)
                        ]);
                    } 
                }else{
                    return $this->json([
                        'error'=>true,
                        'message'=> "Le compte n'est plus actif ou est suspendu.",
                    ],403);
                }
                
            }
        }
        return $this->json([
            'error'=>true,
            'message'=> "Email/Password incorrect",
        ],400);
        
    }
}
