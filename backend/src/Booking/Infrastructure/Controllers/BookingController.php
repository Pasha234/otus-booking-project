<?php

namespace App\Booking\Infrastructure\Controllers;

use App\Booking\Application\Query\FindBookingById\FindBookingByIdQuery;
use DateTimeImmutable;
use App\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Shared\Infrastructure\Controllers\BaseController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Booking\Infrastructure\Request\CreateBookingRequest;
use App\Booking\Application\Query\GetBookingList\GetBookingListQuery;
use App\Booking\Application\DTO\Basic\ResourceWithAvailableDTO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Booking\Application\Query\FindGroupById\FindGroupByIdQuery;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\Booking\Infrastructure\Request\GetAvailableResourcesRequest;
use App\Booking\Infrastructure\Request\GetBookingListRequest;
use App\Booking\Application\Command\CreateBooking\CreateBookingCommand;
use App\Booking\Application\Command\DeleteBooking\DeleteBookingCommand;
use App\Booking\Application\Command\UpdateBooking\UpdateBookingCommand;
use App\Booking\Application\DTO\FindGroupById\Response as FindGroupByIdResponse;
use App\Booking\Application\DTO\GetBookingList\BookingDTO;
use App\Booking\Application\Query\GetResourcesForGroupWithAvailableQuantity\GetResourcesForGroupWithAvailableQuantityQuery;
use App\Booking\Infrastructure\Request\UpdateBookingRequest;

#[Route('/booking-group/{group_id}/booking')]
class BookingController extends BaseController
{
    #[Route('/create', name: 'app_booking_create', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function create(string $group_id, #[CurrentUser] User $user): Response
    {
        /** @var FindGroupByIdResponse|null $group */
        $group = $this->queryBus->execute(new FindGroupByIdQuery($group_id, $user->getId()));

        if (!$group) {
            throw new NotFoundHttpException('Group not found');
        }

        return $this->render('booking/create.html.twig', [
            'errors' => [],
            'form_errors' => [],
            'group' => $group,
        ]);
    }

    #[Route('/get_available_resources', name: 'app_booking_get_available_resources', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function getAvailableResources(string $group_id, #[CurrentUser] User $user, Request $request): Response
    {
        return $this->handleApiRequest(function () use ($group_id, $user, $request) {
            $requestPayload = $this->getRequestPayload($request, GetAvailableResourcesRequest::class);
            $this->validateRequest($requestPayload);

            /** @var FindGroupByIdResponse|null $group */
            $group = $this->queryBus->execute(new FindGroupByIdQuery($group_id, $user->getId()));

            if (!$group) {
                throw new NotFoundHttpException('Group not found');
            }

            /** @var ResourceWithAvailableDTO[] $resources */
            $resources = $this->queryBus->execute(
                new GetResourcesForGroupWithAvailableQuantityQuery(
                    $group_id,
                    new DateTimeImmutable($requestPayload->start_at),
                    new DateTimeImmutable($requestPayload->end_at),
                    $requestPayload->current_booking_id,
                )
            );

            return $this->json([
                'resources' => $resources
            ]);
        });
    }

    #[Route('/create', name: 'app_booking_create_post', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createPost(string $group_id, Request $request, #[CurrentUser] User $user): Response
    {
        /** @var FindGroupByIdResponse|null $group */
        $group = $this->queryBus->execute(new FindGroupByIdQuery($group_id, $user->getId()));

        if (!$group) {
            throw new NotFoundHttpException('Group not found');
        }

        return $this->handleForm(function () use ($request, $group_id, $user) {
            $requestPayload = $this->getRequestPayload($request, CreateBookingRequest::class);
            $this->validateRequest($requestPayload);

            $resources = [];
            if (!empty($requestPayload->quantity)) {
                foreach ($requestPayload->quantity as $resourceId => $quantity) {
                    $quantity = (int)$quantity;
                    if ($quantity > 0) {
                        $resources[] = [
                            'id' => $resourceId,
                            'quantity' => $quantity,
                        ];
                    }
                }
            }

            $command = new CreateBookingCommand(
                $group_id,
                $user->getId(),
                $requestPayload->title,
                $requestPayload->description,
                new \DateTimeImmutable($requestPayload->start_at),
                new \DateTimeImmutable($requestPayload->end_at),
                $resources,
                $requestPayload->participants
            );

            $this->commandBus->execute($command);

            $this->addFlash('success', 'Booking created successfully!');
            return $this->redirectToRoute('app_booking_group_show', ['id' => $group_id]);
        }, 'booking/create.html.twig', ['group' => $group]);
    }

