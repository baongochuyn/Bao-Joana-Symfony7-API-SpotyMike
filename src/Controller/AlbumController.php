<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Song;
use Doctrine\Migrations\Query\Query;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlbumController extends AbstractController
{

    private $repository;
    private $entityManager;
    private $songRepository;
    private $tokenVerifier;

    public function __construct(EntityManagerInterface $entityManager,TokenVerifierService $tokenVerifier)
    {
        $this->entityManager = $entityManager;
        $this->repository =  $entityManager->getRepository(Album::class);
        $this->songRepository = $entityManager->getRepository(Song::class);
        $this->tokenVerifier = $tokenVerifier;
    }


    #[Route('/album/{id}', name: 'app_get_an_album', methods:['GET'])]
    public function GetAnAlbum(Request $request, $id): JsonResponse
    { 
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
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
                
                $artistData = [
                    'firstname' => $album->getArtistUserIdUser()->getUserIdUser()->getFirstname(),
                    'lastname' => $album->getArtistUserIdUser()->getUserIdUser()->getLastname(),
                    'sexe' => ($album->getArtistUserIdUser()->getUserIdUser()->getSexe() == 0) ? "Femme" : "Homme",
                    'dateBirth' => $album->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                    'createdAt' => $album->getArtistUserIdUser()->getCreateAt()->format('Y-m-d H:i:s'),
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
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
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
                
                $artistData = [
                    'firstname' => $album->getArtistUserIdUser()->getUserIdUser()->getFirstname(),
                    'lastname' => $album->getArtistUserIdUser()->getUserIdUser()->getLastname(),
                    'sexe' => ($album->getArtistUserIdUser()->getUserIdUser()->getSexe() == 0) ? "Femme" : "Homme",
                    'dateBirth' => $album->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                    'createdAt' => $album->getArtistUserIdUser()->getCreateAt()->format('Y-m-d H:i:s'),
                ];
                $albumData['artist']= $artistData;
                
                $serializedData[] = $albumData;
            }
        };
        $requestData = $request->query->all();
        $currentPage =1;
        //dd($requestData['currentPage']);
        if(isset($requestData['currentPage']) && is_numeric($requestData['currentPage']) && intval($requestData['currentPage']) > 0){
            $currentPage = $requestData['currentPage'];
            $itemsPerPage = isset($requestData['limit']) ? $requestData['limit'] : 5;
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
        'totalArtist' => count($serializedData)
        ];

        return $this->json([
            'error'=>false,
            'albums'=> $dataForCurrentPage,
            'pagination'=> $pagination
        ]);
    }
}
