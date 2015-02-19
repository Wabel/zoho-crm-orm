<?php
namespace Wabel\Zoho\CRM\Service;


use Wabel\Zoho\CRM\ZohoClient;

class EntitiesGeneratorServiceTest extends \PHPUnit_Framework_TestCase {

    public function getEntitiesGeneratorService()
    {
        $client =  new ZohoClient($GLOBALS['auth_token']);
        return new EntitiesGeneratorService($client);
    }

    public function testGenerateModule() {
        $generator = $this->getEntitiesGeneratorService();

        $generator->generateModule('Leads', __DIR__.'/../generated/', 'TestNamespace');

        $this->assertFileExists(__DIR__.'/../generated/Lead.php');
    }
}
