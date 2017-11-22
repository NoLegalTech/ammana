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
            ->setName('orders:check')
            ->setDescription('Checks orders in the system to detect if there are unpaid ones');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        $now = new \DateTime(date('Y-m-d'));
        $days_to_worry = $this->getContainer()->getParameter('days_to_worry_about_unpaid_order');

        $io = new SymfonyStyle($input, $output);

        $io->title('Pending orders');

        $theOrders = $this->getContainer()->get('doctrine')
            ->getRepository(Protocol::class)
            ->findByEnabled(false);

        $rows = array();

        foreach ($theOrders as $order) {
            $open = '';
            $close = '';
            $orderDate = $order->getOrderDate();
            $elapsed = $now->diff($orderDate)->format('%a');
            if ($elapsed > $days_to_worry) {
                $open = '<error>';
                $close = '</error>';
            }
            $rows []= array(
                $open . $orderDate->format('d/m/Y') . $close,
                $open . $order->getIdentifier() . $close,
                $open . $this->getContainer()->get('doctrine')
                    ->getRepository(User::class)
                    ->findOneById($order->getUser())
                    ->getEmail() . $close,
                $open . $elapsed . ' days' . $close
            );
        }

        $io->table(
            array('Date', 'Protocol', 'User', 'Elapsed'),
            $rows
        );
    }

}
