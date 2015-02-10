<?php
namespace Wabel\Zoho\Service;

use Wabel\Zoho\CRM\ZohoClient;

/**
 * This class is in charge of generating Zoho entities.
 */
class EntitiesGeneratorService {

    private $zohoClient;

    public function __construct(ZohoClient $zohoClient) {
        $this->zohoClient = $zohoClient;
    }

    /**
     * Generate ALL entities for all Zoho modules.
     *
     * @param string $targetDirectory
     * @param string $namespace
     */
    public function generateAll($targetDirectory, $namespace) {
        $modules = $this->zohoClient->getModules();
        foreach ($modules->getRecords() as $module) {
            $this->generateModule($module, $targetDirectory, $namespace);
        }
    }

    public function generateModule($module, $targetDirectory, $namespace) {
        // TODO: continue here!
        // TODO: continue here!
        // TODO: continue here!
        // TODO: continue here!
        // Note: use https://github.com/gossi/php-code-generator to quickly generate beans!
    }
}