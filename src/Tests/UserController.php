<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserController extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();

        try {
            $client->request('GET', '/api/users');

            $this->assertResponseIsSuccessful();
            $this->assertJson($client->getResponse()->getContent());
        } catch (AccessDeniedException $e) {
            $this->fail('Access denied: ' . $e->getMessage());
        }
    }

    public function testShow(): void
    {
        $client = static::createClient();

        try {
            // Replace {id} with the ID of an existing user
            $userId = 1; // Replace with an actual user ID
            $client->request('GET', '/api/users/' . $userId);

            $this->assertResponseIsSuccessful();
            $this->assertJson($client->getResponse()->getContent());
        } catch (AccessDeniedException $e) {
            $this->fail('Access denied: ' . $e->getMessage());
        }
    }

    public function testNew(): void
    {
        $client = static::createClient();

        try {
            // Authenticate as a super admin
            $this->loginAsSuperAdmin($client);

            // Send a POST request to create a new user
            $response = $client->request('POST', '/api/users', [], [], [], json_encode([
                'name' => 'Test User',
                'role' => 'ROLE_USER',
                'company_id' => 1 // Replace with an existing company ID
            ]));

            $this->assertResponseStatusCodeSame(201);
            $this->assertJson($response->getContent());
        } catch (AccessDeniedException $e) {
            $this->fail('Access denied: ' . $e->getMessage());
        }
    }

    public function testDelete(): void
    {
        $client = static::createClient();

        try {
            // Authenticate as a super admin
            $this->loginAsSuperAdmin($client);

            // Replace {id} with the ID of an existing user
            $userId = 1; // Replace with an actual user ID
            $client->request('DELETE', '/api/users/' . $userId);

            $this->assertResponseIsSuccessful();
            $this->assertJson($client->getResponse()->getContent());
        } catch (AccessDeniedException $e) {
            $this->fail('Access denied: ' . $e->getMessage());
        }
    }

    private function loginAsSuperAdmin($client): void
    {
        // Simulate authentication by setting up session data
        $session = $client->getContainer()->get('session');
        $firewallName = 'main'; // Assuming your firewall name is 'main'

        // Replace this with the logic to retrieve the super admin user from your database
        $superAdminId = 1; // Replace with the ID of your super admin user
        $session->set('_security_' . $firewallName, serialize(['ROLE_SUPER_ADMIN', null, $superAdminId]));
        $session->save();
    }
}
