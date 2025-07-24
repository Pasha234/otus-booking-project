<?php

namespace App\Tests\Integration\User\Controller;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?ORMExecutor $executor = null;
    private ?EntityManagerInterface $entityManager = null;
    private ?UserRepositoryInterface $userRepository = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepositoryInterface::class);

        $loader = new Loader();
        $loader->addFixture($container->get(UserFixtures::class));

        $this->executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_register_page_is_accessible(): void
    {
        $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
    }

    public function test_should_register_user_successfully(): void
    {
        // Arrange
        $faker = Factory::create();
        $email = $faker->email();
        $fullName = $faker->name();
        $password = 'password123';

        // Act
        $this->client->request('POST', '/register', [
            'email' => $email,
            'full_name' => $fullName,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        // Assert
        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();

        // $this->assert('.alert-success', 'Registration successful! Please log in.');

        $user = $this->userRepository->findOneBy(['email' => $email]);
        $this->assertNotNull($user);
        $this->assertEquals($fullName, $user->getFullName());
    }

    public function test_should_fail_registration_if_user_exists(): void
    {
        // Arrange: The user from UserFixtures already exists.
        $existingUserEmail = $this->executor->getReferenceRepository()->getReference('user', User::class)->getEmail();

        // Act
        $this->client->request('POST', '/register', [
            'email' => $existingUserEmail,
            'full_name' => 'Another Name',
            'password' => 'anotherpassword',
            'password_confirmation' => 'anotherpassword',
        ]);

        // Assert: Should not redirect, but re-render the form with an error.
        $this->assertResponseIsUnprocessable();
        $this->assertSelectorTextContains('.text-red-500', 'There is already an account with this email.');
    }

    public function test_should_fail_registration_with_invalid_data(): void
    {
        // Act
        $this->client->request('POST', '/register', [
            'email' => 'not-an-email',
            'full_name' => '',
            'password' => 'short',
        ]);

        // Assert
        $this->assertResponseIsUnprocessable();
        $this->assertSelectorTextContains('body', 'This value is not a valid email address.');
        $this->assertSelectorTextContains('body', 'This value should not be blank.');
    }

    public function test_login_page_is_accessible(): void
    {
        $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
    }

    public function test_should_login_successfully(): void
    {
        // Arrange: User is created by UserFixtures.
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);

        $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        // Act
        $this->client->submitForm('Login', [
            '_username' => $user->getEmail(),
            '_password' => 'test',
        ]);

        // Assert: Successful login redirects to the profile page.
        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
    }

    public function test_should_fail_login_with_bad_credentials(): void
    {
        // Arrange
        $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        // Act
        $this->client->submitForm('Login', [
            '_username' => 'test.user@test.com',
            '_password' => 'wrongpassword',
        ]);

        // Assert: Failed login redirects back to the login page.
        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.bg-red-100', 'Invalid credentials.');
    }

    public function test_should_logout_successfully(): void
    {
        // Arrange: First, log in the user.
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->request('GET', '/login');
        $this->client->submitForm('Login', [
            '_username' => $user->getEmail(),
            '_password' => 'test',
        ]);
        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();

        // Act: Logout.
        $this->client->request('GET', '/logout');

        // Assert: We are redirected to the login page.
        $this->assertResponseRedirects('/');
        $this->client->followRedirect();

        // Assert: We are logged out by trying to access a protected route.
        $this->client->request('GET', '/profile');
        $this->assertResponseRedirects('http://localhost/login');
    }
}