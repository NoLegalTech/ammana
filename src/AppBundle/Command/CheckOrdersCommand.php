<?php

namespace AppBundle\Command;

use AppBundle\Entity\Protocol;
use AppBundle\Entity\User;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckOrdersCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('orders:show')
            ->setDescription('Show all orders in the system');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);

        $theOrders = $this->getContainer()->get('doctrine')
            ->getRepository(Protocol::class)
            ->findAll();

        $this->printOrders($io, $theOrders);
        $this->checkUnpaidOrders($io, $theOrders);
    }

    private function printOrders($io, $orders) {
        $paid = array();
        $unpaid = array();

        foreach ($orders as $order) {
            if ($order->getEnabled()) {
                $paid []= $order;
            } else {
                $unpaid []= $order;
            }
        }

        if (count($paid) > 0) {
            $io->title('Paid orders');
            $this->_printOrders($io, $paid);
        }

        if (count($unpaid) > 0) {
            $io->title('Unpaid orders');
            $this->_printOrders($io, $unpaid);
        }
    }

    private function _printOrders($io, $orders) {
        $days_to_worry = $this->getContainer()->getParameter('days_to_worry_about_unpaid_order');
        $now = new \DateTime(date('Y-m-d'));

        $rows = array();
        foreach ($orders as $order) {
            $open = '';
            $close = '';
            $orderDate = $order->getOrderDate();
            $elapsed = $now->diff($orderDate)->format('%a');
            if (!$order->getEnabled() && $elapsed > $days_to_worry) {
                $open = '<error>';
                $close = '</error>';
            }
            $rows []= array(
                $open . $order->getId() . $close,
                $open . $orderDate->format('d/m/Y') . $close,
                $open . $order->getIdentifier() . $close,
                $open . $this->getContainer()->get('doctrine')
                    ->getRepository(User::class)
                    ->findOneById($order->getUser())
                    ->getEmail() . $close,
                $open . $elapsed . ' days' . $close,
                $open . ($order->getAlerted() ? "true" : "false") . $close,
            );
        }

        $io->table(
            array('ID', 'Date', 'Protocol', 'User', 'Elapsed', 'Alerted'),
            $rows
        );
    }

    private function checkUnpaidOrders($io, $orders) {
        $days_to_worry = $this->getContainer()->getParameter('days_to_worry_about_unpaid_order');
        $now = new \DateTime(date('Y-m-d'));

        foreach ($orders as $order) {
            $orderDate = $order->getOrderDate();
            $elapsed = $now->diff($orderDate)->format('%a');
            if (!$order->getEnabled() && $elapsed > $days_to_worry) {
                if (!$order->getAlerted()) {
                    $this->alertUnpaidOrder($io, $order, $elapsed);
                    $order->setAlerted(true);
                    $this->getContainer()->get('doctrine')->getManager()->flush();
                }
            }
        }
    }

    private function alertUnpaidOrder($io, $order, $days) {
        $io->note('Sending alert email for order ' . $order->getId());
        $alerts = $this->getContainer()->get('AppBundle\Service\AlertsService');
        $alerts->error(
            $this->getI18n()['alerts']['unpaid_order']['title'],
            $this->getI18n()['alerts']['unpaid_order']['message'],
            $order->__toString()
        );
    }

    private function getI18n() {
        return $this->getContainer()->get('twig')->getGlobals()['i18n']['es'];
    }

}
