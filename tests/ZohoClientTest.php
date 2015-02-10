<?php

class ZohoClientTest extends PHPUnit_Framework_TestCase
{

    public function getClient()
    {
        return new \Wabel\Zoho\CRM\ZohoClient($GLOBALS['auth_token']);
    }

    public function testGetModules()
    {
        $zohoClient = $this->getClient();

        $modules = $zohoClient->getModules();

        $this->assertContains('Leads', $modules->getRecords());
    }

    public function testGetFields()
    {
        $zohoClient = $this->getClient();

        $fields = $zohoClient->getFields('Leads');

        $this->assertArrayHasKey('Company', $fields->getRecords()['Company Information']);
    }
}
