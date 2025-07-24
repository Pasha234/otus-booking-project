<?php

namespace App\Shared\Infrastructure\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Infrastructure\Exception\ValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

abstract class BaseController extends AbstractController
{
    public function __construct(
        protected CommandBusInterface $commandBus,
        protected QueryBusInterface $queryBus,
        protected SerializerInterface $serializer,
        protected ValidatorInterface $validator,
    ) {}

    /**
     * @template T
     * 
     * @param Request $request
     * @param class-string<T> $dtoRequestClass
     * 
     * @return T
     */
    protected function getRequestPayload(Request $request, string $dtoRequestClass): object
    {
        return $this->serializer->deserialize(json_encode($request->getPayload()->all()), $dtoRequestClass, 'json');
    }

    protected function validateRequest($requestPayload): void
    {
        $validationErrors = $this->validator->validate($requestPayload);
        
        if (count($validationErrors) > 0) {
            throw new ValidationException($requestPayload, $validationErrors);
        }
    }
    
    protected function handleForm(callable $callback, string $view, array $additionalVars = []): Response
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            return $this->render($view, $additionalVars + [
                'errors' => [],
                'form_errors' => (object) $e->getFormattedErrors(),
            ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        } catch (HandlerFailedException $e) {
            $errors = [];
            $formErrors = [];
            $code = Response::HTTP_UNPROCESSABLE_ENTITY;

            foreach ($e->getWrappedExceptions() as $previous) {
                $this->handleHandlerException($previous, $formErrors, $errors, $view, $code);
            }

            return $this->render($view, $additionalVars + [
                'errors' => $errors,
                'form_errors' => (object) $formErrors,
            ], new Response('', $code));
        } catch (\Exception $e) {
            $errors = [];
            $errors[] = 'An unexpected error occurred during form submission.';

            return $this->render($view, $additionalVars + [
                'errors' => $errors,
                'form_errors' => [],
            ], new Response('', Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    protected function handleHandlerException(\Throwable $e, array &$formErrors, array &$errors, string $view, int &$code) 
    {
        $errors = ['An unexpected error occurred during form submission.'];
        $code = Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    protected function handleApiRequest(callable $callback): Response
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            return $this->json([
                'error' => implode(', ', $e->getFormattedErrors()),
                'form_errors' => $e->getFormattedErrors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (HandlerFailedException $e) {
            $errors = [];
            $formErrors = [];
            $code = Response::HTTP_UNPROCESSABLE_ENTITY;

            foreach ($e->getWrappedExceptions() as $previous) {
                $this->handleHandlerException($previous, $formErrors, $errors, '', $code);
            }

            return $this->json([
                'error' => implode(', ', $errors),
                'form_errors' => $formErrors,
            ], $code);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            // You should probably log the exception here
            return $this->json(['error' => 'An unexpected error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}