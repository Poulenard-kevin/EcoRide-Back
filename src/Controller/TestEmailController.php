<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class TestEmailController extends AbstractController
{
    #[Route('/test-email', name: 'app_test_email')]
    public function sendTestEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('hello@demomailtrap.co')
            ->to('poulenard.kevin@gmail.com') 
            ->subject('Test email Mailtrap')
            ->text('Ceci est un test d\'envoi d\'email via Mailtrap.');

        $mailer->send($email);

        return new Response('Email test envoyé via Mailtrap');
    }
}