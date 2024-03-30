<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
        $users = $this->repository->findAll();
        $resultat = [];
        try{
            if(count($users)>0){
                foreach($users as $user){
                   array_push($resultat,$user->serializer()) ;
                }
            }
        }catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
        return $this->json([
            'user' => $resultat,
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
            'user'=>$user->serializer(),
            'message' => 'welcome !!! ',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/register', name: 'app_create_user',methods:['POST'])]
    public function CreateUser(Request $request,UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $requestData = $request->request->all();
        $user = new User();
        $invalidValue = [];

        try{
            if(!isset($requestData['firstname'])
        && !isset($requestData['lastname'])
        && !isset($requestData['email'])
        && !isset($requestData['dateBirth'])
        && !isset($requestData['password']))
        {
            return $this->json([
                'error'=>true,
                'message'=> "Une ou plusiers donnees obligatoires sont manquantes",
            ],400);
        }
        else
        {
            $user->setIdUser("User_".rand(0,999999999999));

            $pattern = "/^[a-zA-Z\-']+$/";
            preg_match($pattern, $requestData['firstname']) ? $user->setFirstname($requestData['firstname']): array_push($invalidValue,$requestData['firstname']);
            
            preg_match($pattern, $requestData['lastname']) ? $user->setLastname($requestData['lastname']) : array_push($invalidValue,$requestData['lastname']);
            
            filter_var($requestData['email'], FILTER_VALIDATE_EMAIL) ? $user->setEmail($requestData['email']) : array_push($invalidValue,$requestData['email']);
            
            //check birthday
            $diff = date_diff(date_create($requestData['dateBirth']), date_create(date("Y-m-d")));
            if($diff->format('%y') > 12){
                $user->setDateBirth(new \DateTimeImmutable($requestData['dateBirth']));
            }else{
                return $this->json([
                    'error'=>true,
                    'message'=> "L'age de l'utilisateur ne permet pas (12 ans)",
                ],406);
            }
            $hash = $passwordHash->hashPassword($user, $requestData['password']);
            $user->setPassword($hash);

            $user->setCreateAt(new \DateTimeImmutable());
            $user->setUpdateAt(new \DateTimeImmutable());
        }
        
        if(isset($requestData['sexe'])){
            $requestData['sexe'] == "F" || $requestData['sexe'] == "M" ?
            $user->setSexe($requestData['sexe']) :
            array_push($invalidValue,$requestData['sexe']);
        }
        if(isset($requestData['tel'])){
            preg_match("/^[0-9]{3} [0-9]{4} [0-9]{4}$/", $requestData['tel']) ?
            $user->setTel($requestData['tel']) :
            array_push($invalidValue,$requestData['tel']);
        }
       
        if(count($invalidValue) > 0){
            return $this->json([
                'error'=>true,
                'message'=> "Une ou plusieurs donnees sont erronees",
                'data'=>$invalidValue
            ],409);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        }catch (Exception $e) {
            return $this->json([
                'error'=>true,
                'message'=> "Un compte utilisant cette adresse email est deja enregistre"
            ],409);
        }

        return $this->json([
            'error'=>false,
            'message'=> "L'utilisateur a bien été créé avec succes",
            'user'=>$user->serializer()
        ],201);
    }

    #[Route('/user/update/{id}', name: 'app_update_user',methods:['POST'])]
    public function UpdateUser(Request $request, int $id): JsonResponse
    {
        //$requestData = json_decode($request->getContent(), true);
        $requestData = $request->request->all();
        $user = $this->repository->findOneBy(['id'=> $id]);
        if(!$user){
            return $this->json([
                'message' => 'user not found !!! ',
                'path' => 'src/Controller/UserController.php',
            ]);
        }

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