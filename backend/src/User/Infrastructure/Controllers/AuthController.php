<?php

namespace App\User\Infrastructure\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\User\Infrastructure\Request\RegistrationRequest;
use App\Shared\Infrastructure\Controllers\BaseController;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Application\Command\CreateUser\CreateUserCommand;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends BaseController
{
    #[Route('/register', name: 'app_register', methods: ['GET'])]
    public function register(): Response
    {
        return $this->render('auth/register.html.twig', [
            'errors' => [],
            'form_errors' => [],
        ]);
    }

    #[Route('/register', name: 'app_register_post', methods: ['POST'])]
    public function registerPost(
        Request $request,
    )
    {
        return $this->handleForm(function() use ($request) {
            $requestPayload = $this->getRequestPayload($request, RegistrationRequest::class);
            $this->validateRequest($requestPayload);

            $command = new CreateUserCommand(
                $requestPayload->email,
                $requestPayload->full_name,
                $requestPayload->password
            );

            $this->commandBus->execute($command);

            $this->addFlash('success', 'Registration successful! Please log in.');
            return $this->redirectToRoute('app_login');
        }, 'auth/register.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If the user is already logged in, redirect them to the profile page.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Controller can be empty, as the security logout listener will catch this route and handle the logout.
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    protected function handleHandlerException(\Throwable $e, array &$formErrors, array &$errors, string $view, int &$code)
    {
        if ($e instanceof UserAlreadyExistsException) {
            $formErrors['email'] = $e->getMessage();
        } else {
            $errors[] = 'An unexpected error occurred during registration.';
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }
    }
}