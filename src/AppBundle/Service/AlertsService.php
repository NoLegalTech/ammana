<?php

namespace AppBundle\Service;

use Psr\Log\LoggerInterface;
use Twig_Environment;

class AlertsService {

    private $permissions;
    private $logger;
    private $twig;
    private $mailer;

    private $sender_email;
    private $sender_name;
    private $email_to_report_errors;

    public function __construct(\Swift_Mailer $mailer, LoggerInterface $logger, Twig_Environment $twig, PermissionsService $permissions, $sender_email, $sender_name, $email_to_report_errors) {
        $this->permissions = $permissions;
        $this->logger = $logger;
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->sender_email = $sender_email;
        $this->sender_name = $sender_name;
        $this->email_to_report_errors = $email_to_report_errors;
    }

    public function error($title, $message, $additional_data = null) {
        $this->logger->error($title . ':');
        $this->logger->error('    ' . $message);

        $this->_alert('error', $title, $message, $additional_data);
    }

    public function info($title, $message, $additional_data = null) {
        $this->logger->info($title . ':');
        $this->logger->info('    ' . $message);

        $this->_alert('info', $title, $message, $additional_data);
    }

    private function getI18n() {
        return $this->twig->getGlobals()['i18n']['es'];
    }

    private function _alert($type, $title, $message, $additional_data = null) {
        $user = $this->permissions->getCurrentUser();
        if ($user != null) {
            $user = $user->__toString();
        }

        $plain_text = $this->twig->render(
            'email/' . $type . '.txt.twig',
            array(
                'title' => $title,
                'message' => $message,
                'user' => $user,
                'additional_data' => $additional_data
            )
        );

        $swift_message = (new \Swift_Message($this->getI18n()['emails'][$type]['title']))
            ->setFrom(array($this->sender_email => $this->sender_name))
            ->setTo($this->email_to_report_errors)
            ->setBody(
                $this->twig->render(
                    'email/' . $type . '.html.twig',
                    array(
                        'title' => $title,
                        'message' => $message,
                        'user' => $user,
                        'additional_data' => $additional_data
                    )
                ),
                'text/html'
            )
            ->addPart($plain_text, 'text/plain');

        $this->mailer->send($swift_message);
    }

}
