<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;

class TokenVerifierService {
    private $jwtManager;
    private $jwtProvider;
    private $userRepository;

    public function __construct(JWTTokenManagerInterface $jwtManager, JWSProviderInterface $jwtProvider, UserRepository $userRepository)
    {
        $this->jwtManager = $jwtManager;
        $this->jwtProvider = $jwtProvider;
        $this->userRepository = $userRepository;
    }

    public function checkToken(Request $request){
        if( $request->headers->has('Authorization')){
            $data =  explode(" ", $request->headers->get('Authorization'));
            if(count($data) == 2){
                $token = $data[1];
                try {
                    $dataToken = $this->jwtProvider->load($token);
                    if($dataToken->isVerified($token)){
                        $user = $this->userRepository->findOneBy(["email" => $dataToken->getPayload()["username"]]);
                        return ($user) ? $user : false;
                    }
                } catch (\Throwable $th) {
                    return false;
                }
            }
        }else{
            return true;
        }
        return false;
    }

    public function sendJsonErrorToken($nullToken): Array
    {
        return [
            'error' => true,
            'message' => ($nullToken) ?"Authentification requise. Vous devez être connecté pour effectuer cette action." : "Vous n'êtes pas autorisé à accéder aux informations.",
        ];
    }
}