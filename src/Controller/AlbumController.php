<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Song;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlbumController extends AbstractController
{

    private $repository;
    private $entityManager;
    private $songRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository =  $entityManager->getRepository(Album::class);
        $this->songRepository = $entityManager->getRepository(Song::class);
    }


    #[Route('/album/{id}', name: 'app_album',methods: ['GET'])]
    public function getAnAlbum(int $id): Response
    {
        //check token
        // return $this->json([
        //     'error'=>true,
        //     'message'=>"Votre token n'est pas correct"          
        // ],401);

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
