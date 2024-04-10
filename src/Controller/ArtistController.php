<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PhpParser\Builder\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArtistController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $tokenVerifier;

    public function __construct(EntityManagerInterface $entityManager,TokenVerifierService $tokenVerifier)
    {
        $this->entityManager = $entityManager;
        $this->repository =  $entityManager->getRepository(Artist::class);
        $this->tokenVerifier = $tokenVerifier;
    }

    #[Route('/artist', name: 'app_create_artist', methods:['POST'])]
    public function CreateArtist(Request $request,JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $requestData = $request->request->all();

        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }
        $user = $dataMiddellware;

        $artist = $this->repository->findOneBy(['User_idUser' => $user->getId()]);
        if($artist){
            return $this->json([
                'error'=>true,
                'message'=> "Un utilisateur ne peut gérer qu'un seul compte artist. Veuillez supprimer le compte existant pour en créer un nouveau.",
            ],403);
        }
        $now = new DateTimeImmutable();
        $age = $user->getDateBirth()->diff($now)->y;
        if($age < 16){
            return $this->json([
                'error'=>true,
                'message'=> "Vous devez au moins 16 ans pour être artiste.",
            ],403);
        }
        
        $artist = new Artist;
        if(isset($requestData['label']) && isset($requestData['fullname'])){
            if($requestData['fullname'] == "" ){
                return $this->json([
                    'error'=>true,
                    'message'=> "Le format du fullname est invalide."
                ],400);
            }
            if($this->repository->findOneBy(['fullname'=>$requestData['fullname']])){
                return $this->json([
                    'error'=>true,
                    'message'=> "Ce nom d'artiste a déjà pris. Veuillez en choisir un autre."
                ],409);
            }
            if(preg_match('/[^a-zA-Z0-9_-]/', $requestData['label']) || $requestData['label'] == "" ){
                return $this->json([
                    'error'=>true,
                    'message'=> "Le format de l'id du label est invalide."
                ],400);
            }
            if(isset($requestData['description'])){
                $artist->setDescription($requestData['description']);
            }
            $artist->setLabel($requestData['label']);
            $artist->setFullname($requestData['fullname']);
            $artist->setDateBegin(new DateTimeImmutable());
            $artist->setUserIdUser($user);
            $artist->setActive(true);

            $this->entityManager->persist($artist);
            $this->entityManager->flush();
            return $this->json([
                'success'=>true,
                'message'=> "Votre compte d'artiste a été créé avec succès. Bienvenue dans notre communauté d'artistes.",
                'artiste_id'=> $artist->getId()
            ],201);

            
        }else{
            return $this->json([
                'error'=>true,
                'message'=> "L'id du label et le fullname sont obligatoires.",
            ],400);
        }
    }

    #[Route('/artist', name: 'app_get_artist', methods:['GET'])]
    public function getArtist(Request $request,JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $requestData = $request->request->all();

        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }
        $user = $dataMiddellware;
        
        // SELECT *
        // FROM user
        // RIGHT JOIN artist ON user.id = artist.user_id_user_id
        $query = $this->entityManager->createQueryBuilder()
        ->select('a', 'u')
        ->from(Artist::class, 'a')
        ->leftJoin('a.User_idUser', 'u')
        ->where('a.active =1');

        $result = $query->getQuery()->getResult();
        dd($result);
        $serializedArtists = [];
        foreach($result as $a){
          
           $serializedArtist = $a->serializer();
        }
        // dd($serializedArtists);

        return $this->json([
            'error'=>false,
            'artists'=> $result,
            'message'=>"Informations des artistes récupérées avec succès."

        ]);
    }

    // #[Route('/artist', name: 'app_update_artist', methods:['POST'])]
    // public function UpdateArtist(Request $request,JWTTokenManagerInterface $JWTManager): JsonResponse
    // {
    //     $requestData = $request->request->all();

    //     $dataMiddellware = $this->tokenVerifier->checkToken($request);
    //     if(gettype($dataMiddellware) == 'boolean'){
    //         return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
    //     }
    //     $user = $dataMiddellware;

    //     $artist = $this->repository->findOneBy(['User_idUser' => $user->getId()]);
    //     if(!$artist){
    //         return $this->json([
    //             'error'=>true,
    //             'message'=> "Artiste non trouvé. Veuillez vérifier des informations fournies.",
    //         ],404);
    //     }
    //     $invalide = false;
    //     if(isset($requestData['label'])){
    //         if(preg_match('/[^a-zA-Z0-9_-]/', $requestData['label'] || $requestData['label'] == "" )){
    //             $invalide = true;
    //         }else{
    //             $artist->setLabel($requestData['label']);
    //             $artist->setDateBegin(new DateTimeImmutable());
    //         }
    //     }
    //     if(isset($requestData['fullname'])){
    //         if($this->repository->findOneBy(['fullname'=>$requestData['fullname']])){
    //             return $this->json([
    //                 'error'=>true,
    //                 'message'=> "Le nom d'artiste est déjà utilisé. Veuillez choisir un autre nom.",
    //             ],409);
    //         }
    //         //check forma ? $invalide = true : false
    //          if($requestData['fullname'] == ""){
    //              $invalide = true;
    //          }
    //         $artist->setFullname($requestData['fullname']);
    //     }
    //     if(isset($requestData['description'])){
    //          //check forma ? $invalide = true : false
    //         $artist->setDescription($requestData['description']);
    //     }

    //     if($invalide){
    //         return $this->json([
    //             'error'=>true,
    //             'message'=> "Les paramettres fournis sont invalides. Veuillez vérifier des données soumises.",
    //         ],400);
    //     }
    //     $this->entityManager->flush();
    //     return $this->json([
    //         'success'=>true,
    //         'message'=> "Les informations de l'artiste ont été mises à jour avec succès."
    //     ],201);
    // }

    #[Route('/artist', name: 'app_artist', methods:['DELETE'])]
    public function DeleteArtist(Request $request,JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }
        $user = $dataMiddellware;
        $artist = $this->repository->findOneBy(['User_idUser' => $user->getId()]);
        if(!$artist){
            return $this->json([
                'error'=>true,
                'message'=> "Compte artiste non trouvé. Veuillez vérifier des informations fournies et réessayez.",
            ],404);
        }
        if(!$artist->getActive()){
            return $this->json([
                'error'=>true,
                'message'=> "Le compte artiste est déjà déactivé.",
            ],410);
        }
        $artist->setActive(false);
        $this->entityManager->flush();
        return $this->json([
            'success'=>true,
            'message'=> "Le compte artiste a été déactivé avec succès."
        ],201);
    }

}
