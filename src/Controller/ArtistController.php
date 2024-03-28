<?php

namespace App\Controller;

use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ArtistController extends AbstractController
{
    private $entityManager;
    private $artistRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->artistRepository = $entityManager->getRepository(Artist::class);
    }

    #[Route('/artists', name: 'artists_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $artists = $this->artistRepository->findAll();

        return $this->json([
            'artists' => $artists,
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }

    #[Route('/artists', name: 'artist_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $artist = new Artist();
        $artist->setName($data['name']); 
        $artist->setLabel($data['label']);
        $artist->setDescription($data['description']);
        $artist->setFullname($data['fullname']);
        
        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Artist created successfully!',
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }

    #[Route('/artists/{id}', name: 'artist_show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $artist = $this->artistRepository->find($id);

        if (!$artist) {
            return $this->json([
                'error' => 'Artist not found!',
            ], 404);
        }

        return $this->json([
            'artist' => $artist,
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }

    #[Route('/artists/{id}', name: 'artist_update', methods: ['PUT'])]
    public function update($id, Request $request): JsonResponse
    {
        $artist = $this->artistRepository->find($id);

        if (!$artist) {
            return $this->json([
                'error' => 'Artist not found!',
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        $artist->setName($data['name']); 
        $artist->setLabel($data['label']);
        $artist->setDescription($data['description']);
        $artist->setFullname($data['fullname']);
        
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Artist updated successfully!',
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }

    #[Route('/artists/{id}', name: 'artist_delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        $artist = $this->artistRepository->find($id);

        if (!$artist) {
            return $this->json([
                'error' => 'Artist not found!',
            ], 404);
        }

        $this->entityManager->remove($artist);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Artist deleted successfully!',
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }
}
