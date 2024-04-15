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
                'message'=>"Id de l'album est manquant"          
            ],400);
        }
            
        $album = $this->repository->findOneBy(['id'=>$id]);
        if($album)
            return $this->json([
                'error'=>false,
                'album'=>$album->serializer()       
            ]);

        return $this->json([
            'error'=>true,
            'message'=>"Une ou plusieurs données obligatoire sont erronees"          
        ],409);
    }
}
