<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\StockRepository;
use App\Service\ActivityLoggerService;
use App\Service\StockMercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/stocks')]
#[IsGranted('ROLE_STAFF')]
class StockController extends AbstractController
{
    public function __construct(
        private StockMercurePublisher $stockPublisher,
        private ActivityLoggerService $activityLogger,
    ) {}

    #[Route('', name: 'app_dashboard_stocks', methods: ['GET'])]
    public function index(StockRepository $stockRepository): Response
    {
        return $this->render('stock/index.html.twig', [
            'stocks' => $stockRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_dashboard_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stock = new Stock();
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stock->setCreatedBy($this->getUser());
            $stock->setLastUpdated(new \DateTime());
            $entityManager->persist($stock);
            $entityManager->flush();

            $this->stockPublisher->publishCreated($stock);
            $this->activityLogger->logCreate(
                'Stock',
                (int) $stock->getId(),
                (string) ($stock->getProduct()?->getName() ?? 'Unknown product')
            );

            $this->addFlash('success', 'Stock created successfully!');

            return $this->redirectToRoute('app_dashboard_stocks', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stock/new.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/row/{id}', name: 'app_dashboard_stock_row', methods: ['GET'])]
    public function row(Stock $stock): Response
    {
        return $this->render('stock/_row.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/stats', name: 'app_dashboard_stock_stats', methods: ['GET'])]
    public function stats(StockRepository $stockRepository): JsonResponse
    {
        $stocks = $stockRepository->findAll();
        $outCount = 0;
        $lowCount = 0;
        $healthyCount = 0;

        foreach ($stocks as $stock) {
            $quantity = (int) $stock->getQuantity();
            $reorderLevel = (int) $stock->getReorderLevel();

            if ($quantity === 0) {
                ++$outCount;
            } elseif ($quantity <= $reorderLevel) {
                ++$lowCount;
            } else {
                ++$healthyCount;
            }
        }

        return $this->json([
            'total' => count($stocks),
            'out' => $outCount,
            'low' => $lowCount,
            'healthy' => $healthyCount,
        ]);
    }

    #[Route('/low-stock-panel', name: 'app_dashboard_stock_low_stock_panel', methods: ['GET'])]
    public function lowStockPanel(StockRepository $stockRepository): Response
    {
        return $this->render('dashboard/_low_stock_panel.html.twig', [
            'lowStockItems' => $stockRepository->findLowStockItems(5),
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_stock_show', methods: ['GET'])]
    public function show(Stock $stock): Response
    {
        return $this->render('stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dashboard_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stock->setLastUpdated(new \DateTime());
            $entityManager->flush();

            $this->stockPublisher->publishUpdated($stock);
            $this->activityLogger->logUpdate(
                'Stock',
                (int) $stock->getId(),
                (string) ($stock->getProduct()?->getName() ?? 'Unknown product')
            );

            $this->addFlash('success', 'Stock updated successfully!');

            return $this->redirectToRoute('app_dashboard_stocks', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stock/edit.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_delete', methods: ['POST'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$stock->getId(), $request->request->get('_token'))) {
            $stockId = (int) $stock->getId();
            $productId = $stock->getProduct()?->getId();
            $productName = (string) ($stock->getProduct()?->getName() ?? 'Unknown product');

            $entityManager->remove($stock);
            $entityManager->flush();

            $this->stockPublisher->publishDeleted($stockId, $productId);
            $this->activityLogger->logDelete('Stock', $stockId, $productName);

            $this->addFlash('success', 'Stock deleted successfully!');
        }

        return $this->redirectToRoute('app_dashboard_stocks', [], Response::HTTP_SEE_OTHER);
    }
}