    #[Route('/get_bookings', name: 'app_booking_get_list', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function getBookings(string $group_id, #[CurrentUser] User $user, Request $request): Response
    {
        return $this->handleApiRequest(function () use ($group_id, $user, $request) {
            $requestPayload = $this->getRequestPayload($request, GetBookingListRequest::class);
            $this->validateRequest($requestPayload);

            /** @var FindGroupByIdResponse|null $group */
            $group = $this->queryBus->execute(new FindGroupByIdQuery($group_id, $user->getId()));
            if (!$group) {
                throw new NotFoundHttpException('Group not found');
            }

            $query = new GetBookingListQuery(
                $group_id,
                $requestPayload->start_at . ' 00:00:00',
                $requestPayload->end_at . ' 00:00:00',
                $user->getId(),
            );

            /** @var \App\Booking\Application\DTO\GetBookingList\BookingDTO[] $bookings */
            $bookings = $this->queryBus->execute($query);

            return $this->json($bookings);
        });
    }

    #[Route('/update/{booking_id}', name: 'app_booking_update', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function update(string $group_id, string $booking_id, #[CurrentUser] User $user): Response
    {
        /** @var FindGroupByIdResponse|null $group */
        $group = $this->queryBus->execute(new FindGroupByIdQuery($group_id, $user->getId()));

        if (!$group) {
            throw new NotFoundHttpException('Group not found');
        }

        /** @var BookingDTO|null $booking */
        $booking = $this->queryBus->execute(new FindBookingByIdQuery($booking_id));

        if (!$booking || $booking->author->id !== $user->getId()) {
            throw new NotFoundHttpException('Booking not found');
        }

        return $this->render('booking/update.html.twig', [
            'errors' => [],
            'form_errors' => [],
            'group' => $group,
            'booking' => $booking,
        ]);
    }

    #[Route('/update/{booking_id}', name: 'app_booking_update_post', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updatePost(string $group_id, string $booking_id, Request $request, #[CurrentUser] User $user): Response
    {
        /** @var FindGroupByIdResponse|null $group */
        $group = $this->queryBus->execute(new FindGroupByIdQuery($group_id, $user->getId()));

        if (!$group) {
            throw new NotFoundHttpException('Group not found');
        }

        /** @var BookingDTO|null $booking */
        $booking = $this->queryBus->execute(new FindBookingByIdQuery($booking_id));

        if (!$booking || $booking->author->id !== $user->getId()) {
            throw new NotFoundHttpException('Booking not found');
        }

        return $this->handleForm(function () use ($request, $group_id, $booking_id) {
            $requestPayload = $this->getRequestPayload($request, UpdateBookingRequest::class);
            $this->validateRequest($requestPayload);

            $rawPayload = $request->request->all();

            $resources = [];
            if (!empty($requestPayload->quantity)) {
                foreach ($requestPayload->quantity as $resourceId => $quantity) {
                    $quantity = (int)$quantity;
                    if ($quantity > 0) {
                        $resources[] = [
                            'id' => $resourceId,
                            'quantity' => $quantity,
                        ];
                    }
                }
            }

            $command = new UpdateBookingCommand(
                $booking_id,
                $requestPayload->title,
                $requestPayload->description,
                $requestPayload->start_at ? new DateTimeImmutable($requestPayload->start_at) : null,
                $requestPayload->end_at ? new DateTimeImmutable($requestPayload->end_at) : null,
                array_key_exists('quantity', $rawPayload) ? $resources : null,
                array_key_exists('participants', $rawPayload) ? $requestPayload->participants : null
            );

            $this->commandBus->execute($command);

            $this->addFlash('success', 'Booking updated successfully!');
            return $this->redirectToRoute('app_booking_group_show', ['id' => $group_id]);
        }, 'booking/update.html.twig', ['group' => $group, 'booking' => $booking]);
    }

    #[Route('/delete/{booking_id}', name: 'app_booking_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(string $group_id, string $booking_id, Request $request, #[CurrentUser] User $user): Response
    {
        /** @var FindGroupByIdResponse|null $group */
        $group = $this->queryBus->execute(new FindGroupByIdQuery($group_id, $user->getId()));

        if (!$group) {
            throw new NotFoundHttpException('Group not found');
        }

        /** @var BookingDTO|null $booking */
        $booking = $this->queryBus->execute(new FindBookingByIdQuery($booking_id));

        if (!$booking || $booking->author->id !== $user->getId()) {
            throw new NotFoundHttpException('Booking not found');
        }

        return $this->handleApiRequest(function () use ($request, $group_id, $booking, $user) {
            $command = new DeleteBookingCommand(
                $booking->id,
            );

            $this->commandBus->execute($command);

            return $this->json([
                'message' => 'Booking deleted successfully!'
            ]);
        });
    }

    protected function handleHandlerException(\Throwable $e, array &$formErrors, array &$errors, string $view, int &$code)
    {
        if ($e instanceof NotFoundInRepositoryException) {
            $errors[] = $e->getMessage();
            $code = Response::HTTP_NOT_FOUND;
        } else if ($e instanceof \DomainException) {
            $errors[] = $e->getMessage();
            $code = Response::HTTP_BAD_REQUEST;
        } else {
            $errors[] = 'An unexpected error occurred.';
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }
    }
}