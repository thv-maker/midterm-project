<?php

namespace App\Controller;

use App\Entity\Dashboard;
use App\Entity\Product;
use App\Entity\Stock;
use App\Form\DashboardType;
use App\Form\ProductType;
use App\Form\StockType;
use App\Repository\DashboardRepository;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
final class DashboardController extends AbstractController
{
    #[Route(name: 'app_dashboard_index', methods: ['GET'])]
    public function index(DashboardRepository $dashboardRepository): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'dashboards' => $dashboardRepository->findAll(),
        ]);
    }

    #[Route('/products', name: 'app_dashboard_products')]
    public function products(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/products/new', name: 'app_dashboard_product_new', methods: ['GET', 'POST'])]
    public function newProduct(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_products', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/products/{id}', name: 'app_dashboard_product_show', methods: ['GET'])]
    public function showProduct(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/products/{id}/edit', name: 'app_dashboard_product_edit', methods: ['GET', 'POST'])]
    public function editProduct(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_products', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

   #[Route('/stocks', name: 'app_dashboard_stocks')]
public function stocks(StockRepository $stockRepository): Response
{
    $stocks = $stockRepository->findAll();

    return $this->render('stock/index.html.twig', [
        'stocks' => $stocks,
    ]);
}

#[Route('/stocks/new', name: 'app_dashboard_stock_new', methods: ['GET', 'POST'])]
public function newStock(Request $request, EntityManagerInterface $entityManager): Response
{
    $stock = new Stock();
    $form = $this->createForm(StockType::class, $stock);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($stock);
        $entityManager->flush();

        return $this->redirectToRoute('app_dashboard_stocks', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('stock/new.html.twig', [
        'stock' => $stock,
        'form' => $form,
    ]);
}

#[Route('/stocks/{id}', name: 'app_dashboard_stock_show', methods: ['GET'])]
public function showStock(Stock $stock): Response
{
    return $this->render('stock/show.html.twig', [
        'stock' => $stock,
    ]);
}

#[Route('/stocks/{id}/edit', name: 'app_dashboard_stock_edit', methods: ['GET', 'POST'])]
public function editStock(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
{
    // ...
}

    #[Route('/new', name: 'app_dashboard_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $dashboard = new Dashboard();
        $form = $this->createForm(DashboardType::class, $dashboard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($dashboard);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/new.html.twig', [
            'dashboard' => $dashboard,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_show', methods: ['GET'])]
    public function show(Dashboard $dashboard): Response
    {
        return $this->render('dashboard/show.html.twig', [
            'dashboard' => $dashboard,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dashboard_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dashboard $dashboard, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DashboardType::class, $dashboard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/edit.html.twig', [
            'dashboard' => $dashboard,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_delete', methods: ['POST'])]
    public function delete(Request $request, Dashboard $dashboard, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$dashboard->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($dashboard);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_index', [], Response::HTTP_SEE_OTHER);
    }
}