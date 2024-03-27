<?php

namespace App\Controller;

use App\Entity\Song;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SongController extends AbstractController
{
    private $repository;
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository =  $entityManager->getRepository(Song::class);
    }

    #[Route('/song', name: 'app_get_song', methods:['GET'])]
    public function index(): JsonResponse
    {
        $songs = $this->repository->findAll();
        return $this->json([
            'songs'=>json_encode($songs),
            'message' => 'successful !!! ',
            'path' => 'src/Controller/SongController.php',
        ]);
    }

    #[Route('/song/create', name: 'app_create_song',methods:['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $song = new Song();
        if(isset($requestData['idSong'])){
            $song->setIdSong($requestData['idSong']);
        }
        if(isset($requestData['title'])){
            $song->setTitle($requestData['title']);
        }
        if(isset($requestData['url'])){
            $song->setUrl($requestData['url']);
        }
        if(isset($requestData['cover'])){
            $song->setCover($requestData['cover']);
        }
        if(isset($requestData['visibility'])){
            $song->setVisibility($requestData['visibility']);
        }
        if(isset($requestData['createAt'])){
            $song->setCreateAt(new \DateTimeImmutable($requestData['createAt']));
        }
       

        $this->entityManager->persist($song);
        $this->entityManager->flush();

        return $this->json([
            'request'=> $requestData,
            'message' => 'created !!! ',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/song/{id}', name: 'app_song', methods:['GET'])]
    public function getSong(int $id): JsonResponse
    {
        $song = $this->repository->findOneBy(['id'=>$id]);
        return $this->json([
            'song'=>json_encode($song),
            'message' => 'successful !!! ',
            'path' => 'src/Controller/SongController.php',
        ]);
    }

    #[Route('/song/update/{id}', name: 'app_update_song',methods:['POST','PUT'])]
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $song =$this->repository->findOneBy(['id'=> $id]);

        if(!$song){
            return $this->json([
                'message' => 'song not found !!! ',
                'path' => 'src/Controller/SongController.php',
            ]);
        }
        if(isset($requestData['idSong'])){
            $song->setIdSong($requestData['idSong']);
        }
        if(isset($requestData['title'])){
            $song->setTitle($requestData['title']);
        }
        if(isset($requestData['url'])){
            $song->setUrl($requestData['url']);
        }
        if(isset($requestData['cover'])){
            $song->setCover($requestData['cover']);
        }
        if(isset($requestData['visibility'])){
            $song->setVisibility($requestData['visibility']);
        }
        if(isset($requestData['createAt'])){
            $song->setCreateAt(new \DateTimeImmutable($requestData['createAt']));
        }
       
        $this->entityManager->flush();

        return $this->json([
            'request'=> $requestData,
            'message' => 'song updated !!! ',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/song/delete/{id}', name: 'song_delete', methods: ['DELETE'])]
    public function deleteSong(int $id): JsonResponse
    {
        $song = $this->repository->find($id);

        if (!$song) {
            return $this->json([
                'message' => 'user not found !!! ',
                'path' => 'src/Controller/SongController.php',
            ]);
        }

        $this->entityManager->remove($song);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'deleted !!! ',
            'path' => 'src/Controller/SongController.php',
        ]);
    }
}
