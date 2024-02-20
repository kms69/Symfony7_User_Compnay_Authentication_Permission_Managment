<?php

namespace App\Controller;

use App\Entity\Company;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CompanyController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/api/companies', name: 'company_index', methods: ['GET'])]
    public function index(): Response
    {
        $companyRepository = $this->entityManager->getRepository(Company::class);
        $companies = $companyRepository->findAll();

        return $this->json($companies);
    }

    #[Route('/api/companies', name: 'company_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        // Authenticate user
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Check user's role
        $userRole = $this->getUserRole($currentUser->getUsername());
        if ($userRole !== 'ROLE_SUPER_ADMIN') {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Validate request data
        $requestData = $this->validateRequestData($request);
        if ($requestData instanceof Response) {
            return $requestData; // Return error response
        }

        // Create company
        $company = $this->createCompany($requestData);

        // Persist company to the database
        $this->entityManager->persist($company);
        $this->entityManager->flush();

        // Return response
        return $this->json($company, Response::HTTP_CREATED);
    }

    private function getUserRole(string $username): ?string
    {
        return $this->userRepository->loadUserByRole($username);
    }

    private function validateRequestData(Request $request): array|Response
    {
        // Check if Content-Type is application/json
        if ($request->headers->get('Content-Type') !== 'application/json') {
            return $this->json(['error' => 'Request must be JSON'], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        // Decode JSON content
        $data = json_decode($request->getContent(), true);

        // Check if decoding was successful
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Check if required fields are present
        if (!isset($data['name'])) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    private function createCompany(array $data): Company
    {

        $company = new Company();
        $company->setName($data['name']);

        return $company;
    }

    #[Route('/api/companies/{id}', name: 'company_show', methods: ['GET'])]
    public function show(Company $company): Response
    {
        return $this->json($company);
    }
}
