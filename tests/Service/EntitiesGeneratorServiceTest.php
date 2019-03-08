<?php

namespace Wabel\Zoho\CRM\Service;

use Psr\Log\NullLogger;
use Wabel\Zoho\CRM\ZohoClient;
use PHPUnit\Framework\TestCase;

class EntitiesGeneratorServiceTest extends TestCase
{
    /**
     * @var ZohoClient
     */
    private $zohoClient;

    /**
     * @var EntitiesGeneratorService
     */
    private $entitiesGeneratorService;

    protected function setUp()
    {
        $this->zohoClient  = new ZohoClient(
            [
                'client_id' => getenv('client_id'),
                'client_secret' => getenv('client_secret'),
                'redirect_uri' => getenv('redirect_uri'),
                'currentUserEmail' => getenv('currentUserEmail'),
                'applicationLogFilePath' => getenv('applicationLogFilePath'),
                'persistence_handler_class' => getenv('persistence_handler_class'),
                'token_persistence_path' => getenv('token_persistence_path'),
            ],
            getenv('timeZone')

        );
        $this->entitiesGeneratorService = new EntitiesGeneratorService($this->zohoClient, new NullLogger());
    }

    public function testGenerateAll()
    {
        $zohoModulesDaos = $this->entitiesGeneratorService->generateAll(__DIR__.'/../DaoGeneratedTest/', 'Wabel\\Zoho\\CRM\\DaoGeneratedTest');
        $this->assertContains('Wabel\\Zoho\\CRM\\DaoGeneratedTest\\LeadZohoDao', $zohoModulesDaos);
    }

    public function testGenerateModule()
    {

        $this->entitiesGeneratorService->generateModule('Leads', 'Leads', 'Lead', __DIR__.'/../DaoGeneratedTest/', 'Wabel\\Zoho\\CRM\\DaoGeneratedTest');

        $this->assertFileExists(__DIR__.'/../DaoGeneratedTest/Lead.php');

        require __DIR__.'/../DaoGeneratedTest/Lead.php';

        // Second iteration: from existing class!
        $daoFullyQualified = $this->entitiesGeneratorService->generateModule('Leads', 'Leads', 'Lead', __DIR__.'/../DaoGeneratedTest/', 'Wabel\\Zoho\\CRM\\DaoGeneratedTest');

        $this->assertFileExists(__DIR__.'/../DaoGeneratedTest/Lead.php');
        $this->assertEquals('Wabel\\Zoho\\CRM\\DaoGeneratedTest\\LeadZohoDao', $daoFullyQualified);
    }
}
