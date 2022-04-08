<?php

namespace App\Controller\Api\Admin;

use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Entity\ProductItem;
use App\Event\OrderEvent;
use App\Form\OrderType;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductItemRepository;
use App\Repository\ProductRepository;
use App\Service\GetUserInfo;
use App\Service\PdfService;
use Dompdf\Dompdf;
use Dompdf\Options;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class ReportController extends AbstractFOSRestController
{
    public const STATUS_PENDING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_CANCELED = 3;
    public const STATUS_COMPLETED = 4;

    private $purchaseOrderRepository;
    private $productRepository;

    public function __construct(OrderRepository $purchaseOrderRepository, ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * @Rest\Get("/admin/reports")
     * @return Response
     */
    public function getReport(): Response
    {
        $report = [];
        $report['total'] = count($this->productRepository->findBy(['deletedAt' => null]));
        $report['totalRevenue'] = $this->purchaseOrderRepository->getRevenue();
        $pendingOrder = $this->purchaseOrderRepository->findBy(['deletedAt' => null, 'status' => self::STATUS_PENDING]);
        $approvedOrder = $this->purchaseOrderRepository->findBy(['deletedAt' => null, 'status' => self::STATUS_APPROVED]);
        $report['order']['pending'] = count($pendingOrder);
        $report['order']['approved'] = count($approvedOrder);

        return $this->handleView($this->view($report, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/admin/reports/chart")
     * @return Response
     */
    public function getDataForChart(): Response
    {
        $data = $this->purchaseOrderRepository->getChart();

        return $this->handleView($this->view($data, Response::HTTP_OK));
    }
}
