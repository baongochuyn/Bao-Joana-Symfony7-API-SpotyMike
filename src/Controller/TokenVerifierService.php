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
        $query = $request->query->all();
        if( $request->headers->has('Authorization') || isset($query["token"])){
            $token = "";
            
            if(isset($query["token"])){
                $token = $query["token"];
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
            else if( $request->headers->has('Authorization')){
                $data = explode(" ", $request->headers->get('Authorization'));
                if(count($data) == 2){
                    $token = $data[1];
                }
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

    public function checkTokenWithParam($token){
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
    public function isTokenExpired($token){
        try{
            $dataToken = $this->jwtProvider->load($token);
            $expiration = $dataToken->getPayload(); 
            return ( $expiration["exp"]  < time()) ? true : false;
        }
        catch (\Throwable $th) {
            return $th;
        }
       //dd(date('m/d/Y H:i:s', 1713531003) ,date('m/d/Y H:i:s', $expiration["exp"]), date('m/d/Y H:i:s',time()));
        return true;
    }
    public function sendJsonErrorToken($nullToken): Array
    {
        return [
            'error' => true,
            'message' => ($nullToken) ?"Authentification requise. Vous devez être connecté pour effectuer cette action." : "Vous n'êtes pas autorisé à accéder aux informations.",
        ];
    }
}