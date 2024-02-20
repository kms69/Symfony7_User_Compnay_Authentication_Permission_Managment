<?php

namespace App\Security;


use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;


class CustomAuthenticator implements AuthenticatorInterface
{
    private EntityManagerInterface $entityManager;

    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    public function supports(Request $request): ?bool
    {
        if ($request->attributes->get('_route') === 'login' && $request->isMethod('POST')) {
            return false; // Return false for the login route
        }

        // Return true for other routes where you want this authenticator to handle authentication
        return true;
    }


    public function authenticate(Request $request): SelfValidatingPassport
    {
        try {
            $authorizationHeader = $request->headers->get('Authorization');

            if (!$authorizationHeader || !preg_match('/^Bearer\s+(.*?)$/', $authorizationHeader, $matches)) {
                throw new CustomUserMessageAuthenticationException('Invalid authorization header', ['error' => 'Bearer token not found']);
            }

            $jwtToken = $matches[1]; // Extract the JWT token from the Authorization header

            // Retrieve the secret key from the JWT_SECRET environment variable
            $secretKey = $_ENV['JWT_SECRET'] ?? null;

            if (!$secretKey) {
                throw new \Exception('JWT_SECRET environment variable not found.');
            }

            // Decode the JWT token
            $decodedToken = JWT::decode($jwtToken, new Key($secretKey, 'HS256'));
//dd($decodedToken);
            // Extract the user information from the decoded token
            $name = $decodedToken->name ?? null;
//dd($name);
            if (!$name) {
                throw new CustomUserMessageAuthenticationException('User not found in token');
            }

            // Load the user from the database
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['name' => $name]);
//dd($user);
            if (!$user) {
                throw new CustomUserMessageAuthenticationException('User not found', ['name' => $name]);
            }

            $userBadge = new UserBadge($user->getUsername(), function ($username) use ($user) {
                return $user;
            });

// Create and return a SelfValidatingPassport with the UserBadge
            return new SelfValidatingPassport($userBadge);
        } catch (\Exception $e) {
            // Log the exception to PHP error log
            error_log('Authentication error: ' . $e->getMessage());

            // Rethrow the exception with a generic error message
            throw new CustomUserMessageAuthenticationException('Error decoding token');
        }
    }


    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {


        $user = $passport->getUser();

        // Retrieve the roles of the authenticated user using the UserRepository
        $roles = $this->userRepository->loadUserByRole($user->getName());
//dd($roles);
        // Create the UsernamePasswordToken with the provided $firewallName and user roles
        return new UsernamePasswordToken($user, 'main', (array)$roles);

    }
}
