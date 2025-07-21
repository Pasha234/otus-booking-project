<?php

namespace App\Booking\Infrastructure\Controllers;

use App\User\Domain\Entity\User;
use App\Booking\Domain\Entity\Group;
use App\User\Domain\Exception\UserAlreadyHasInvitationException;
use App\User\Domain\Exception\UserAlreadyInGroupException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Shared\Infrastructure\Controllers\BaseController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\User\Application\Query\SearchUsers\SearchUsersQuery;
use App\Booking\Infrastructure\Request\InviteUserToGroupRequest;
use App\Booking\Infrastructure\Request\CreateBookingGroupRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Booking\Application\Query\FindGroupById\FindGroupByIdQuery;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\User\Application\Query\FindUsersByEmail\FindUsersByEmailQuery;
use App\Booking\Application\Command\InviteUserToGroup\InviteUserToGroupCommand;
use App\Booking\Application\DTO\FindGroupById\Response as FindGroupByIdResponse;
use App\Booking\Application\Command\CreateBookingGroup\CreateBookingGroupCommand;
use App\Booking\Application\Query\SearchUsersForInvitations\SearchUsersForInvitationsQuery;

#[Route('/booking-group')]
class BookingGroupController extends BaseController
{
    #[Route('/create', name: 'app_booking_group_create', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function create(): Response
    {
        return $this->render('booking_group/create.html.twig', [
            'errors' => [],
            'form_errors' => [],
        ]);
    }

    #[Route('/create', name: 'app_booking_group_create_post', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createPost(Request $request): Response
    {
        return $this->handleForm(function() use ($request) {
            $requestPayload = $this->getRequestPayload($request, CreateBookingGroupRequest::class);
            $this->validateRequest($requestPayload);

            $command = new CreateBookingGroupCommand(
                $requestPayload->name,
                $requestPayload->description,
                $this->getUser()->getUserIdentifier()
            );

            $this->commandBus->execute($command);

            $this->addFlash('success', 'Booking group created successfully!');
            return $this->redirectToRoute('app_home');
        }, 'booking_group/create.html.twig');
    }

    #[Route('/{id}', name: 'app_booking_group_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(string $id, #[CurrentUser] ?User $user): Response
    {
        $query = new FindGroupByIdQuery(
            $id,
            $user?->getId()
        );

        /** @var FindGroupByIdResponse|null $group */
        $group = $this->queryBus->execute($query);

        if (!$group) {
            throw new NotFoundHttpException('Group not found');
        }

        return $this->render('booking_group/show.html.twig', [
            'booking_group' => $group,
        ]);
    }

    #[Route('/{id}/settings', name: 'app_booking_group_settings', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function settings(string $id, #[CurrentUser] ?User $user): Response
    {
        $query = new FindGroupByIdQuery(
            $id,
            $user?->getId()
        );

        /** @var FindGroupByIdResponse|null $group */
        $group = $this->queryBus->execute($query);

        if (!$group) {
            throw new NotFoundHttpException('Group not found');
        }

        return $this->render('booking_group/show.html.twig', [
            'booking_group' => $group,
        ]);
    }

    #[Route('/{id}/search-users', name: 'app_booking_group_search_users_for_invitation', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function searchUsers(Request $request, string $id): Response
    {
        $groupQuery = new FindGroupByIdQuery($id);
        /** @var FindGroupByIdResponse|null $group */
        $group = $this->queryBus->execute($groupQuery);

        if (!$group || $group->owner->email !== $this->getUser()->getUserIdentifier()) {
            return $this->json(['error' => 'Access Denied'], Response::HTTP_FORBIDDEN);
        }

        $query = $request->query->get('q', '');
        if (mb_strlen($query) < 2) {
            return $this->json([]);
        }

        $usersQuery = new SearchUsersForInvitationsQuery(
            $group->id, 
            $query,
            10
        );
        /** @var \App\Booking\Application\DTO\Basic\InvitationUserDTO[] $users */
        $users = $this->queryBus->execute($usersQuery);

        return $this->json(array_values($users));
    }

    #[Route('/{id}/invite', name: 'app_booking_group_invite_user', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function inviteUser(Request $request, string $id, #[CurrentUser] ?User $user): Response
    {
        return $this->handleApiRequest(function() use ($request, $id, $user){
            $requestPayload = $this->getRequestPayload($request, InviteUserToGroupRequest::class);
            $this->validateRequest($requestPayload);

            $command = new InviteUserToGroupCommand(
                $id,
                $requestPayload->email,
                $user->getId(),
            );

            $this->commandBus->execute($command);

            return $this->json(['message' => 'Invitation sent successfully.']);
        });
    }

    protected function handleHandlerException(\Throwable $e, array &$formErrors, array &$errors, string $view, int &$code)
    {
        if ($e instanceof NotFoundInRepositoryException) {
            $errors[] = $e->getMessage();
            $code = Response::HTTP_NOT_FOUND;
        } else if ($e instanceof AccessDeniedException) {
            $errors[] = $e->getMessage();
            $code = Response::HTTP_FORBIDDEN;
        } else if ($e instanceof UserAlreadyInGroupException) {
            $errors[] = $e->getMessage();
        } else if ($e instanceof UserAlreadyHasInvitationException){
            $errors[] = $e->getMessage();
        } else {
            $errors = ['An unexpected error occurred.'];
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }
    }
}