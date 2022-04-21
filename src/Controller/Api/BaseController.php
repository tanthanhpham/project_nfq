<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\CategoryRepository;
use App\Repository\GalleryRepository;
use App\Repository\OrderRepository;
use App\Repository\PaymentRepository;
use App\Repository\ProductItemRepository;
use App\Repository\ProductRepository;
use App\Repository\SizeRepository;
use App\Repository\UserRepository;
use App\Service\CartService;
use App\Service\GetUserInfo;
use App\Service\OrderService;
use App\Service\PaymentService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;

class BaseController extends AbstractFOSRestController
{
    public const PATH = '/uploads/images/';

    public const ITEM_PAGE_LIMIT = 10;
    public const ITEM_PAGE_NUMBER = 1;

    public const STATUS_APPROVED = 1;
    public const STATUS_DELIVERY = 2;
    public const STATUS_CANCELED = 3;
    public const STATUS_COMPLETED = 4;
    public const STATUS_PENDING_PAYMENT = 5;

    public const METHOD_CAST = 'cast';
    public const METHOD_PAYPAL = 'paypal';

    /**
     * @var CartRepository
     */
    protected $cartRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var User|null
     */
    protected $userLoginInfo;

    /**
     * @var SizeRepository
     */
    protected $sizeRepository;

    /**
     * @var ProductItemRepository
     */
    protected $productItemRepository;

    /**
     * @var GalleryRepository
     */
    protected $galleryRepository;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var ContainerBagInterface
     */
    protected $containerBag;

    /**
     * @var PaymentService
     */
    protected $paymentService;

    /**
     * @var string
     */
    protected $domain;
    /**
     * @param CartRepository $cartRepository
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param OrderRepository $orderRepository
     * @param UserRepository $userRepository
     * @param LoggerInterface $logger
     * @param GetUserInfo $userLogin
     * @param SizeRepository $sizeRepository
     * @param ProductItemRepository $productItemRepository
     * @param GalleryRepository $galleryRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param CartService $cartService
     * @param ContainerBagInterface $containerBag
     * @param OrderService $orderService
     * @param PaymentRepository $paymentRepository
     * @param PaymentService $paymentService
     */
    public function __construct(
        CartRepository           $cartRepository,
        CategoryRepository       $categoryRepository,
        ProductRepository        $productRepository,
        OrderRepository          $orderRepository,
        UserRepository           $userRepository,
        LoggerInterface          $logger,
        GetUserInfo              $userLogin,
        SizeRepository           $sizeRepository,
        ProductItemRepository    $productItemRepository,
        GalleryRepository        $galleryRepository,
        EventDispatcherInterface $eventDispatcher,
        CartService              $cartService,
        ContainerBagInterface    $containerBag,
        OrderService             $orderService,
        PaymentRepository        $paymentRepository,
        PaymentService           $paymentService
    ) {
        $this->cartService = $cartService;
        $this->cartRepository = $cartRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->sizeRepository = $sizeRepository;
        $this->productItemRepository = $productItemRepository;
        $this->galleryRepository = $galleryRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->containerBag = $containerBag;
        $this->orderService = $orderService;
        $this->paymentRepository = $paymentRepository;
        $this->paymentService = $paymentService;
        $this->domain = $this->containerBag->get('app.domain');
    }

    /**
     * @param array $data
     * @param string $group
     * @return array
     */
    protected function transferDataGroup(array $data, string $group): array
    {
        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize(
            $data,
            'json',
            SerializationContext::create()->setGroups([$group])
        );

        return $serializer->deserialize($convertToJson, 'array', 'json');
    }

    /**
     * @param Form $form
     * @return array
     */
    protected function getFormErrorMessage(Form $form): array
    {
        $errorMessage = [];

        foreach ($form as $child) {
            /** @var FormInterface $child */
            if ($child->isSubmitted() && $child->isValid()) {
                continue;
            }

            $errorList = $child->getErrors(true, true);
            if (0 === count($errorList)) {
                continue;
            } else {
                $firstErrorMessage = '';
                foreach ($errorList as $error) {
                    $firstErrorMessage = $error->getMessage();
                    break;
                }

                $errorMessage[$child->getName()] = $firstErrorMessage;
            }
        }

        return $errorMessage;
    }
}
