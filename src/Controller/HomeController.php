<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        try {
            return $this->render('home/index.html.twig');
        } catch (\Throwable) {
            return new Response('Service is running. Try /api for API routes.', Response::HTTP_OK);
        }
    }

    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function health(): Response
    {
        return $this->json([
            'status' => 'ok',
            'service' => 'midterm-project',
        ]);
    }

    #[Route('/about-us', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route('/contact-us', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer, \Psr\Log\LoggerInterface $logger): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));
            $email = trim((string) $request->request->get('email', ''));
            $subject = trim((string) $request->request->get('subject', 'Contact Inquiry'));
            $message = trim((string) $request->request->get('message', ''));

            $logger->info('Contact form submission received', [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
            ]);

            if ($name === '' || $email === '' || $message === '') {
                $logger->warning('Contact form: validation failed - missing required fields');
                $this->addFlash('contact_error', 'Please complete all required fields.');
                return new RedirectResponse($this->generateUrl('app_contact'));
            }

            try {
                $contactEmail = (new Email())
                    ->from(new Address('carpediemcafe6@gmail.com', 'Carpe Diem Contact Form'))
                    ->replyTo($email)
                    ->to('carpediemcafe6@gmail.com')
                    ->subject('[Contact] ' . $subject)  
                    ->html(sprintf(
                        '<h2>New Contact Inquiry</h2><p><strong>Name:</strong> %s</p><p><strong>Email:</strong> %s</p><p><strong>Subject:</strong> %s</p><p><strong>Message:</strong><br>%s</p>',
                        htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
                        nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'))
                    ));

                $logger->info('Attempting to send contact email', ['to' => 'carpediemcafe6@gmail.com']);
                $mailer->send($contactEmail);
                $logger->info('Contact email sent successfully');
                $this->addFlash('contact_success', 'Message sent successfully. We will reply within 24 hours.');
            } catch (\Throwable $e) {
                $logger->error('Contact form email error', [
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
                $this->addFlash('contact_error', 'Unable to send your message right now. Please try again later.');
            }

            return new RedirectResponse($this->generateUrl('app_contact'));
        }

        return $this->render('home/contact.html.twig');
    }

    #[Route('/admins', name: 'app_admins')]
    public function admins(): Response
    {
        return $this->render('home/admins.html.twig');
    }

    #[Route('/our-products', name: 'app_our_products')]
    public function ourProducts(): Response
    {
        return $this->render('home/our_products.html.twig');
    }

    #[Route('/our-things', name: 'app_our_things')]
    public function ourThings(): Response
    {
        return $this->render('home/our_things.html.twig');
    }

    #[Route('/why-choose-us', name: 'app_why_choose')]
    public function whyChoose(): Response
    {
        return $this->render('home/why_choose.html.twig');
    }
}