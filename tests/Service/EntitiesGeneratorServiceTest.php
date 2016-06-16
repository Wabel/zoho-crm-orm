<?php

namespace Wabel\Zoho\CRM\Service;

use Psr\Log\NullLogger;
use Wabel\Zoho\CRM\ZohoClient;

class EntitiesGeneratorServiceTest extends \PHPUnit_Framework_TestCase
{
    public function getEntitiesGeneratorService()
    {
        $client = new ZohoClient($GLOBALS['auth_token']);

        return new EntitiesGeneratorService($client, new NullLogger());
    }

    public function testGenerateAll()
    {
        $generator = $this->getEntitiesGeneratorService();
        $zohoModulesDaos = $generator->generateAll(__DIR__.'/../generated/', 'TestNamespace');
        $this->assertContains('TestNamespace\\LeadZohoDao', $zohoModulesDaos);
    }

    public function testGenerateModule()
    {
        $generator = $this->getEntitiesGeneratorService();

        $generator->generateModule('Leads', 'Leads', 'Lead', __DIR__.'/../generated/', 'TestNamespace');

        $this->assertFileExists(__DIR__.'/../generated/Lead.php');

        require __DIR__.'/../generated/Lead.php';

        // Second iteration: from existing class!
        $daoFullyQualified = $generator->generateModule('Leads', 'Leads', 'Lead', __DIR__.'/../generated/', 'TestNamespace');

        $this->assertFileExists(__DIR__.'/../generated/Lead.php');
        $this->assertEquals('TestNamespace\\LeadZohoDao', $daoFullyQualified);
    }
}
