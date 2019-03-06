<?php

namespace Wabel\Zoho\CRM;

use Wabel\Zoho\CRM\BeanComponents\Field;
use Wabel\Zoho\CRM\Exceptions\ZohoCRMORMException;
use Wabel\Zoho\CRM\Helpers\BeanHelper;
use Wabel\Zoho\CRM\Helpers\ComponentHelper;
use Wabel\Zoho\CRM\Helpers\ZCRMModuleHelper;

/**
 * Base class that provides access to Zoho through Zoho beans.
 */
abstract class AbstractZohoDao
{

    /**
     * The class implementing API methods not directly related to a specific module.
     *
     * @var ZohoClient
     */
    protected $zohoClient;

    public function __construct(ZohoClient $zohoClient)
    {
        $this->zohoClient = $zohoClient;
    }

    abstract protected function getModule();
    abstract protected function getSingularModuleName();
    abstract protected function getPluralModuleName();
    abstract protected function getBeanClassName();
    abstract protected function getFieldsDetails();

    /**
     * @return ZohoClient
     */
    public function getZohoClient(): ZohoClient
    {
        return $this->zohoClient;
    }


    /**
     * @return Field[]
     */
    public function getFields(){
        return array_map(function(array $fieldDetails){
            return ComponentHelper::createFieldFromArray($fieldDetails);
        }, $this->getFieldsDetails());
    }


    /**
     * Returns a module from Zoho.
     * @return \ZCRMModule
     */
    public function getZCRMModule(){
        return $this->zohoClient->getModule($this->getModule());
    }

    /**
     * Parse a Zoho Response in order to retrieve one or several ZohoBeans from it.
     * @param \ZCRMRecord[] $ZCRMRecords
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMORMException
     */
    protected function getBeansFromZCRMRecords(array $ZCRMRecords)
    {
        $beanClass = $this->getBeanClassName();
        $beanArray = array();

        foreach ($ZCRMRecords as $record) {

            /** @var ZohoBeanInterface $bean */
            $bean = new $beanClass();
            BeanHelper::updateZCRMRecordToBean($this, $bean, $record);
            $beanArray[] = $bean;
        }

        return $beanArray;
    }

    /**
     * Implements deleteRecords API method.
     *
     * @param  string $id
     * @return ZohoBeanInterface[]
     * @throws ZohoCRMORMException
     */
    public function delete($id): array
    {
        /***
         * @var $ZCRMRecordDeleted \EntityResponse[]
         */
        $ZCRMRecordsDeleted = $this->zohoClient->deleteRecords($this->getModule(), $id);
        $recordsToDeleted = array_map(function(\EntityResponse $ZCRMRecordDeleted){
            return $ZCRMRecordDeleted->getData();
        }, $ZCRMRecordsDeleted);
        return $this->getBeansFromZCRMRecords($recordsToDeleted);
    }

    /**
     * Implements getRecordById API method.
     *
     * @param string $id Zoho Id of the record to retrieve OR an array of IDs
     *
     * @return ZohoBeanInterface The array of Zoho Beans parsed from the response
     * @throws ZohoCRMORMException
     */
    public function getById($id): ZohoBeanInterface
    {
            $module = $this->getModule();
            $ZCRMRecord = $this->zohoClient->getRecordById($module, $id);
            $beans =  $this->getBeansFromZCRMRecords([$ZCRMRecord]);

            return $beans[0];
    }

    /**
     * Implements getRecords API method.
     *
     * @param string|null $cvId
     * @param string|null $sortColumnString
     * @param string|null $sortOrderString
     * @param \DateTime|null $lastModifiedTime
     * @param int $page
     * @param int $perPage
     * @return ZohoBeanInterface[]
     * @throws ZohoCRMORMException
     * @throws \ZCRMException
     */
    public function getRecords($cvId = null, $sortColumnString = null, $sortOrderString = null, \DateTime $lastModifiedTime = null, $page = 1, $perPage = 200): array
    {
        $ZCRMRecords =  ZCRMModuleHelper::getAllZCRMRecordsFromPagination($this->zohoClient, $this->getModule(),
            $cvId, $sortColumnString, $sortOrderString, $page, $perPage, $lastModifiedTime);
        return $this->getBeansFromZCRMRecords($ZCRMRecords);
    }

    /**
     * Returns the list of deleted records.
     * @param \DateTimeInterface|null $lastModifiedTime
     * @param int $page
     * @param int $perPage
     * @return \ZCRMTrashRecord[]
     * @throws \ZCRMException
     */
    public function getDeletedRecordIds(\DateTimeInterface $lastModifiedTime = null, $page = 1, $perPage = 200)
    {
        return ZCRMModuleHelper::getAllZCRMTrashRecordsFromPagination($this->zohoClient, $this->getModule(),'all', $lastModifiedTime, $page ,$perPage);
    }

