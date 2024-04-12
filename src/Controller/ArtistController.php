<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ArtistController extends AbstractController
{
    private $entityManager;
    private $artistRepository;
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->artistRepository = $entityManager->getRepository(Artist::class);
        $this->userRepository = $entityManager->getRepository(User::class);
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

    #[Route('/artists/{id}', name: 'artist_show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $artist = $this->artistRepository->find($id);

        if (!$artist) {
            return $this->json([
                'error' => true,
                'message' => 'Artiste non trouvé; Aucun artiste trouvé correspondant à l\'ID fourni.',
            ], 404);
        }

        return $this->json([
            'artist' => $artist,
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }

    #[Route('/artists', name: 'artist_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['fullname'])) {
            return $this->json([
                'error' => true,
                'message' => 'Le nom d\'artiste est obligatoire pour cette requête.',
            ], 400);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté.',
            ], 401);
        }

        $artist = new Artist();
        $artist->setFullname($data['fullname']);
        $artist->setUser($user);

        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Votre compte a été créé avec succès. Bienvenue dans notre communauté d\'artistes !',
        ]);
    }

    #[Route('/artists/{id}', name: 'artist_update', methods: ['PUT'])]
    public function update($id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $artist = $this->artistRepository->find($id);
        if (!$artist) {
            return $this->json([
                'error' => true,
                'message' => 'Artiste non trouvé; Aucun artiste trouvé correspondant à l\'ID fourni.',
            ], 404);
        }

        if ($artist->getUser() !== $this->getUser()) {
            return $this->json([
                'error' => true,
                'message' => 'Mise à jour non autorisée. Vous n\'avez pas les droits requis pour modifier les informations de cet artiste.',
            ], 403);
        }

        if (isset($data['fullname'])) {
            $fullname = $data['fullname'];
            $artist->setFullname($fullname);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Les informations de l\'artiste ont été mises à jour avec succès.',
        ]);
    }

    #[Route('/artists/{id}', name: 'artist_delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        $artist = $this->artistRepository->find($id);

        if (!$artist) {
            return $this->json([
                'error' => true,
                'message' => 'Artiste non trouvé; Aucun artiste trouvé correspondant à l\'ID fourni.',
            ], 404);
        }

        if ($artist->getUser() !== $this->getUser()) {
            return $this->json([
                'error' => true,
                'message' => 'Suppression non autorisée. Vous n\'avez pas les droits requis pour supprimer cet artiste.',
            ], 403);
        }

        $this->entityManager->remove($artist);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'L\'artiste a été supprimé avec succès.',
        ]);
    }
} 
