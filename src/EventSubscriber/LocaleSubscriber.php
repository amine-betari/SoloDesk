<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class LocaleSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED = ['fr', 'en', 'ar'];

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasSession()) {
            return;
        }

        $sessionLocale = $request->getSession()->get('_locale');
        if ($sessionLocale && in_array($sessionLocale, self::SUPPORTED, true)) {
            $request->setLocale($sessionLocale);
        }
    }
}
