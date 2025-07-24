<?php

namespace App\Booking\Infrastructure\Controllers;

use App\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Shared\Infrastructure\Controllers\BaseController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Booking\Infrastructure\Request\CreateResourceRequest;
use App\Booking\Application\Exception\ResourceExistsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Booking\Application\Query\FindGroupById\FindGroupByIdQuery;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\Booking\Application\Command\CreateResource\CreateResourceCommand;
use App\Booking\Application\DTO\FindGroupById\Response as FindGroupByIdResponse;

#[Route('/booking-group/{group_id}/resource')]
class ResourceController extends BaseController
{
    #[Route('/create', name: 'app_resource_create', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function create(string $group_id, #[CurrentUser] ?User $user): Response
    {
        /** @var FindGroupByIdResponse */
        $group = $this->queryBus->execute(new FindGroupByIdQuery(
            $group_id,
            $user?->getId(),
        ));

        if (!$group || $group->owner->id != $user->getId()) {
            throw $this->createNotFoundException();
        }

        return $this->render('resource/create.html.twig', [
            'errors' => [],
            'form_errors' => [],
            'group_id' => $group_id,
        ]);
    }

    #[Route('/create', name: 'app_resource_create_post', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createPost(string $group_id, Request $request, #[CurrentUser] ?User $user): Response
    {
        $group = $this->queryBus->execute(new FindGroupByIdQuery(
            $group_id,
            $user?->getId(),
        ));

        if (!$group || $group->owner->id != $user->getId()) {
            throw new NotFoundHttpException('Group not found');
        }

        return $this->handleForm(function() use ($request, $group_id) {
            $requestPayload = $this->getRequestPayload($request, CreateResourceRequest::class);
            $this->validateRequest($requestPayload);

            $command = new CreateResourceCommand($group_id, $requestPayload->name, (int)$requestPayload->quantity);

            $this->commandBus->execute($command);

            $this->addFlash('success', 'Resource created successfully!');
            return $this->redirectToRoute('app_booking_group_show', ['id' => $group_id]);
        }, 'resource/create.html.twig', ['group_id' => $group_id]);
    }

    public function handleHandlerException(\Throwable $e, array &$formErrors, array &$errors, string $view, int &$code)
    {
        if ($e instanceof ResourceExistsException) {
            $errors[] = 'Resource with given name already exists in this group';
        } else if ($e instanceof NotFoundInRepositoryException) {
            $errors[] = $e->getMessage();
            $code = Response::HTTP_NOT_FOUND;
        } else {
            $errors[] = 'An unexpected error occured';
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }
    }
}