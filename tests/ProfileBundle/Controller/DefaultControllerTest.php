<?php

namespace ProfileBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/profile');

        $this->assertContains('Tu perfil', $client->getResponse()->getContent());
    }
}
