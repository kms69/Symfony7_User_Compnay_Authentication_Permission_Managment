<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private TokenStorageInterface $tokenStorage;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, TokenStorageInterface $tokenStorage)
    {

        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;

    }


    #[Route('/api/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {

        $requestData = json_decode($request->getContent(), true);

        // Ensure the required fields are provided
        if (!isset($requestData['name'])) {
            return new JsonResponse(['error' => 'Name field is required'], Response::HTTP_BAD_REQUEST);
        }

        $name = $requestData['name'];


        $user = $this->entityManager->getRepository(User::class)->findOneBy(['name' => $name]);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Generate a token
        $token = $this->generateToken($name);


        return new JsonResponse(['token' => $token]);
    }

    public function generateToken(string $name): string
    {
        // You can use any method to generate a token. Here, I'm using a simple JWT for demonstration purposes.
        $payload = [
            'name' => $name,
            'exp' => time() + 3600 // Token expiration time (1 hour)
        ];

        // Retrieve the secret key from the APP_SECRET environment variable
        $secretKey = $_ENV['JWT_SECRET'];

        if (!$secretKey) {
            throw new \Exception('JWT_SECRET environment variable not found.');
        }


        return JWT::encode($payload, $secretKey, 'HS256');
    }


    #[Route('/api/users', name: 'user_index', methods: ['GET'])]
    public function index(): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $userRole = $this->getUserRole($currentUser->getUsername());

        if ($userRole === 'ROLE_SUPER_ADMIN') {
            $users = $this->userRepository->findAll();
        } elseif ($userRole === 'ROLE_COMPANY_ADMIN') {
            $users = $this->userRepository->findBy(['company' => $currentUser->getCompany()]);
        } else {
            $users = [$currentUser];
        }

        return $this->json($users);
    }

    private function getUserRole(string $username): ?string
    {
        return $this->userRepository->loadUserByRole($username);
    }

    #[Route('/api/users/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $userRole = $this->getUserRole($currentUser->getUsername());

        if ($userRole !== 'ROLE_SUPER_ADMIN' && ($user->getCompany() !== $currentUser->getCompany() || $userRole !== 'ROLE_COMPANY_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($user);
    }

    #[Route('/api/users', name: 'user_new', methods: ['POST'])]
    public function new(Request $request, ValidatorInterface $validator): Response
    {

        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Check user's role
        $userRole = $this->getUserRole($currentUser->getUsername());
        if (!in_array($userRole, ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN'])) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }


        $requestData = $this->validateRequestData($request, $validator);
        if ($requestData instanceof Response) {
            return $requestData; // Return error response
        }


        $user = $this->createUser($requestData);


        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Return response
        $responseData = [
            'name' => $user->getName(),
            'role' => $requestData['role'],
            'id' => $user->getId(),
            'company' => $user->getCompany()?->getName() ?? null
        ];
        return new JsonResponse($responseData, Response::HTTP_CREATED);
    }

    private function validateRequestData(Request $request, ValidatorInterface $validator): array|Response
    {

        if ($request->headers->get('Content-Type') !== 'application/json') {
            return $this->json(['error' => 'Request must be JSON'], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }


        $data = json_decode($request->getContent(), true);

        // Check if decoding was successful
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['name'])) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    private function createUser(array $data): User
    {

        $user = new User();
        $user->setName($data['name']);
        $role = $data['role'] ?? 'ROLE_USER';
        $user->setRole($role);


        if ($role !== 'ROLE_SUPER_ADMIN' && isset($data['company_id'])) {
            $company = $this->entityManager->getRepository(Company::class)->find($data['company_id']);
            if ($company) {
                $user->setCompany($company);
            }
        }

        return $user;
    }

    #[Route('/api/users/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(User $user): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }


        $userRole = $this->getUserRole($currentUser->getUsername());

        if (!in_array($userRole, ['ROLE_SUPER_ADMIN'])) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }


        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User deleted'], Response::HTTP_OK);
    }

}
