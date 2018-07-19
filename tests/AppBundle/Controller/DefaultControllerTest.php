<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/home');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Identificarse', $crawler->filter('a')->eq(1)->text());
        $this->assertContains('Registrarse', $crawler->filter('a')->eq(2)->text());
    }
}
