<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\User;
use Doctrine\Migrations\Query\Query;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
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
    private $cache;
    private $artistRepository;

    public function __construct(EntityManagerInterface $entityManager, TokenVerifierService $tokenVerifier)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->tokenVerifier = $tokenVerifier;
        $this->cache = new FilesystemAdapter();
        $this->artistRepository = $entityManager->getRepository(Artist::class);
    }

    #[Route('/user', name: 'app_user',methods:['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->repository->findAll();
        $resultat = [];
        try {
            if (count($users) > 0) {
                foreach ($users as $user) {
                    array_push($resultat, $user->serializer());
                }
            }
        } catch (Exception $e) {
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
        dd("ok");
        $user = $this->repository->findOneBy(["id" => $id]);
        if (!$user) {
            return $this->json([
                'message' => 'user not found !!! ',
                'path' => 'src/Controller/UserController.php',
            ]);
        }
        return $this->json([
            'user' => $user->serializer(),
            'message' => 'welcome !!! ',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/register', name: 'app_create_user',methods:['POST'])]
    public function CreateUser(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $requestData = $request->request->all();
        $user = new User();

        try {
            if (!isset($requestData['firstname'])
                && !isset($requestData['lastname'])
                && !isset($requestData['email'])
                && !isset($requestData['dateBirth'])
                && !isset($requestData['password'])) {
                return $this->json([
                    'error' => true,
                    'message' => "Des champs obligatoires sont manquants",
                ], 400);
            } else {
                $user->setIdUser("User_".rand(0,999999999999));
                // Validation du firstname
                $firstname = $requestData['firstname'];
                if (strlen($firstname) < 1 || strlen($firstname) > 60) {
                    return $this->json([
                        'error' => true,
                        'message' => "La longueur du prénom doit être comprise entre 1 et 60 caractères.",
                    ], 400);
                }
                $user->setFirstname($firstname);

                // Validation du lastname
                $lastname = $requestData['lastname'];
                if (strlen($lastname) < 1 || strlen($lastname) > 60) {
                    return $this->json([
                        'error' => true,
                        'message' => "La longueur du nom doit être comprise entre 1 et 60 caractères.",
                    ], 400);
                }
                $user->setLastname($lastname);

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
                    
                    $diff = date_diff(date_create_from_format('d/m/Y',$requestData['dateBirth']), 
                    date_create_from_format('d/m/Y', date("d/m/Y")));
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
                        'message'=> "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum.",
                    ],403);
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
                if(preg_match('/^[0-9]{10}+$/', $requestData['tel'])){
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
        // check authen 401
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware),401);
        }

        $user = $dataMiddellware;
        $requestData = $request->request->all();

        if(count($requestData) <= 0){
            return $this->json([
                'error'=>true,
                'message'=> "Les données fournies sont invalides ou incomplètes."
            ],400);
        }
        
        $arrParam = array("firstname", "lastname", "tel", "sexe");
        foreach ($requestData as $key => $value){
            if (!in_array($key, $arrParam)){
                return $this->json([
                    'error'=>true,
                    'message'=> "Les données fournies sont invalides ou incomplètes."
                ],400);
            }
        };

        try{
            if (isset($requestData)) {
                if(isset($requestData['firstname'])){
                    if (strlen($requestData['firstname']) < 1 || strlen($requestData['firstname']) > 60) {
                        return $this->json([
                            'error'=>true,
                            'message'=> "Erreur de validation des données.",
                        ],422);
                    } 
                    $user->setFirstname($requestData['firstname']);
                }
                if(isset($requestData['lastname'])){
                    if (strlen($requestData['lastname']) < 1 || strlen($requestData['lastname']) > 60) {
                        return $this->json([
                            'error'=>true,
                            'message'=> "Le format du prénom est invalide",
                        ],400);
                    } 
                    $user->setLastname($requestData['lastname']); 
                }
                if(isset($requestData['tel'])){
                    if(preg_match('/^[0-9]{10}+$/', $requestData['tel'])){
                        $dataUser = $this->repository->findOneBy(["tel"=>$requestData['tel']]);
                        if($dataUser){
                            return $this->json([
                                'error'=>true,
                                'message'=> "Conflit de données. Le numéro de téléphone est déjà utilisé par un autre utilisateur.",
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
                $user->setUpdateAt(new \DateTimeImmutable());

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
                'message'=> "Les données fournies sont invalides ou incomplètes."
            ],409);
        }
    }

    #[Route('/account-deactivation', name: 'app_desactive_user', methods: ['DELETE'])]
    public function desactiveUser(Request $request): JsonResponse
    {
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware),401);
        }
        $user = $dataMiddellware;
        
        if(!$user->getActive()){
            return $this->json([
                'error' => true,
                'message' => 'Le compte est déjà désactivé.', 
            ],409);
        }
        $user->setActive(false);
        
        //supprimer artiste
        $artist = $this->artistRepository->findOneBy(['User_idUser'=> $user->getId()]);
        if($artist){
            //$this->entityManager->remove($artist);
            $artist->setActive(false);
        }
        
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Votre compte a été désactivé avec succès. Nous sommes désolés de vous voir partir.',
        ]);
    }

    #[Route('/password-lost', name: 'password_lost', methods: ['POST'])]
    public function passwordLost(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $requestData = $request->request->all();
        if(!isset($requestData['email'])){
            return $this->json([
                'error'=>true,
                'message'=> "Email manquant. Veuillez fournir votre email pour la récupération du mot de passe.",
            ],400);
        }
        if(!filter_var($requestData['email'], FILTER_VALIDATE_EMAIL)){
            return $this->json([
                'error'=>true,
                'message'=> "Le format de l'email est invalide. Veuillez entrer un email valide.",
            ],400);
        }
        $dataUser = $this->repository->findOneBy(["email"=>$requestData['email']]);
        if(!$dataUser){
            return $this->json([
                'error'=>true,
                'message'=> "Aucun compte n'est associé à cet email. Veuillez vérifier et réessayer.",
            ],404);
        }

        $userEmail = str_replace('@', '', $requestData['email']);
        $userKey  = "password_attempts_$userEmail";
        //dd($this->cache->getItem($userKey)->get());
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

            
            if ($currentLogin->number >= 3 && time() - $currentLogin->time < 300) {
                $waitTime = ceil((300 - (time() - $currentLogin->time)) / 60);
                //dd($waitTime);
                return $this->json([
                    'error'=>true,
                    'message'=> "Trop de demandes de réinitialisation de mot de passe (3 max). Veuillez attendre avant de réessayer ( dans $waitTime min).",
                ],429);
            }
            $currentLogin->number++;
            $currentLogin->time = time();
            $cacheItem->set($currentLogin)->expiresAfter(300);
            $this->cache->save($cacheItem);
        }
        $currentDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $expiration = clone $currentDateTime;
        $expiration->modify('+2 minutes');
        $token = $JWTManager->create($dataUser, ['exp' => $expiration->getTimestamp()]);
        return $this->json([
            'success'=>true,
            'token'=> $token,
            'message' => "Un email de réinitialisation de mot de passe a été envoyé à votre address email. Veuillez suivre les instructions contenues dans l'email pour réinitialiser votre mot de passe."
        ]);
    }

    #[Route('/reset-password/{token}', name: 'reset-password', methods: ['POST'])]
    public function resetPassword(Request $request,string $token,UserPasswordHasherInterface $passwordHash): JsonResponse
    {
         //check token
         
         if($token){
             $dataMiddellware = $this->tokenVerifier->checkTokenWithParam($token);
             
             $tokenExpiration = $this->tokenVerifier->isTokenExpired($token);
             
            if(gettype($tokenExpiration) == 'boolean' && $tokenExpiration){
                return $this->json([
                    'error' => true,
                    'message' => "Votre token de réinitialisation de mot de passe a expiré. Veuillez refaire une demande de réinitialisation de mot de passe."
                ], 410); 
            }
            if(!$dataMiddellware || (gettype($tokenExpiration) == 'boolean' && $tokenExpiration)){
                return $this->json([
                    'error'=>true,
                    'message' => "Token de réinitialisation manquant ou invalide. Veuillez utiliser le lien fourni dans l'email de réinilisation de mot de passe."
                ],400);
            }
         }
         
        $user = $dataMiddellware;
        $requestData = $request->query->all();
        if(!isset($requestData['password'])){
            return $this->json([
                'error'=>true,
                'message'=> "Veuillez fournir un nouveau mot de passe.",
            ],400);
        }
        //check password
        if(!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!\s).{8,16}$/", $requestData['password']) || strlen($requestData['password']) < 8){
            return $this->json([
                'error'=>true,
                'message'=> "Le nouveau mot de passe ne respecte pas les critère requi. Il doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et être composé d'au moins 8 caractères.",
            ],400);
        }
        
        
        $hash = $passwordHash->hashPassword($user, $requestData['password']);
        $user->setPassword($hash);
        $user->setUpdateAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json([
            'success'=>true,
            'message' => "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe."
        ],200);
    }
}