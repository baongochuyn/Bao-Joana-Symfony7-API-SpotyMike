<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use DateTimeImmutable;
use Doctrine\Migrations\Query\Query;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function PHPUnit\Framework\assertFalse;

class AlbumController extends AbstractController
{

    private $repository;
    private $entityManager;
    private $songRepository;
    private $tokenVerifier;
    private $artistRepository;
    private $albumRepository;

    public function __construct(EntityManagerInterface $entityManager,TokenVerifierService $tokenVerifier)
    {
        $this->entityManager = $entityManager; 
        $this->repository =  $entityManager->getRepository(Album::class);
        $this->songRepository = $entityManager->getRepository(Song::class);
        $this->artistRepository = $entityManager->getRepository(Artist::class);
        $this->albumRepository = $entityManager->getRepository(Album::class);
        $this->tokenVerifier = $tokenVerifier;
    }


    #[Route('/album/{id}', name: 'app_get_an_album', methods:['GET'])]
    public function GetAnAlbum(Request $request, $id): JsonResponse
    { 
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware),401);
        }
        if(!isset($id)){
            return $this->json([
                'error'=>true,
                'message'=>"L'id de l'album est obligatoire pour cette requête."          
            ],400);
        }
            
        $result = $this->repository->findById(['id'=>$id]);
        
        if($result){
            foreach($result as $album){
                $albumData = $album->serializer();
                $songsData = [];
                foreach($album->getSongIdSong() as $song){
                    $songsData[] = $song->serializer();
                }
                $albumData['songs']= $songsData;
                
                $email =  $album->getArtistUserIdUser()->getUserIdUser()->getEmail();
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
                    'firstname' => $album->getArtistUserIdUser()->getUserIdUser()->getFirstname(),
                    'lastname' => $album->getArtistUserIdUser()->getUserIdUser()->getLastname(),
                    'fullname' => $album->getArtistUserIdUser()->getFullname(),
                    'avatar' => $avatar,
                    'follower' =>  $album->getArtistUserIdUser()->getFollower(),
                    'cover'=> "",
                    'sexe' => ($album->getArtistUserIdUser()->getUserIdUser()->getSexe() == 0) ? "Femme" : "Homme",
                    'dateBirth' => $album->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                    'createdAt' => $album->getArtistUserIdUser()->getCreateAt()->format('Y-m-d'),
                ];
                $albumData['artist']= $artistData;
            }
            if($albumData)
            return $this->json([
                'error'=>false,
                'album'=>$albumData       
            ]);
        };

        return $this->json([
            'error'=>true,
            'message'=>"L'album non trouvé. Vérifiez les informations fournies et réessayez."          
        ],404);
    }

    #[Route('/albums', name: 'app_get_albums', methods:['GET'])]
    public function GetAllAlbum(Request $request): JsonResponse
    { 
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware),401);
        }
        
        $result = $this->repository->findAlbums();
        $serializedData = [];
        if($result){
            foreach($result as $album){
                $albumData = $album->serializer();
                $songsData = [];
                foreach($album->getSongIdSong() as $song){
                    $songsData[] = $song->serializer();
                }
                $albumData['songs']= $songsData;
                
                $email =  $album->getArtistUserIdUser()->getUserIdUser()->getEmail();
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
                    'firstname' => $album->getArtistUserIdUser()->getUserIdUser()->getFirstname(),
                    'lastname' => $album->getArtistUserIdUser()->getUserIdUser()->getLastname(),
                    'fullname' => $album->getArtistUserIdUser()->getFullname(),
                    'avatar' => $avatar,
                    'follower' =>  $album->getArtistUserIdUser()->getFollower(),
                    'cover'=> "",
                    'sexe' => ($album->getArtistUserIdUser()->getUserIdUser()->getSexe() == 0) ? "Femme" : "Homme",
                    'dateBirth' => $album->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                    'createdAt' => $album->getArtistUserIdUser()->getCreateAt()->format('Y-m-d'),
                ];
                $albumData['artist']= $artistData;
                
                $serializedData[] = $albumData;
            }
        };
        $requestData = $request->query->all();
        $currentPage =1;
        $totalPages = 1;
        //dd($requestData['currentPage']);
        if(isset($requestData['currentPage']) && is_numeric($requestData['currentPage']) && intval($requestData['currentPage']) > 0){
            $currentPage = $requestData['currentPage'];
            $itemsPerPage  = 5;
            if (isset($requestData['limit'])){
                if (is_numeric($requestData['currentPage']) && intval($requestData['currentPage']) > 0){
                    $itemsPerPage = $requestData['limit'];
                }
                else{
                    return $this->json([
                        'error'=>true,
                        'message'=>"Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide."          
                    ],400);
                }
            }
            $totalPages = ceil(count($serializedData) / $itemsPerPage);
        }else{
            return $this->json([
                'error'=>true,
                'message'=>"Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide."          
            ],400);
        }
        if ($currentPage > $totalPages) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé pour la page demandée.'
            ], 404);
        }
        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $dataForCurrentPage = array_slice($serializedData, $startIndex, $itemsPerPage);

        $pagination = [
        'currentPage' => (int)($currentPage),
        'totalPage' => $totalPages,
        'totalAlbums' => count($serializedData)
        ];

        return $this->json([
            'error'=>false,
            'albums'=> $dataForCurrentPage,
            'pagination'=> $pagination
        ]);
    }

    #[Route('/albums/search', name: 'app_search_albums', methods:['GET'])]
    public function SearchAlbum(Request $request): JsonResponse
    { 
        // 401
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware),401);
        }
        
        $requestData = $request->query->all();
        if(count($requestData) <= 0){
            return $this->json([
                'error'=>true,
                'message'=> "Les paramètres fournis sont invalides. Veuillez vérifier les données soumises."
            ],400);
        }

        //typo issue from plan final labe -> label.
        $arrParam = array("nom", "fullname", "labe", "year", "featuring", "category", "limit");
        foreach ($requestData as $key => $value){
            if (!in_array($key, $arrParam)){
                return $this->json([
                    'error'=>true,
                    'message'=> "Les paramètres fournis sont invalides. Veuillez vérifier les données soumises."
                ],400);
            }
        };

        //check categ
        $categBase = ["rap","r'n'b","gospel","soul","country","hip hop","jazz","le Mike"];
        $categ = "";
        if (isset($requestData['category'])){
            $arrayCat = json_decode($requestData['category']);
            if(!$arrayCat || strlen($requestData['category']) <= 0 ){
                return $this->json([
                    'error'=>true,
                    'message'=> "Les catégorie ciblée sont invalide."
                ],400);
            }
            
            foreach ($arrayCat as $key => $value) {
                if (in_array($value, $categBase)) {
                    if ($key === count($arrayCat ) - 1) { //check if it's the last ele ? without , : with ,
                        $categ .= $value;
                    } else {
                        $categ .= "$value,";
                    }
                } else {
                    return $this->json([
                        'error'=>true,
                        'message'=> "Les catégorie ciblée sont invalide."
                    ],400);
                }
            }
        }

        $featuringList = null;
        if(isset($requestData['featuring'])){
            $featuringList = json_decode($requestData['featuring']);
            if (!$featuringList || !is_array($featuringList)){
                return $this->json([
                    'error'=>true,
                    'message'=> "Les featuring ciblée sont invalide."
                ],400);
            }
        }

        $year = -1;
        if(isset($requestData['year'])){
            if (!is_numeric($requestData['year']) || intval($requestData['year']) < 0 || intval($requestData['year'] > 32767)){
                return $this->json([
                    'error'=>true,
                    'message'=> "L'année n'est pas valide."
                ],400);
            }
            $year = $requestData['year'];
        }
        
        // handle albums
        $result = $this->repository->findAlbums();
        $serializedData = [];
        if($result){
            foreach($result as $album){
                if ($year != -1 && $album->getYear() != $year){
                    continue;
                }

                if (isset($requestData['nom']) && $album->getNom() != $requestData['nom']){
                    continue;
                }

                if (isset($requestData['fullname']) && $album->getArtistUserIdUser()->getFullname() != $requestData['fullname']){
                    continue;
                }

                if (isset($requestData['labe']) && $album->getLabel() != $requestData['labe']){
                    continue;
                }

                if ($categ != "" && $album->getCateg() != $categ){
                    continue;
                }
                $fullname = $album->getArtistUserIdUser()->getFullname();
                if ($featuringList != null && !in_array($fullname, $featuringList )){
                    continue;
                }

                $albumData = $album->serializer();
                $songsData = [];
                foreach($album->getSongIdSong() as $song){
                    $songsData[] = $song->serializer();
                }
                $albumData['songs']= $songsData;
                
                $artistData = [
                    'firstname' => $album->getArtistUserIdUser()->getUserIdUser()->getFirstname(),
                    'lastname' => $album->getArtistUserIdUser()->getUserIdUser()->getLastname(),
                    'sexe' => ($album->getArtistUserIdUser()->getUserIdUser()->getSexe() == 0) ? "Femme" : "Homme",
                    'dateBirth' => $album->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                    'createdAt' => $album->getArtistUserIdUser()->getCreateAt()->format('Y-m-d'),
                ];
                $albumData['artist']= $artistData;
                
                $serializedData[] = $albumData;
            }
        };

        $currentPage = 1;
        $itemsPerPage = 5;
        //dd($requestData['currentPage']);
        if(isset($requestData['currentPage'])){
            if( is_numeric($requestData['currentPage']) && intval($requestData['currentPage']) > 0){
                $currentPage = $requestData['currentPage'];
                $itemsPerPage = 5;
            }else{
                return $this->json([
                    'error'=>true,
                    'message'=>"Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide."          
                ],400);
            }
        }

        if (isset($requestData['limit'])){
            if (is_numeric($requestData['limit']) && intval($requestData['limit']) > 0){
                $itemsPerPage = $requestData['limit'];
            }
            else{
                return $this->json([
                    'error'=>true,
                    'message'=>"Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide."          
                ],400);
            }
        }
        $totalPages = ceil(count($serializedData) / $itemsPerPage);

        if ($currentPage > $totalPages) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé pour la page demandée.'
            ], 404);
        }

        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $dataForCurrentPage = array_slice($serializedData, $startIndex, $itemsPerPage);

        $pagination = [
        'currentPage' => (int)($currentPage),
        'totalPage' => $totalPages,
        'totalAlbums' => count($serializedData)
        ];

        return $this->json([
            'error'=>false,
            'albums'=> $dataForCurrentPage,
            'pagination'=> $pagination
        ]);
    }

    #[Route('/album', name: 'app_create_album', methods:['POST'])]
    public function CreateAlbum(Request $request): JsonResponse
    { 
        $requestData = $request->request->all();

        // check authentication 401
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware),401);
        }

        // check authorization 403. If user is not artist, he has no right.
        $user = $dataMiddellware;
        $artist = $this->artistRepository->findOneBy(['User_idUser'=>$user]);
        if(!$artist){
            return $this->json([
                'error'=>true,
                'message'=> "Vous n'avez pas l'authorisation pour accéder à cet album."
            ],403);        
        }

        $album = new Album;
        $categ = "";
        // if not present 4 element, or contain wrong element => 400
        if(count($requestData) != 4 || !isset($requestData['visibility']) || !isset($requestData['cover']) 
        || !isset($requestData['title']) || !isset($requestData['categorie'])){
            return $this->json([
                'error'=>true,
                'message'=> "Les paramètres fournis sont invalide. Veuillez vérifier les données soumises."
            ],400);
        }else{
            //check visibility
            if($requestData['visibility'] != 0 && $requestData['visibility'] != 1){
                return $this->json([
                    'error'=>true,
                    'message'=> "La valeur du champ visibility est invalide. Les valeurs autorisées sont 0 pour invisible, 1 pour visible."
                ],400);
            }
            $invalide = false;
            
            //check categ
            $categBase = ["rap","r'n'b","gospel","soul","country","hip hop","jazz","le Mike"];
            
            if(strlen($requestData['categorie']) > 0){
                foreach (json_decode($requestData['categorie']) as $key => $value) {
                    if (in_array($value, $categBase)) {
                        if ($key === count(json_decode($requestData['categorie'])) - 1) { //check if it's the last ele ? without , : with ,
                            $categ .= $value;
                        } else {
                            $categ .= "$value,";
                        }
                    } else {
                        return $this->json([
                            'error'=>true,
                            'message'=> "Les catégorie ciblée sont invalide."
                        ],400);
                    }
                }
            }else{
                $invalide = true;
            }
           
            //check title
            if(strlen($requestData['title']) < 1 || strlen($requestData['title']) > 90){
                $invalide = true;
            }

            if($invalide){
                return $this->json([
                    'error'=>true,
                    'message'=> "Erreur de validation des données."
                ],422);
            } 

            $albumdata = $this->repository->findOneBy(['nom'=>$requestData['title']]);
            if($albumdata){
                return $this->json([
                    'error'=>true,
                    'message'=> "Ce titre est déjà pris. Veuillez en choisir un autre."
                ],409);
            }
            
            //check cover
            $explodeData = explode(",", $requestData['cover']);
            if (count($explodeData) == 2) {
                $file = base64_decode($explodeData[1]);
                if($file == false){
                    return $this->json([
                        'error'=>true,
                        'message'=> "Le serveur ne peut pas décoder le contenue base64 en fichier binaire.",
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

                $tempFilePath = tempnam(sys_get_temp_dir(), 'cover_');
                file_put_contents($tempFilePath, $file);
                $fileSize = getimagesize($tempFilePath);
                $size = ($fileSize[0]* $fileSize[1]*24)/(1024*1024*8);
                if($size < 1 || $size > 7){
                    return $this->json([
                                'error'=>true,
                                'message'=> "Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1Mb et 7Mb.",
                            ],422);
                }
                //save cover in data
                $album->setCover($file);
            }
            else {
                return $this->json([
                    'error'=>true,
                    'message'=> "Le serveur ne peut pas décoder le contenue base64 en fichier binaire.",
                ],422);
            }
        }
        $datetime = new DateTimeImmutable();

        $album->setIdAlbum("Album_".rand(0,999999999999));
        $album->setNom($requestData['title']);
        $album->setCateg($categ);
        $album->setLabel("Label_".rand(0,999999999999));
        $album->setYear($datetime->format('Y'));
        $album->setCreateAt($datetime);
        $album->setActive(true);
        $album->setVisibility(true);
        $album->setArtistUserIdUser($artist);

       // dd($album);
        $this->entityManager->persist($album);
        $this->entityManager->flush();
        return $this->json([
            'error'=>false,
            'message'=>"Album créé avec succès.",
            'id' => $album->getId()
        ]);
    }

    #[Route('/album/{id}', name: 'app_update_album', methods:['PUT'])]
    public function UpdateAlbum(Request $request,$id): JsonResponse
    { 
        // 401
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware),401);
        }

        // 403
        $user = $dataMiddellware;
        $artist = $this->artistRepository->findOneBy(['User_idUser'=>$user]);
        if(!$artist){
            return $this->json([
                'error'=>true,
                'message'=> "Vous n'avez pas l'authorisation pour accéder à cet album."
            ],403);
        }

        $requestData = $request->request->all();
        $categ = "";
        // 404
        $album = $this->albumRepository->findOneBy(['id'=>$id]);
        if(!$album){
            return $this->json([
                'error'=>true,
                'message'=> "Aucun album trouvé correspondant au nom fourni."
            ],404);
        }

        //dd($requestData);
        $arrCat = array("visibility", "cover", "categorie", "title");
        $isCorrectParam = false;
        foreach ($requestData as $key => $value){
            if (!in_array($key, $arrCat)){
                return $this->json([
                    'error'=>true,
                    'message'=> "Les paramètres fournis sont invalides. Veuillez vérifier les données soumises."
                ],400);
            }
            $isCorrectParam = true;
        };

        if(!$isCorrectParam){
            return $this->json([
                'error'=>true,
                'message'=> "Les paramètres fournis sont invalide. Veuillez vérifier les données soumises."
            ],400);
        }else
        {
            //check visibility
            if(isset($requestData['visibility'])){
                if($requestData['visibility'] != 0 && $requestData['visibility'] != 1){
                    return $this->json([
                        'error'=>true,
                        'message'=> "La valeur du champ visibility est invalide. Les valeurs autorisées sont 0 pour invisible, 1 pour visible."
                    ],400);
                }
                $album->setVisibility(true);
            }

            $invalide = false;
            //check categ
            if(isset($requestData['categorie'])){
                $categBase = ["rap","r'n'b","gospel","soul","country","hip hop","jazz","le Mike"];
            
                if(strlen($requestData['categorie']) > 0){
                    foreach (json_decode($requestData['categorie']) as $key => $value) {
                        if (in_array($value, $categBase)) {
                            if ($key === count(json_decode($requestData['categorie'])) - 1) { //check if it's the last ele ? without , : with ,
                                $categ .= $value;
                            } else {
                                $categ .= "$value,";
                            }
                        } else {
                            return $this->json([
                                'error'=>true,
                                'message'=> "Les catégorie ciblée sont invalide."
                            ],400);
                        }
                    }
                    $album->setCateg($categ);
                }else{
                    $invalide = true;
                }
            }
            
            //check title
            if(isset($requestData['title'])){
                if(strlen($requestData['title']) < 1 || strlen($requestData['title']) > 90){
                    $invalide = true;
                } 
                $albumdata = $this->repository->findOneBy(['nom'=>$requestData['title']]);
                //dd($albumdata);
                if($albumdata){
                    return $this->json([
                        'error'=>true,
                        'message'=> "Ce titre est déjà pris. Veuillez en choisir un autre."
                    ],409);
                }
                $album->setNom($requestData['title']);
            }
          
            if($invalide){
                return $this->json([
                    'error'=>true,
                    'message'=> "Erreur de validation des données."
                ],422);
            }
            
            //check cover
            if(isset($requestData['cover'])){
                $explodeData = explode(",", $requestData['cover']);
                if (count($explodeData) == 2) {
                    $file = base64_decode($explodeData[1]);
                    if($file === false){
                        return $this->json([
                            'error'=>true,
                            'message'=> "Le serveur ne peut pas décoder le contenue base64 en fichier binaire.",
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

                    $tempFilePath = tempnam(sys_get_temp_dir(), 'cover_');
                    file_put_contents($tempFilePath, $file);
                    $fileSize = getimagesize($tempFilePath);
                    $size = ($fileSize[0]* $fileSize[1]*24)/(1024*1024*8);
                    if($size < 1 || $size > 7){
                        return $this->json([
                                    'error'=>true,
                                    'message'=> "Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1Mb et 7Mb.",
                                ],422);
                    }
                    //save cover in data
                    $album->setCover($file);
                }
                else {
                    return $this->json([
                        'error'=>true,
                        'message'=> "Le serveur ne peut pas décoder le contenue base64 en fichier binaire.",
                    ],422);
                }
            }
        }
        $album->setActive(true);
        
        $this->entityManager->persist($album);
        $this->entityManager->flush();
        return $this->json([
            'error'=>false,
            'message'=>"Album mis à jour avec succès.",
            'id' => $album->getId()
        ]);
    }

    #[Route('/album/{id}/song', name: 'app_update_album_song', methods:['POST'])]
    public function UpdateSongAlbum(Request $request,$id): JsonResponse
    { 
        // 401
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware),401);
        }

        // 403
        $user = $dataMiddellware;
        $artist = $this->artistRepository->findOneBy(['User_idUser'=>$user]);
        if(!$artist){
            return $this->json([
                'error'=>true,
                'message'=> "Vous n'avez pas l'authorisation pour accéder à cet album."
            ],403);
        }

        // 404
        $album = $this->albumRepository->findOneBy(['id'=>$id]);
        if(!$album){
            return $this->json([
                'error'=>true,
                'message'=> "Aucun album trouvé correspondant au nom fourni."
            ],404);
        }

        $requestData = $request->request->all();

        $song = new Song();
        $album->addSongIdSong($song);
        
        $this->entityManager->persist($album);
        $this->entityManager->flush();
        return $this->json([
            'error'=>false,
            'message'=>"Album mis à jour avec succès.",
            'id' => $album->getId()
        ]);
    }
}