    /**
     * @Todo
     */
    // public function getRelatedRecords
    // public function searchRecords
    // public function uploadFile
    // public function downloadFile

    /**
     * Implements insertRecords or updateRecords or upsertRecords API method.
     * @param ZohoBeanInterface[] $beans
     * @param bool $wfTrigger Whether or not the call should trigger the workflows related to a "created" event
     * @throws ZohoCRMORMException
     */
    public function createOrUpdate( array $beans, bool $wfTrigger = false, $action = 'upsert'): void
    {
        /**
         * @var $records \ZCRMRecord[]
         */
        $records = [];

        $dao = $this;
        $processAction = ($action === 'update')?'updating':$action.'ing';

        foreach (array_chunk($beans, 100) as $beansPool) {
            /**
             * @var $beansPool ZohoBeanInterface[]
             */
            $recordsToMerge = array_map(function ($beanPool) use ($dao){
                /**
                 * @var $beanPool ZohoBeanInterface
                 */
                BeanHelper::createOrUpdateBeanToZCRMRecord($dao, $beanPool);
                return $beanPool->getZCRMRecord();
            }, $beansPool);
            $records = array_merge($records, $recordsToMerge);
            switch ($action){
                case 'insert':
                     $this->zohoClient->insertRecords($this->getModule(),$records, $wfTrigger);
                    break;
                case 'update':
                    $this->zohoClient->updateRecords($this->getModule(),$records, $wfTrigger);
                    break;
                case 'upsert':
                default:
                    $this->zohoClient->upsertRecords($this->getModule(), $records);
            }
        }
        if (count($records) != count($beans)) {
            throw new ZohoCRMORMException('Error while '.$processAction.' beans in Zoho. '.count($beans).' passed in parameter, but '.count($records).' returned.');
        }

        foreach ($beans as $key => $bean) {
            BeanHelper::updateZCRMRecordToBean($dao, $bean, $records[$key]);
        }
    }

    /**
     * Implements insertRecords API method.
     * @param ZohoBeanInterface[] $beans
     * @param bool $wfTrigger Whether or not the call should trigger the workflows related to a "created" event
     * @throws ZohoCRMORMException
     */
    public function insertRecords( array $beans, bool $wfTrigger = false): void
    {
        $this->createOrUpdate($beans, $wfTrigger, 'insert');
    }

    /**
     * Implements updateRecords API method.
     *
     * @param ZohoBeanInterface[] $beans
     * @param bool $wfTrigger
     * @throws ZohoCRMORMException
     */
    public function updateRecords(array $beans, bool $wfTrigger = false): void
    {
        $this->createOrUpdate($beans, $wfTrigger, 'update');
    }

    /**
     * Saves the bean or array of beans passed in Zoho.
     * It will perform an insert if the bean has no ZohoID or an update if the bean has a ZohoID.
     * wfTrigger only usable for a single record update/insert.
     * @param  ZohoBeanInterface[] $beans A bean or an array of beans.
     * @param bool $wfTrigger
     */
    public function save($beans, $wfTrigger = false): void
    {

        if (!is_array($beans)) {
            $beans = [$beans];
        }

        $toInsert = [];
        $toUpdate = [];

        foreach ($beans as $bean) {
            if ($bean->getZohoId()) {
                $toUpdate[] = $bean;
            } else {
                $toInsert[] = $bean;
            }
        }

        if ($toInsert) {
            $this->insertRecords($toInsert, $wfTrigger);
        }
        if ($toUpdate) {
            $this->updateRecords($toUpdate, $wfTrigger);
        }
    }

    /**
     * @return ZohoBeanInterface
     * @throws ZohoCRMORMException
     */
    public function create(){
        $record = \ZCRMRecord::getInstance($this->getModule(),null);
        $beanClassName = $this->getBeanClassName();
        $bean = new $beanClassName();
        BeanHelper::updateZCRMRecordToBean($this, $bean, $record);
        return $bean;
    }

    /**
     * @param $fieldName
     * @return null|Field
     */
    public function getFieldFromFieldName($fieldName){
        $fields = $this->getFields();
        /**
         * @var Field[] $field
         */
        $field = array_values(array_filter($fields, function (Field $fiedObj) use ($fieldName){
            return $fiedObj->getName() === $fieldName;
        }));

        return count($field) === 1?$field[0] :null;
    }
}
