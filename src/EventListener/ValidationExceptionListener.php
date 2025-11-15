<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: 'kernel.exception')]
class ValidationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (
            !$throwable instanceof UnprocessableEntityHttpException &&
            !$throwable instanceof BadRequestHttpException
        ) {
            return;
        }

        $previous = $throwable->getPrevious();
        if (!$previous instanceof ValidationFailedException) {
            return;
        }

        $violations = $previous->getViolations();
        $errors = [];

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        $response = new JsonResponse(
            [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
                'timestamp' => date('Y-m-d H:i:s')
            ],
            Response::HTTP_BAD_REQUEST
        );

        $event->setResponse($response);
    }
}
