<?php
namespace Wabel\Zoho\CRM\Service;

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
        $fields = $this->zohoClient->getFields($module);

        mkdir($targetDirectory, 0775, true);


        var_export($fields);exit;

        // TODO: continue here!
        // TODO: continue here!
        // TODO: continue here!
        // TODO: continue here!
        // Note: use https://github.com/gossi/php-code-generator to quickly generate beans!
    }
}

/*

Wabel\Zoho\CRM\Request\Response::__set_state(array(
   'code' => NULL,
   'message' => NULL,
   'method' => 'getFields',
   'module' => 'Leads',
   'records' =>
  array (
    'Company Information' =>
    array (
      'Company' =>
      array (
        'req' => false,
        'type' => 'Text',
        'isreadonly' => false,
        'maxlength' => 100,
        'label' => 'Company',
        'dv' => 'Company',
        'customfield' => false,
      ),
      'Product' =>
      array (
        'req' => false,
        'type' => 'TextArea',
        'isreadonly' => false,
        'maxlength' => 32000,
        'label' => 'Product',
        'dv' => 'Product',
        'customfield' => true,
      ),
      'Website' =>
      array (
        'req' => false,
        'type' => 'Website',
        'isreadonly' => false,
        'maxlength' => 255,
        'label' => 'Website',
        'dv' => 'Website',
        'customfield' => false,
      ),
      'City' =>
      array (
        'req' => false,
        'type' => 'Text',
        'isreadonly' => false,
        'maxlength' => 30,
        'label' => 'City',
        'dv' => 'City',
        'customfield' => false,
      ),
      'Country' =>
      array (
        'req' => false,
        'type' => 'Text',
        'isreadonly' => false,
        'maxlength' => 30,
        'label' => 'Country',
        'dv' => 'Country',
        'customfield' => false,
      ),
      'State' =>
      array (
        'req' => false,
        'type' => 'Text',
        'isreadonly' => false,
        'maxlength' => 30,
        'label' => 'State',
        'dv' => 'Province',
        'customfield' => false,
      ),
      'Street' =>
      array (
        'req' => false,
        'type' => 'Text',
        'isreadonly' => false,
        'maxlength' => 250,
        'label' => 'Street',
        'dv' => 'Street',
        'customfield' => false,
      ),
      'Zip Code' =>
      array (
        'req' => false,
        'type' => 'Text',
        'isreadonly' => false,
        'maxlength' => 30,
        'label' => 'Zip Code',
        'dv' => 'Postal Code',
        'customfield' => false,
      ),
      'Phone' =>
      array (
        'req' => false,
        'type' => 'Phone',
        'isreadonly' => false,
        'maxlength' => 30,
        'label' => 'Phone',
        'dv' => 'Phone',
        'customfield' => false,
      ),
      'No of Employees' =>
      array (
        'req' => false,
        'type' => 'Integer',
        'isreadonly' => false,
        'maxlength' => 16,
        'label' => 'No of Employees',
        'dv' => 'No of Employees',
        'customfield' => false,
      ),
      'Fax' =>
      array (
        'req' => false,
        'type' => 'Text',
        'isreadonly' => false,
        'maxlength' => 30,
        'label' => 'Fax',
        'dv' => 'Fax',
        'customfield' => false,
      ),
      'Description' =>
      array (
        'req' => false,
        'type' => 'TextArea',
        'isreadonly' => false,
        'maxlength' => 32000,
        'label' => 'Description',
        'dv' => 'Description',
        'customfield' => false,
      ),
      'Annual Revenue' =>
      array (
        'req' => false,
        'type' => 'Currency',
        'isreadonly' => false,
        'maxlength' => 16,
        'label' => 'Annual Revenue',
        'dv' => 'Annual Revenue',
        'customfield' => false,
      ),
      'Buying Office' =>
      array (
        'req' => false,
        'type' => 'Lookup',
        'isreadonly' => false,
        'maxlength' => 120,
        'label' => 'Buying Office',
        'dv' => 'Buying Office',
        'customfield' => true,
      ),
    ),



 */