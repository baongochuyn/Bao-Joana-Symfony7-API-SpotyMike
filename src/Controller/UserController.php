<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
    private $tokenVerifier;

    public function __construct(EntityManagerInterface $entityManager,TokenVerifierService $tokenVerifier)
    {
        $this->entityManager = $entityManager;
        $this->repository =  $entityManager->getRepository(User::class);
        $this->tokenVerifier = $tokenVerifier;
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
      
        try{
            if(!isset($requestData['firstname'])
                && !isset($requestData['lastname'])
                && !isset($requestData['email'])
                && !isset($requestData['dateBirth'])
                && !isset($requestData['password']))
            {
                return $this->json([
                    'error'=>true,
                    'message'=> "Des champs obligatoires sont manquantes",
                ],400);
            }
            else
            {
                $user->setIdUser("User_".rand(0,999999999999));

                $pattern = "/^[a-zA-Z\-']+$/";
                preg_match($pattern, $requestData['firstname']) ? $user->setFirstname($requestData['firstname']): array_push($invalidValue,$requestData['firstname']);
                
                preg_match($pattern, $requestData['lastname']) ? $user->setLastname($requestData['lastname']) : array_push($invalidValue,$requestData['lastname']);
                
                //check email format
                if(filter_var($requestData['email'], FILTER_VALIDATE_EMAIL)){
                    $dataUser = $this->repository->findOneBy(["email"=>$requestData['email']]);
                    if($dataUser){
                        return $this->json([
                            'error'=>true,
                            'message'=> "Cette email est déjà utilisé par un autre compte",
                        ],409);
                    }
                    $user->setEmail($requestData['email']);
                }else{
                    return $this->json([
                        'error'=>true,
                        'message'=> "Le format de l'email est invalide",
                    ],400);
                }
                
                //check birthday
                $d = \DateTime::createFromFormat('d/m/Y',$requestData['dateBirth']);
                //dd($d->format('d/m/Y') ==  $requestData['dateBirth']);
                if($d && ($d->format('d/m/Y') ==  $requestData['dateBirth'])){
                    $diff = date_diff(date_create($requestData['dateBirth']), date_create(date("Y-m-d")));
                    if($diff->format('%y') > 12){
                        $user->setDateBirth(new \DateTimeImmutable($requestData['dateBirth']));
                    }else{
                        return $this->json([
                            'error'=>true,
                            'message'=> "L'age de l'utilisateur ne permet pas (12 ans)",
                        ],406);
                    }
                }else{
                    return $this->json([
                        'error'=>true,
                        'message'=> "Le format de la date de naissance est invalide. Le format attendu est JJ/MM/AAAA.",
                    ],400);
                }
                
                //check password
                if(!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!\s).{8,16}$/", $requestData['password']) || strlen($requestData['password']) < 8){
                    return $this->json([
                        'error'=>true,
                        'message'=> "Le mot de pass doit contenir au moins une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum",
                    ],400);
                }
                $hash = $passwordHash->hashPassword($user, $requestData['password']);
                $user->setPassword($hash);

                $user->setCreateAt(new \DateTimeImmutable());
                $user->setUpdateAt(new \DateTimeImmutable());
            }
            
            if(isset($requestData['sexe'])){
                if($requestData['sexe'] == "0" || $requestData['sexe'] == "1"){
                    $user->setSexe($requestData['sexe']);
                }else{
                    return $this->json([
                        'error'=>true,
                        'message'=> "La valeur de champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme",
                    ],400);
                }
            }
            if(isset($requestData['tel'])){
                if(preg_match("/^[0-9]{3} [0-9]{4} [0-9]{4}$/", $requestData['tel'])){
                    $dataUser = $this->repository->findOneBy(["tel"=>$requestData['tel']]);
                    if($dataUser){
                        return $this->json([
                            'error'=>true,
                            'message'=> "Ce numéro de téléphone est déjà utilisé par un autre compte",
                        ],409);
                    }
                    $user->setTel($requestData['tel']);
                }else{
                    return $this->json([
                        'error'=>true,
                        'message'=> "Le format du numéro de téléphone est invalide.",
                    ],400);
                }
            }
            $user->setActive(true);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }catch (Exception $e) {
            return $this->json([
                'error'=>true,
                'message'=> $e,
                //'message'=> "Cette email est déjà par un autre compte"
            ],409);
        }

        return $this->json([
            'error'=>false,
            'message'=> "L'utilisateur a bien été créé avec succes",
            'user'=>$user->serializer()
        ],201);
    }

    #[Route('/user', name: 'app_update_user',methods:['POST'])]
    public function UpdateUser(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $requestData = $request->request->all();
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }
        $user = $dataMiddellware;
        
        if(!$user){
            return $this->json([
                'message' => 'user not found !!! ',
                
            ]);
        }
        try{
            if (isset($requestData)) {
                if(isset($requestData['firstname'])){
                    if(preg_match("/^[a-zA-Z\-']+$/", $requestData['firstname'])){
                        $user->setFirstname($requestData['firstname']);
                    } 
                }
                if(isset($requestData['lastname'])){
                    if(preg_match("/^[a-zA-Z\-']+$/", $requestData['lastname'])){
                        $user->setLastname($requestData['lastname']);
                    } 
                }
                if(isset($requestData['tel'])){
                    if(preg_match("/^[0-9]{3} [0-9]{4} [0-9]{4}$/", $requestData['tel'])){
                        $dataUser = $this->repository->findOneBy(["tel"=>$requestData['tel']]);
                        if($dataUser){
                            return $this->json([
                                'error'=>true,
                                'message'=> "Conflit de données. Le numéro de téléphone est déjà utilisé par un autre ",
                            ],409);
                        }
                        $user->setTel($requestData['tel']);
                    }else{
                        return $this->json([
                            'error'=>true,
                            'message'=> "Le format du numéro de téléphone est invalide.",
                        ],400);
                    }
                }
                if(isset($requestData['sexe'])){
                    if($requestData['sexe'] == "0" || $requestData['sexe'] == "1"){
                        $user->setSexe($requestData['sexe']);
                    }else{
                        return $this->json([
                            'error'=>true,
                            'message'=> "La valeur de champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme",
                        ],400);
                    }
                }
                if(isset($requestData['updateAt'])){
                    $user->setUpdateAt(new \DateTimeImmutable($requestData['updateAt']));
                }
                $this->entityManager->flush();
                return $this->json([
                    'error'=>false,
                    'message'=> "Votre inscription a bien été prise en compte",
                ]);
    
            }
    
        }catch (Exception $e) {
            return $this->json([
                'error'=>true,
                'message'=> $e,
                'message'=> "Les données fournies sont invalides ou incomplètes"
            ],409);
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