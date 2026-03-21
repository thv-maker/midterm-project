<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/about-us', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route('/contact-us', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(): Response
    {
        // Normally a contact form would process submission here.
        // For now, display the same page regardless of POST.
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