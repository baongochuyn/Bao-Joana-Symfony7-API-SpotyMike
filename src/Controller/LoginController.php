<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    
    private $repository;
    private $entityManager;
    private $cache;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository =  $entityManager->getRepository(User::class);
        $this->cache = new FilesystemAdapter();

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
                
                $userEmail = str_replace('@', '', $data['Email']);
                $userKey  = "login_attempts_$userEmail";
    
                if (!$this->cache->hasItem($userKey)) {
                    // if not exist, create a new key with value 1
                    $loginControl = new RequestControl();
                    $loginControl->number = 1;
                    $loginControl->time = time() ;
                    $this->cache->save($this->cache->getItem($userKey)->set($loginControl)->expiresAfter(300));
                    //dd($this->cache->getItem($userKey)->get());
                }else{
                    $cacheItem = $this->cache->getItem($userKey);
                    $currentLogin = $cacheItem->get();
    
                    //dd($this->cache->getItem($userKey)->get());
                    if ($currentLogin->number >= 5 && time() - $currentLogin->time < 300) {
                        $waitTime = ceil((300 - (time() - $currentLogin->time)) / 60);
                        //dd($waitTime);
                        return $this->json([
                            'error'=>true,
                            'message'=> "Trop de tentatives de connexion (5max). Veuillez réessayer ulterieurement - $waitTime min d'attente.",
                        ],429);
                    }
                    $currentLogin->number++;
                    $currentLogin->time = time();
                    $cacheItem->set($currentLogin);
                    $cacheItem->expiresAfter(300);
                    $this->cache->save($cacheItem);
                }
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
                }
            }else{
                return $this->json([
                    'error'=>true,
                    'message'=> "Le compte n'est plus actif ou est suspendu.",
                ],403);
            }
        }
        return $this->json([
            'error'=>true,
            'message'=> "Email/Password incorrect",
        ],400);
        
    }
}
