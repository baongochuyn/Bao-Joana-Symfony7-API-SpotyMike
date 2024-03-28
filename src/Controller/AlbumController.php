<?php

namespace App\Controller;

use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AlbumController extends AbstractController
{
    private $entityManager;
    private $albumRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->albumRepository = $entityManager->getRepository(Album::class);
    }

    #[Route('/albums', name: 'albums_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $albums = $this->albumRepository->findAll();

        return $this->json([
            'albums' => $albums,
            'path' => 'src/Controller/AlbumController.php',
        ]);
    }

    // #[Route('/album/create', name: 'album_create', methods: ['POST'])]
    // public function create(Request $request): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);

    //     $album = new Album();
    //     $album->setTitle($data['title']);
     
    //     $this->entityManager->persist($album);
    //     $this->entityManager->flush();

    //     return $this->json([
    //         'message' => 'Album created successfully!',
    //         'path' => 'src/Controller/AlbumController.php',
    //     ]);
    // }

    // #[Route('/albums/{id}', name: 'album_show', methods: ['GET'])]
    // public function show($id): JsonResponse
    // {
    //     $album = $this->albumRepository->find($id);

    //     if (!$album) {
    //         return $this->json([
    //             'error' => 'Album not found!',
    //         ], 404);
    //     }

    //     return $this->json([
    //         'album' => $album,
    //         'path' => 'src/Controller/AlbumController.php',
    //     ]);
    // }

    // #[Route('/albums/{id}', name: 'album_update', methods: ['PUT'])]
    // public function update($id, Request $request): JsonResponse
    // {
    //     $album = $this->albumRepository->find($id);

    //     if (!$album) {
    //         return $this->json([
    //             'error' => 'Album not found!',
    //         ], 404);
    //     }

    //     $data = json_decode($request->getContent(), true);

    //     $album->setTitle($data['title']);
    //      $this->entityManager->flush();

    //     return $this->json([
    //         'message' => 'Album updated successfully!',
    //         'path' => 'src/Controller/AlbumController.php',
    //     ]);
    // }

    // #[Route('/albums/{id}', name: 'album_delete', methods: ['DELETE'])]
    // public function delete($id): JsonResponse
    // {
    //     $album = $this->albumRepository->find($id);

    //     if (!$album) {
    //         return $this->json([
    //             'error' => 'Album not found!',
    //         ], 404);
    //     }

    //     $this->entityManager->remove($album);
    //     $this->entityManager->flush();

    //     return $this->json([
    //         'message' => 'Album deleted successfully!',
    //         'path' => 'src/Controller/AlbumController.php',
    //     ]);
    // }
}
