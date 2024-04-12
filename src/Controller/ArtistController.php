<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\SerializerInterface;

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
    public function show($id, SerializerInterface $serializer): JsonResponse
    {
        $artist = $this->artistRepository->find($id);

        if (!$artist) {
            return $this->json([
                'error' => true,
                'message' => 'Artiste non trouvé. Vérifiez les informations fournies.',
            ], 404);
        }

        $data = $serializer->serialize($artist, 'json', ['groups' => 'exclude']);

        return JsonResponse::fromJsonString($data);
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
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.',
            ], 401);
        }

        $artist = new Artist();
        $artist->setFullname($data['fullname']);
        $artist->setUser($user);

        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Votre compte a été créé avec succès. Bienvenue dans notre communauté d\'artistes !',
        ]);
    }

    #[Route('/artists/{id}', name: 'artist_update', methods: ['PUT'])]
    public function update($id, Request $request, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $artist = $this->artistRepository->find($id);
        if (!$artist) {
            return $this->json([
                'error' => true,
                'message' => 'Artiste non trouvé. Vérifiez les informations fournies.',
            ], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.',
            ], 401);
        }

        if ($artist->getUser() !== $user) {
            return $this->json([
                'error' => true,
                'message' => 'Mise à jour non autorisée. Vous n\'avez pas les droits requis pour modifier les informations de cet artiste.',
            ], 403);
        }

        if (!isset($data['fullname'])) {
            return $this->json([
                'error' => true,
                'message' => 'Le nom d\'artiste est obligatoire pour cette requête.',
            ], 400);
        }

        $artist->setFullname($data['fullname']);

        $this->entityManager->flush();

        $responseData = $serializer->serialize($artist, 'json', ['groups' => 'exclude']);

        return JsonResponse::fromJsonString($responseData);
    }

    #[Route('/artists/{id}', name: 'artist_delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        $artist = $this->artistRepository->find($id);

        if (!$artist) {
            return $this->json([
                'error' => true,
                'message' => 'Artiste non trouvé. Vérifiez les informations fournies.',
            ], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.',
            ], 401);
        }

        if ($artist->getUser() !== $user) {
            return $this->json([
                'error' => true,
                'message' => 'Suppression non autorisée. Vous n\'avez pas les droits requis pour supprimer cet artiste.',
            ], 403);
        }

        $this->entityManager->remove($artist);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'L\'artiste a été supprimé avec succès.',
        ]);
    }
}
