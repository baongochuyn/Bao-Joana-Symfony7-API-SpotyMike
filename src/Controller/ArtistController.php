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
        $data = $request->request->all();

        if (!isset($data['label']) || !isset($data['fullname'])) {
            return $this->json([
                'error' => true,
                'message' => 'L\'id du label et le fullname sont obligatoires.',
            ], 400);
        }

        $user = $this->userRepository->findOneBy(['id'=>$data['idUser']]);

        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Utilisateur non trouvé; L\'utilisateur avec cet ID n\'existe pas.',
            ], 404);
        }

        $artist = new Artist();
        $artist->setUserIdUser($user);
        $artist->setLabel($data['label']);
        $artist->setDescription($data['description'] ?? '');
        $artist->setFullname($data['fullname']);
        
        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Artist created successfully!',
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }

    #[Route('/artists/{id}', name: 'artist_update', methods: ['PUT'])]
    public function update($id, Request $request): JsonResponse
    {
        $artist = $this->artistRepository->findOneBy(['id'=> $id]);
        $requestData = json_decode($request->getContent(), true);
        $user = $this->userRepository->findOneBy(['id'=>$requestData['idUser']]);

        if (!$artist) {
            return $this->json([
                'error' => true,
                'message' => 'Artiste non trouvé; Aucun artiste trouvé correspondant à l\'ID fourni.',
            ], 404);
        }

        if(isset($requestData['label'])){
            $artist->setLabel($requestData['label']);
        }
        if(isset($requestData['description'])){
            $artist->setDescription($requestData['description']);
        }
        if(isset($requestData['fullname'])){
            $artist->setFullname($requestData['fullname']);
        }
        if(isset($requestData['idUser'])){
            $artist->setUserIdUser($user);
        }
   
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
                'error' => true,
                'message' => 'Artiste non trouvé; Aucun artiste trouvé correspondant à l\'ID fourni.',
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
