<?php

namespace App\Tests;

use AllowDynamicProperties;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\UserController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Mockery;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;


#[AllowDynamicProperties] class UserTest extends WebTestCase
{

    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testLoginSuccessful()
    {
        $mockRequest = Mockery::mock(Request::class);
        $mockRequest->allows('getContent')->andReturns(json_encode(['name' => 'john.doe']));

//        $mockUser = new User();
//        $mockUser->setName('john.doe');
//        $mockUser->setRole('ROLE_SUPER_ADMIN');
        $mockUser = $this->createMockUser('john.doe', 'ROLE_SUPER_ADMIN');


        $mockUserRepository = Mockery::mock(UserRepository::class);
        $mockUserRepository->allows('findOneBy')->with(['name' => 'john.doe'])->andReturns($mockUser);
        $this->entityManagerMock
            ->allows('getRepository')
            ->with(User::class)
            ->andReturns($mockUserRepository);

        $mockEntityManager = Mockery::mock(EntityManagerInterface::class);
        $mockEntityManager->allows('getRepository')->with(User::class)->andReturns($mockUserRepository);


        $mockController = Mockery::mock(UserController::class, [$this->entityManagerMock, $this->userRepositoryMock, $this->tokenStorageMock]) // Pass $this->tokenStorageMock here
        ->makePartial();


        $response = $mockController->login($mockRequest);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertIsString($responseData['token']);


        $secretKey = $_ENV['JWT_SECRET'];
        $decodedToken = JWT::decode($responseData['token'], new Key($secretKey, 'HS256'));
        $this->assertEquals('john.doe', $decodedToken->name);


        $userRepositoryMock = Mockery::mock(UserRepository::class);
        $userRepositoryMock->allows('loadUserByRole')->with('john.doe')->andReturn('ROLE_USER_ADMIN');


        $mockEntityManager = Mockery::mock(EntityManagerInterface::class);
        $mockEntityManager->allows('getRepository')->with(User::class)->andReturn($userRepositoryMock);


        $response = $mockController->login($mockRequest);


        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        return $response;

    }

    protected function createMockUser(string $name, string $role): User
    {
        $mockUser = new User();

        $mockUser->setName($name);
        $mockUser->setId(1);
        $mockUser->setRole($role);
        return $mockUser;
    }

    protected function setUp(): void
    {

        parent::setUp();


        $this->entityManagerMock = Mockery::mock(EntityManagerInterface::class);
        $this->userRepositoryMock = Mockery::mock(UserRepository::class);
        $this->tokenStorageMock = Mockery::mock(TokenStorageInterface::class); // Add this line

    }


}
