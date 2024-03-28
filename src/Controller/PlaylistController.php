<?php

namespace App\Controller;

use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlaylistController extends AbstractController
{
    private $repository;
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository =  $entityManager->getRepository(Playlist::class);
    }

    #[Route('/playlist', name: 'app_playlist')]
    public function index(): JsonResponse
    {
        $playlists = $this->repository->findAll();
        return $this->json([
            'playlists'=>json_encode($playlists),
            'message' => 'successful !!! ',
            'path' => 'src/Controller/SongController.php',
        ]);
    }

    #[Route('/playlist/create', name: 'app_create_playlist',methods:['POST'])]
    public function createPlaylist(Request $request): JsonResponse
    {
        //$requestData = json_decode($request->getContent(),true);
        $requestData = $request->request->all();
        $playlist = new Playlist();
        if(isset($requestData['idPlaylist'])){
            $playlist->setIdPlaylist($requestData['idPlaylist']);
        }
        if(isset($requestData['title'])){
            $playlist->setTitle($requestData['title']);
        }
        if(isset($requestData['public'])){
            $playlist->setPublic($requestData['public']);
        }
        if(isset($requestData['createAt'])){
            $playlist->setCreateAt(new \DateTimeImmutable($requestData['createAt']));
        }
        if(isset($requestData['updateAt'])){
            $playlist->setUpdateAt(new \DateTimeImmutable($requestData['updateAt']));
        }

        $this->entityManager->persist($playlist);
        $this->entityManager->flush();

        return $this->json([
            'request'=> $requestData,
            'message' => 'created !!! ',
            'path' => 'src/Controller/PlaylistController.php',
        ]);
    }

    #[Route('/playlist/{id}', name: 'app_get_playlist', methods:['GET'])]
    public function getPlaylist(int $id): JsonResponse
    {
        $playlist = $this->repository->findOneBy(['id'=>$id]);
        if(!$playlist){
            return $this->json([
                'songId'=>json_encode($id),
                'message' => 'playlist not found !!! ',
                'path' => 'src/Controller/PlaylistController.php',
            ]);
        }
        return $this->json([
            'song'=>json_encode($playlist),
            'message' => 'successful !!! ',
            'path' => 'src/Controller/PlaylistController.php',
        ]);
    }

    #[Route('/playlist/update/{id}', name: 'app_update_playlist',methods:['POST','PUT'])]
    public function updatePlaylist(Request $request, int $id): JsonResponse
    {
        //$requestData = json_decode($request->getContent(),true);
        $requestData = $request->request->all();
        $playlist = $this->repository->findOneBy(['id'=> $id]);

        if(!$playlist){
            return $this->json([
                'message' => 'playlist not found !!! ',
                'path' => 'src/Controller/PlaylistController.php',
            ]);
        }
        if(isset($requestData['idPlaylist'])){
            $playlist->setIdPlaylist($requestData['idPlaylist']);
        }
        if(isset($requestData['title'])){
            $playlist->setTitle($requestData['title']);
        }
        if(isset($requestData['public'])){
            $playlist->setPublic($requestData['public']);
        }
        if(isset($requestData['createAt'])){
            $playlist->setCreateAt(new \DateTimeImmutable($requestData['createAt']));
        }
        if(isset($requestData['updateAt'])){
            $playlist->setUpdateAt(new \DateTimeImmutable($requestData['updateAt']));
        }

        $this->entityManager->flush();

        return $this->json([
            'request'=> $requestData,
            'message' => 'updated !!! ',
            'path' => 'src/Controller/PlaylistController.php',
        ]);
    }

    #[Route('/playlist/delete/{id}', name: 'playlist_delete', methods: ['DELETE'])]
    public function deletePlaylist(int $id): JsonResponse
    {
        $playlist = $this->repository->find($id);

        if (!$playlist) {
            return $this->json([
                'message' => 'playlist not found !!! ',
                'path' => 'src/Controller/SongController.php',
            ]);
        }

        $this->entityManager->remove($playlist);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'deleted !!! ',
            'path' => 'src/Controller/PlaylistController.php',
        ]);
    }
}
