<?php

namespace App\Command;

use App\Repository\OrderRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

//#[AsCommand(
//    name: 'report:revenue',
//    description: 'Add a short description for your command',
//)]
class ReportRevenueCommand extends Command
{
    protected static $defaultName = 'report:revenue';
    protected static $defaultDescription = '(Admin) Export information of order to CSV file.';

    /** @var OrderRepository */
    private $orderRepository;

    /** @var LoggerInterface */
    private $logger;

    private const STATUS_DEFAULT = [
        1 => 'PENDING',
        2 => 'APPROVED',
        3 => 'CANCELED',
        4 => 'COMPLETED'
    ];

    public function __construct(
        OrderRepository $orderRepository,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;

        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->addArgument('status', InputArgument::OPTIONAL, 'Status of order')
            ->addArgument('fromDate', InputArgument::OPTIONAL, 'Date from (yyyy-MM-dd)')
            ->addArgument('toDate', InputArgument::OPTIONAL, 'Date to (yyyy-MM-dd)')
            ->addOption('name', null, InputOption::VALUE_NONE, 'Specific CSV file name');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $arguments = [];
            $arguments['status'] = $input->getArgument('status');
            $arguments['fromDate'] = $input->getArgument('fromDate');
            $arguments['toDate'] = $input->getArgument('toDate');
            $listOrderPurchase = $this->orderRepository->getDataForReport($arguments);

            $orderExportData = [];
            if ($listOrderPurchase) {
                foreach ($listOrderPurchase as $order) {
                    $orderItems = $order->getOrderItems();
                    foreach ($orderItems as $item) {
                        $orderItem = [];
                        $orderItem['id'] = $order->getId();
                        $orderItem['date_order'] = $order->getCreatedAt()->format('Y-m-d H:i:s');
                        $orderItem['customer_name'] = $order->getRecipientName();
                        $orderItem['phone'] = $order->getRecipientPhone();
                        $orderItem['email'] = $order->getRecipientEmail();
                        $orderItem['address'] = $order->getAddressDelivery();

                        $orderItem['category'] = $item->getProductItem()->getProduct()->getCategory()->getName();
                        $orderItem['product_item_id'] = $item->getProductItem()->getId();
                        $orderItem['product'] = $item->getProductItem()->getProduct()->getName();
                        $orderItem['size'] = $item->getProductItem()->getSize()->getName();

                        $orderItem['unit_price'] = $item->getProductItem()->getProduct()->getPrice();
                        $orderItem['amount'] = $item->getAmount();
                        $orderItem['total_price'] = $item->getTotal();
                        $orderItem['status'] = self::STATUS_DEFAULT[$order->getStatus()];

                        $orderExportData[] = $orderItem;
                    }
                }
            }

            $fileName = 'Report_Order_' . date('YmdHis') . '.csv';
            if ($input->getOption('name')) {
                $fileName =  $input->getOption('name') . '.csv';
            }

            $outputBuffer = fopen($fileName, 'w');
            fputcsv($outputBuffer, [
                'Order_id',
                'Order_date',
                'Customer',
                'Phone',
                'Email',
                'Address',
                'Category',
                'Product_item_id',
                'Product',
                'Size',
                'Unit_price',
                'Amount',
                'Total_price',
                'Status'
            ], ',');

            foreach ($orderExportData as $row) {
                fputcsv($outputBuffer, $row, ',');
            }
            fclose($outputBuffer);

            $output->write('Export order data successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $output->write('Something went wrong! Please contact support.');
        return Command::FAILURE;
    }
}
