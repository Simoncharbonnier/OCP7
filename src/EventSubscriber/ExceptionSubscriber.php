<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $data = [
            'status' => $exception instanceof HttpException ? $exception->getStatusCode() : 500,
            'message' => $exception->getMessage()
        ];

        if ($exception instanceof HttpException) {
            if ($exception->getStatusCode() === 404) {
                $data['message'] = "L'identifiant ne correspond à aucun élément.";
            } else if ($exception->getStatusCode() === 403) {
                $data['message'] = "Vous n'avez pas les droits pour réaliser cette requête.";
            }
        }

        $event->setResponse(new JsonResponse($data));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
