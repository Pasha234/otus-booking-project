<?php

namespace App\User\Infrastructure\Controllers;

use App\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Shared\Infrastructure\Controllers\BaseController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\Booking\Application\Query\GetUserInvitations\GetUserInvitationsQuery;
use App\User\Application\Command\SetStatusForInvitation\SetStatusForInvitationCommand;

class ProfileController extends BaseController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        return $this->render('auth/profile.html.twig', ['user' => $this->getUser()]);
    }

    #[Route('/profile/invitations', name: 'app_profile_invitations', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function invitations(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $query = new GetUserInvitationsQuery($user->getUserIdentifier());
        $invitations = $this->queryBus->execute($query);

        return $this->render('profile/invitations.html.twig', [
            'invitations' => $invitations,
        ]);
    }

    #[Route('/profile/invitations/{id}/accept', name: 'app_profile_accept_invitation', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function acceptInvitation(string $id, #[CurrentUser] ?User $user): Response
    {
        return $this->handleApiRequest(function() use ($id, $user){
            $command = new SetStatusForInvitationCommand(
                $id,
                $user->getId(),
                true,
            );

            $this->commandBus->execute($command);

            return $this->json(['message' => 'Invitation accepted.']);
        });
    }

    #[Route('/profile/invitations/{id}/decline', name: 'app_profile_decline_invitation', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function declineInvitation(string $id, #[CurrentUser] ?User $user): Response
    {
        return $this->handleApiRequest(function() use ($id, $user){
            $command = new SetStatusForInvitationCommand(
                $id,
                $user->getId(),
                false,
            );

            $this->commandBus->execute($command);

            return $this->json(['message' => 'Invitation declined.']);
        });
    }

    protected function handleHandlerException(\Throwable $e, array &$formErrors, array &$errors, string $view, int &$code)
    {
        if ($e instanceof NotFoundInRepositoryException) {
            $errors[] = $e->getMessage();
            $code = Response::HTTP_NOT_FOUND;
        } else {
            $errors[] = 'An unexpected error occurred.';
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }
    }
}