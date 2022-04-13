<?php

namespace App\Controller\Api\Admin;

use App\Controller\Api\BaseController;
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
use function PHPUnit\Framework\isNull;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class ReportController extends BaseController
{
    /**
     * @Rest\Post("/admin/reports")
     * @return Response
     */
    public function getReport(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);

        $fromDate = new \DateTime($requestData['fromDate']);
        $toDate = $requestData['toDate'] . ' 23:59:59.999999';
        $toDate = new \DateTime($toDate) ;

        if ($fromDate > $toDate) {
            return $this->handleView($this->view(['error' => 'Date is invalid'], Response::HTTP_BAD_REQUEST));
        }
        $report = [];
        $report['totalProduct'] = count($this->productRepository->findBy(['deletedAt' => null]));
        $report['totalRevenue'] = $this->orderRepository->getRevenue($fromDate, $toDate);
        $report['totalRevenue'] = (isNull($report['totalRevenue']))? 0 : $report['totalRevenue'];
        $report['totalOrder'] = count($this->orderRepository->findBy(['deletedAt' => null]));
        $users = $this->userRepository->findByConditions(['deletedAt' => null, 'roles' => 'ROLE_USER']);
        $report['totalUser'] = $users['total'];

        $approvedOrder = $this->orderRepository->findByConditions(['deletedAt' => null,
            'status' => self::STATUS_APPROVED, 'fromDate' => $fromDate, 'toDate' => $toDate]);
        $deliveryOrder = $this->orderRepository->findByConditions(['deletedAt' => null,
            'status' => self::STATUS_DELIVERY, 'fromDate' => $fromDate, 'toDate' => $toDate]);
        $cancelOrder = $this->orderRepository->findByConditions(['deletedAt' => null,
            'status' => self::STATUS_CANCELED, 'fromDate' => $fromDate, 'toDate' => $toDate]);
        $completedOrder = $this->orderRepository->findByConditions(['deletedAt' => null,
            'status' => self::STATUS_COMPLETED, 'fromDate' => $fromDate, 'toDate' => $toDate]);

        $report['order']['approved'] = $approvedOrder['total'];
        $report['order']['delivery'] = $deliveryOrder['total'];
        $report['order']['cancel'] = $cancelOrder['total'];
        $report['order']['completed'] = $completedOrder['total'];

        return $this->handleView($this->view($report, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/admin/reports/chart")
     * @return Response
     */
    public function getDataForChart(): Response
    {
        $data = $this->orderRepository->getChart();

        return $this->handleView($this->view($data, Response::HTTP_OK));
    }
}
