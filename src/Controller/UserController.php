<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository =  $entityManager->getRepository(User::class);
    }

    #[Route('/user', name: 'app_user',methods:['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->repository->findAll();
        return $this->json([
            'user'=>json_encode($user),
            'message' => 'welcome !!! ',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/user/{id}', name: 'app_get_user',methods:['GET'])]
    public function GetUserById(int $id): JsonResponse
    {
        $user = $this->repository->findOneBy(["id"=>$id]);
        if (!$user) {
            return $this->json([
                'message' => 'user not found !!! ',
                'path' => 'src/Controller/UserController.php',
            ]);
        }
        return $this->json([
            'user'=>json_encode($user),
            'message' => 'welcome !!! ',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/user/create', name: 'app_create_user',methods:['POST'])]
    public function CreateUser(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $user = new User();
        if(isset($requestData['idUser'])){
            $user->setIdUser($requestData['idUser']);
        }
        if(isset($requestData['name'])){
            $user->setName($requestData['name']);
        }
        if(isset($requestData['email'])){
            $user->setEmail($requestData['email']);
        }
        if(isset($requestData['encrypte'])){
            $user->setEncrypte($requestData['encrypte']);
        }
        if(isset($requestData['tel'])){
            $user->setTel($requestData['tel']);
        }
        if(isset($requestData['createAt'])){
            $user->setCreateAt(new \DateTimeImmutable($requestData['createAt']));
        }
        if(isset($requestData['updateAt'])){
            $user->setUpdateAt(new \DateTimeImmutable($requestData['updateAt']));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'request'=> $requestData,
            'message' => 'created !!! ',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/user/update/{id}', name: 'app_update_user',methods:['POST'])]
    public function UpdateUser(Request $request, int $id): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);
        
        $user = $this->repository->findOneBy(['id'=> $id]);
        if(!$user){
            return $this->json([
                'message' => 'user not found !!! ',
                'path' => 'src/Controller/UserController.php',
            ]);
        }
        if($user){
            if (isset($requestData)) {
                if(isset($requestData['idUser'])){
                    $user->setIdUser($requestData['idUser']);
                }
                if(isset($requestData['name'])){
                    $user->setName($requestData['name']);
                }
                if(isset($requestData['email'])){
                    $user->setEmail($requestData['email']);
                }
                if(isset($requestData['encrypte'])){
                    $user->setEncrypte($requestData['encrypte']);
                }
                if(isset($requestData['tel'])){
                    $user->setTel($requestData['tel']);
                }
                if(isset($requestData['updateAt'])){
                    $user->setUpdateAt(new \DateTimeImmutable($requestData['updateAt']));
                }
                $this->entityManager->flush();
                return $this->json([
                    'user'=>json_encode($user),
                    'message' => 'updated !!! ',
                    'path' => 'src/Controller/UserController.php',
                ]);
            } 
        }

        return $this->json([
            'message' => 'cannot update !!! ',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/user/delete/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $user = $this->repository->find($id);

        if (!$user) {
            if (!$user) {
                return $this->json([
                    'message' => 'user not found !!! ',
                    'path' => 'src/Controller/UserController.php',
                ]);
            }
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'deleted !!! ',
            'path' => 'src/Controller/UserController.php',
        ]);
    }
    
}