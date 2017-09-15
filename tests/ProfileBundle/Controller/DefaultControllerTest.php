<?php

namespace ProfileBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/profile');

        //$content = $client->getResponse()->getContent();

        $this->assertContains('Tu perfil', $client->getResponse()->getContent());
        $this->assertContains('Nombre de la empresa:', $client->getResponse()->getContent());
        $this->assertContains('CIF:', $client->getResponse()->getContent());
        $this->assertContains('Domicilio social:', $client->getResponse()->getContent());
        $this->assertContains('Persona de contacto:', $client->getResponse()->getContent());
        $this->assertContains('Sector:', $client->getResponse()->getContent());
    }
}
