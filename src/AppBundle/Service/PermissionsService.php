<?php

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use AppBundle\Entity\User;

class PermissionsService {

    private $session;
    protected $em;

    public function __construct(\Doctrine\ORM\EntityManager $em, SessionInterface $session) {
        $this->session = $session;
        $this->em = $em;
    }

    public function getCurrentUser() {
        if (!$this->session->get('user')) {
            return null;
        }

        $found = $this->em
            ->getRepository(User::class)
            ->findByEmail($this->session->get('user'));

        if (!$found) {
            return null;
        }

        if (!$found[0]->getEnabled()) {
            return null;
        }

        return $found[0];
    }

    public function currentRolesInclude($role) {
        $user = $this->getCurrentUser();

        if ($user == null) {
            return false;
        }

        return in_array($role, explode(',', $user->getRoles()));
    }

}
