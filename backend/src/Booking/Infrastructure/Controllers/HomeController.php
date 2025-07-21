<?php

namespace App\Booking\Infrastructure\Controllers;

use App\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Shared\Infrastructure\Controllers\BaseController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\Booking\Application\Query\FindBookingGroupsByUser\FindBookingGroupsByUserQuery;

class HomeController extends BaseController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user): Response
    {
        $bookingGroups = [];
        if ($user) {
            $bookingGroups = $this->queryBus->execute(
                new FindBookingGroupsByUserQuery($user->getId())
            );
        }

        return $this->render('home/index.html.twig', [
            'booking_groups' => $bookingGroups,
        ]);
    }

    protected function handleHandlerException(\Throwable $e, array &$formErrors, array &$errors, string $view, int &$code)
    {
        if ($e instanceof NotFoundInRepositoryException) {
            $errors[] = $e->getMessage();
            $code = Response::HTTP_NOT_FOUND;
        } else {
            $errors = ['An unexpected error occurred.'];
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }
    }
}