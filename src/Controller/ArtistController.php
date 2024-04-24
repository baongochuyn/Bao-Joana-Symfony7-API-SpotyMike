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

    private function UpdateArtist(Request $request, Artist $artist, User $user): JsonResponse
    {
        $requestData = $request->request->all();
        $invalide = false;
        if(isset($requestData['label'])){
            if(preg_match('/[!@#$%^&*(),.?":{}|<>]/', $requestData['label']) || $requestData['label'] == "" ){
                $invalide = true;
            }else{
                $artist->setLabel($requestData['label']);
                $artist->setDateBegin(new DateTimeImmutable());
            }
        }
        if(isset($requestData['fullname'])){
            if($this->repository->findOneBy(['fullname'=>$requestData['fullname']])){
                return $this->json([
                    'error'=>true,
                    'message'=> "Le nom d'artiste est déjà utilisé. Veuillez choisir un autre nom.",
                ],409);
            }
            //check forma ? $invalide = true : false
             if(strlen($requestData['fullname']) < 1 || strlen($requestData['fullname']) > 30){
                 $invalide = true;
             }
            $artist->setFullname($requestData['fullname']);
        }
        if(isset($requestData['description'])){
            $artist->setDescription($requestData['description']);
        }

        if(isset($requestData['avatar'])){
            $explodeData = explode(",", $requestData['avatar']);
            if (count($explodeData) == 2) {
                $file = base64_decode($explodeData[1]);
                if($file === false){
                    return $this->json([
                        'error'=>true,
                        'message'=> "Le serveur ne peut pas décoder le contenue base64 en fichier binaire.",
                    ],422);
                }

                $tempFilePath = tempnam(sys_get_temp_dir(), 'avatar_');
                file_put_contents($tempFilePath, $file);
                $fileSize = getimagesize($tempFilePath);
                $size = ($fileSize[0]* $fileSize[1]*24)/(1024*1024*8);
                if($size < 1 || $size > 7){
                    return $this->json([
                        'error'=>true,
                        'message'=> "Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1Mb et 7Mb.",
                    ],422);
                }


                $imageInfo = getimagesizefromstring($file)['mime'];
                $parts = explode("/", $imageInfo);
                $extension = end($parts);
                if($extension != "png" && $extension != "jpeg"){
                    return $this->json([
                        'error'=>true,
                        'message'=> "Erreur sur le format du fichier qui n'est pas pris en compte.",
                    ],422);
                }
                $chemin = $this->getParameter('upload_directory') . '/' . $user->getEmail();

                $oldAvatarPath = $chemin . '/avatar.' . $extension;
                if(file_exists($oldAvatarPath)){
                    unlink($oldAvatarPath);
                }
                file_put_contents($chemin . '/avatar.'.$extension, $file);
            }
        }
        if($invalide){
            return $this->json([
                'error'=>true,
                'message'=> "Les paramettres fournis sont invalides. Veuillez vérifier des données soumises.",
            ],400);
        }

        $this->entityManager->flush();
        return $this->json([
            'success'=>true,
            'message'=> "Les informations de l'artiste ont été mises à jour avec succès."
        ],201);

    }

    #[Route('/artist', name: 'app_create_artist', methods:['POST'])]
    public function CreateArtist(Request $request): JsonResponse
    {
        $requestData = $request->request->all();

        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }
        $user = $dataMiddellware;

        $artist = $this->repository->findOneBy(['User_idUser' => $user->getId()]);
        if($artist){
            return $this->UpdateArtist($request, $artist, $user);
            // return $this->json([
            //     'error'=>true,
            //     'message'=> "Un utilisateur ne peut gérer qu'un seul compte artist. Veuillez supprimer le compte existant pour en créer un nouveau.",
            // ],403);
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
        if(!isset($requestData['label']) && !isset($requestData['fullname'])){
            return $this->json([
                'error'=>true,
                'message'=> "L'id du label et le fullname sont obligatoires.",
            ],400);
            
        }else{
            if(strlen($requestData['fullname']) < 1 || strlen($requestData['fullname']) > 30){
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
            if(preg_match('/[!@#$%^&*(),.?":{}|<>]/', $requestData['label']) || $requestData['label'] == ""){
                return $this->json([
                    'error'=>true,
                    'message'=> "Le format de l'id du label est invalide."
                ],400);
            }
            if(isset($requestData['description'])){
                $artist->setDescription($requestData['description']);
            }

            if(isset($requestData['avatar'])){
                $explodeData = explode(",", $requestData['avatar']);
                if (count($explodeData) == 2) {
                    $file = base64_decode($explodeData[1]);
                    if($file === false){
                        return $this->json([
                            'error'=>true,
                            'message'=> "Le serveur ne peut pas décoder le contenue base64 en fichier binaire.",
                        ],422);
                    }
    
                    $tempFilePath = tempnam(sys_get_temp_dir(), 'avatar_');
                    file_put_contents($tempFilePath, $file);
                    $fileSize = getimagesize($tempFilePath);
                    // dd(($fileSize[0]* $fileSize[1]*24)/(1024*1024*8));
                    $size = ($fileSize[0]* $fileSize[1]*24)/(1024*1024*8);
                    if($size < 1 || $size > 7){
                        return $this->json([
                                    'error'=>true,
                                    'message'=> "Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1Mb et 7Mb.",
                                ],422);
                    }
                    $imageInfo = getimagesizefromstring($file)['mime'];
                    $parts = explode("/", $imageInfo);
                    $extension = end($parts);
                    if($extension != "png" && $extension != "jpeg"){
                        return $this->json([
                            'error'=>true,
                            'message'=> "Erreur sur le format du fichier qui n'est pas pris en compte.",
                        ],422);
                    }
                    $chemin = $this->getParameter('upload_directory') . '/' . $user->getEmail();
                    mkdir($chemin);
                    file_put_contents($chemin . '/avatar.'.$extension, $file);
                }
            }

            $artist->setLabel($requestData['label']);
            $artist->setFullname($requestData['fullname']);
            $artist->setDateBegin(new DateTimeImmutable());
            $artist->setCreateAt(new DateTimeImmutable());
            $artist->setUserIdUser($user);
            $artist->setActive(true);

            $this->entityManager->persist($artist);
            $this->entityManager->flush();
            return $this->json([
                'success'=>true,
                'message'=> "Votre compte d'artiste a été créé avec succès. Bienvenue dans notre communauté d'artistes.",
                'artiste_id'=> $artist->getId()
            ],201);

            
        }
    }

    #[Route('/artist', name: 'app_get_artist', methods:['GET'])]
    public function getArtist(Request $request): JsonResponse
    {
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }

        $result = $this->repository->findArtists();

        $serializedData = [];
        foreach ($result as $artist) {
            $email = $artist->getUserIdUser()->getEmail();
            $chemin = $this->getParameter('upload_directory') . '/' . $email;
            $path = null;
            $avatar = null;
            if (file_exists($chemin.'/avatar.png')){
                $path = $chemin.'/avatar.png';
            }
            if (file_exists($chemin.'/avatar.jpeg')){
                $path = $chemin.'/avatar.jpeg';
            }

            if($path != null) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);  
                $avatar = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }

            $artistData = [
                'firstname' =>  $artist->getUserIdUser()->getFirstname(),
                'lastname' => $artist->getUserIdUser()->getLastname(),
                'fullname' => $artist->getFullname(),
                'avatar' => $avatar,
                'sexe' => ($artist->getUserIdUser()->getSexe() == 0) ? "Femme" : "Homme",
                'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                'createdAt' => $artist->getCreateAt()->format('Y-m-d H:i:s'),
                'albums' => []
            ];
        
            foreach ($artist->getAlbums() as $album) {
                $songsData = [];
                foreach ($album->getSongIdSong() as $song) {
                    $songsData[] = [
                        'id' => $song->getId(),
                        'title' => $song->getTitle(),
                        "cover"=>$song->getCover(),
                        "stream"=>$song->getUrl(),
                        "createAt"=> $song->getCreateAt()->format('Y-m-d H:i:s')
                    ];
                }
        
                $artistData['albums'][] = [
                    'id' => $album->getId(),
                    'name' => $album->getNom(),
                    'category' => $album->getCateg(),
                    'cover' => $album->getCover(),
                    'year' => $album->getYear(),
                    'createdAt' => $album->getCreateAt()->format('Y-m-d H:i:s'),
                    'songs' => $songsData
                ];
            }
            $serializedData[] = $artistData;
        }
        //dd($serializedArtists);
        
        $requestData = $request->query->all();
        $currentPage = isset($requestData['currentPage']) ? $requestData['currentPage'] : 1;
        $itemsPerPage = 2;
        $totalPages = ceil(count($serializedData) / $itemsPerPage);

        if ($currentPage > $totalPages) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun artist trouvé pour la page demandée.'
            ], 404);
        }
        
        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $dataForCurrentPage = array_slice($serializedData, $startIndex, $itemsPerPage);

        $pagination = [
        'currentPage' => (int)($currentPage),
        'totalPage' => $totalPages,
        'totalArtist' => count($serializedData)
        ];

        return $this->json([
            'error'=>false,
            'artists'=> $dataForCurrentPage,
            'message'=>"Informations des artistes récupérées avec succès.",
            'pagination'=> $pagination
        ]);
    }

    // #[Route('/artist', name: 'app_update_artist', methods:['POST'])]
    // public function UpdateArtist(Request $request): JsonResponse
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
    public function DeleteArtist(Request $request): JsonResponse
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

    #[Route('/artist/{fullname}', name: 'app_get_an_artist', methods:['GET'])]
    public function GetAnArtist(Request $request, $fullname): JsonResponse
    {   
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }
        
        if(!$fullname){
            return $this->json([
                'success'=>true,
                'message'=> "Le nom d'artiste est obligatoire pour cette requête."
            ],400);
        }
        if($fullname == ""){
            return $this->json([
                'success'=>true,
                'message'=> "Le format du nom d'artiste fourni est invalide."
            ],400);
        }
        
        //get an artist
        $artist = $this->repository->findOneByFullname( $fullname);
        if(!$artist){
            return $this->json([
                'error'=>true,
                'message'=> "Aucun artiste trouvé correspondant au nom fourni.",
            ],404);
        }
        
        $serializedData = [];
        
        $email = $artist->getUserIdUser()->getEmail();
        $chemin = $this->getParameter('upload_directory') . '/' . $email;
        $path = null;
        $avatar = null;
        if (file_exists($chemin.'/avatar.png')){
            $path = $chemin.'/avatar.png';
        }
        if (file_exists($chemin.'/avatar.jpeg')){
            $path = $chemin.'/avatar.jpeg';
        }

        if($path != null) {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);  
            $avatar = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $artistData = [
            'firstname' => $artist->getUserIdUser()->getFirstname(),
            'lastname' => $artist->getUserIdUser()->getLastname(),
            'fullname' => $artist->getFullname(),
            'avatar' => $avatar,
            'sexe' => ($artist->getUserIdUser()->getSexe() == 0) ? "Femme" : "Homme",
            'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
            'createdAt' => $artist->getCreateAt()->format('Y-m-d H:i:s'),
            'albums' => []
        ];
    
        foreach ($artist->getAlbums() as $album) {
            $songsData = [];
            foreach ($album->getSongIdSong() as $song) {
                $songsData[] = [
                    'id' => $song->getId(),
                    'title' => $song->getTitle(),
                    "cover"=>$song->getCover(),
                    "stream"=>$song->getUrl(),
                    "createAt"=> $song->getCreateAt()->format('Y-m-d H:i:s')
                ];
            }
    
            $artistData['albums'][] = [
                'id' => $album->getId(),
                'name' => $album->getNom(),
                'category' => $album->getCateg(),
                'cover' => $album->getCover(),
                'year' => $album->getYear(),
                'createdAt' => $album->getCreateAt()->format('Y-m-d H:i:s'),
                'songs' => $songsData
            ];
        }
        $serializedData[] = $artistData;
        

        return $this->json([
            'error'=>false,
            'artists'=>  $artistData
        ]);
    }
}
