<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionCookieNameListener
{
    public function onKernelRequest(RequestEvent $event)
    {
        
        $request = $event->getRequest();

        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        if (!$session instanceof SessionInterface) {
            return;
        }

        if ($session->isStarted()) {
            return;
        }

        $firewallName = $request->attributes->get('_firewall_context');

        if ($firewallName === 'admin') {
            $session->setName('PHPSESSID_ADMIN');
        } else {
            $session->setName('PHPSESSID_USER');
        }
    }
}