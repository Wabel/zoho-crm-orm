<?php

namespace Wabel\Zoho\CRM;

use Wabel\Zoho\CRM\BeanComponents\Field;
use Wabel\Zoho\CRM\Exceptions\ExceptionZohoClient;
use Wabel\Zoho\CRM\Exceptions\ZohoCRMORMException;
use Wabel\Zoho\CRM\Helpers\BeanHelper;
use Wabel\Zoho\CRM\Helpers\ComponentHelper;
use Wabel\Zoho\CRM\Helpers\ZCRMModuleHelper;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\crud\ZCRMModule;
use zcrmsdk\crm\api\response\EntityResponse;

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

    abstract public function getModule();
    abstract public function getSingularModuleName();
    abstract public function getPluralModuleName();
    abstract public function getBeanClassName();
    abstract public function getFieldsDetails();

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
    public function getFields()
    {
        return array_map(
            function (array $fieldDetails) {
                return ComponentHelper::createFieldFromArray($fieldDetails);
            }, $this->getFieldsDetails()
        );
    }


    /**
     * Returns a module from Zoho.
     *
     * @return ZCRMModule
     */
    public function getZCRMModule()
    {
        return $this->zohoClient->getModule($this->getModule());
    }

    /**
     * Parse a Zoho Response in order to retrieve one or several ZohoBeans from it.
     *
     * @param  ZCRMRecord[] $ZCRMRecords
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMORMException
     */
    public function getBeansFromZCRMRecords(array $ZCRMRecords)
    {
        $beanClass = $this->getBeanClassName();
        $beanArray = array();

        foreach ($ZCRMRecords as $record) {

            /**
 * @var ZohoBeanInterface $bean 
*/
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
         * @var $ZCRMRecordDeleted EntityResponse[]
         */
        $ZCRMRecordsDeleted = $this->zohoClient->deleteRecords($this->getModule(), $id);

        $recordsToDeleted = array_map(
            function (EntityResponse $ZCRMRecordDeleted) {
                return $ZCRMRecordDeleted->getData();
            }, $ZCRMRecordsDeleted
        );

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
     * @param  string|null    $cvId
     * @param  string|null    $sortColumnString
     * @param  string|null    $sortOrderString
     * @param  \DateTime|null $lastModifiedTime
     * @param  int            $page
     * @param  int            $perPage
     * @return ZohoBeanInterface[]
     * @throws ZohoCRMORMException
     * @throws \ZCRMException
     */
    public function getRecords($cvId = null, $sortColumnString = null, $sortOrderString = null, \DateTime $lastModifiedTime = null, $page = 1, $perPage = 200): array
    {
        try{
            $ZCRMRecords =  ZCRMModuleHelper::getAllZCRMRecordsFromPagination($this->zohoClient, $this->getModule(),
                $cvId, $sortColumnString, $sortOrderString, $page, $perPage, $lastModifiedTime);
        } catch(\ZCRMException $exception){
            if(ExceptionZohoClient::exceptionCodeFormat($exception->getExceptionCode()) === ExceptionZohoClient::EXCEPTION_CODE_NO__CONTENT) {
                $ZCRMRecords = [];
            } else{
                $this->zohoClient->logException($exception);
                throw $exception;
            }
        }
        return $this->getBeansFromZCRMRecords($ZCRMRecords);
    }

    /**
     * Returns the list of deleted records.
     *
     * @param  \DateTimeInterface|null $lastModifiedTime
     * @param  int                     $page
     * @param  int                     $perPage
     * @return \ZCRMTrashRecord[]
     * @throws \ZCRMException
     */
    public function getDeletedRecordIds(\DateTimeInterface $lastModifiedTime = null, $page = 1, $perPage = 200)
    {
        return ZCRMModuleHelper::getAllZCRMTrashRecordsFromPagination($this->zohoClient, $this->getModule(), 'all', $lastModifiedTime, $page, $perPage);
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
     *
     * @param ZohoBeanInterface[] $beans
     * @param bool                $wfTrigger Whether or not the call should
     *                                       trigger the workflows related to a
     *                                       "created" event
     * @param string              $action
     *
     * @return array
     * @throws \Wabel\Zoho\CRM\Exceptions\ZohoCRMORMException
     * @throws \zcrmsdk\crm\exception\ZCRMException
     */
    public function createOrUpdate( array $beans, bool $wfTrigger = false, $action = 'upsert'): array
    {
        /**
         * @var $records ZCRMRecord[]
         */
        $records = [];
        /** @var \zcrmsdk\crm\api\response\EntityResponse[] $responses */
        $responses = [];

        $dao = $this;
        $processAction = ($action === 'update')?'updating':$action.'ing';

        foreach (array_chunk($beans, 100) as $beansPool) {
            /**
             * @var $beansPool ZohoBeanInterface[]
             */
            $recordsToMerge = array_map(
                function ($beanPool) use ($dao) {
                    /**
                     * @var $beanPool ZohoBeanInterface
                     */
                    BeanHelper::createOrUpdateBeanToZCRMRecord($dao, $beanPool);
                    return $beanPool->getZCRMRecord();
                }, $beansPool
            );
            $records = array_merge($records, $recordsToMerge);
            switch ($action){
            case 'insert':
                 $responses = $this->zohoClient->insertRecords($this->getModule(), $records, $wfTrigger);
                break;
            case 'update':
                $responses = $this->zohoClient->updateRecords($this->getModule(), $records, $wfTrigger);
                break;
            case 'upsert':
            default:
                $responses = $this->zohoClient->upsertRecords($this->getModule(), $records);
            }
        }
        if (count($records) != count($beans)) {
            throw new ZohoCRMORMException('Error while '.$processAction.' beans in Zoho. '.count($beans).' passed in parameter, but '.count($records).' returned.');
        }

        foreach ($beans as $key => $bean) {
            BeanHelper::updateZCRMRecordToBean($dao, $bean, $records[$key]);
        }

        return $responses;
    }

    /**
     * Implements insertRecords API method.
     *
     * @param ZohoBeanInterface[] $beans
     * @param bool                $wfTrigger Whether or not the call should
     *                                       trigger the workflows related to a
     *                                       "created" event
     *
     * @return array
     * @throws ZohoCRMORMException
     * @throws \zcrmsdk\crm\exception\ZCRMException
     */
    public function insertRecords( array $beans, bool $wfTrigger = false): array
    {
        return $this->createOrUpdate($beans, $wfTrigger, 'insert');
    }

    /**
     * Implements updateRecords API method.
     *
     * @param ZohoBeanInterface[] $beans
     * @param bool                $wfTrigger
     *
     * @return array
     * @throws ZohoCRMORMException
     * @throws \zcrmsdk\crm\exception\ZCRMException
     */
    public function updateRecords(array $beans, bool $wfTrigger = false): array
    {
        return $this->createOrUpdate($beans, $wfTrigger, 'update');
    }

    /**
     * Saves the bean or array of beans passed in Zoho.
     * It will perform an insert if the bean has no ZohoID or an update if the
     * bean has a ZohoID. wfTrigger only usable for a single record
     * update/insert.
     *
     * @param ZohoBeanInterface|ZohoBeanInterface[] $beans A bean or an array
     *                                                     of beans.
     * @param bool                                  $wfTrigger
     *
     * @return array
     * @throws ZohoCRMORMException
     * @throws \zcrmsdk\crm\exception\ZCRMException
     */
    public function save( $beans, $wfTrigger = false ): array
    {

        if (!is_array($beans)) {
            $beans = [$beans];
        }

        $toInsert = [];
        $toUpdate = [];
        $insertResponses = [];
        $updateResponses = [];

        foreach ($beans as $bean) {
            if ($bean->getZohoId()) {
                $toUpdate[] = $bean;
            } else {
                $toInsert[] = $bean;
            }
        }

        if ($toInsert) {
            $insertResponses = $this->insertRecords($toInsert, $wfTrigger);
        }
        if ($toUpdate) {
            $updateResponses = $this->updateRecords($toUpdate, $wfTrigger);
        }

        return array_merge($insertResponses, $updateResponses);
    }

    /**
     * @return ZohoBeanInterface
     * @throws ZohoCRMORMException
     */
    public function create()
    {
        $record = ZCRMRecord::getInstance($this->getModule(), null);
        $beanClassName = $this->getBeanClassName();
        $bean = new $beanClassName();
        BeanHelper::updateZCRMRecordToBean($this, $bean, $record);
        return $bean;
    }

    /**
     * @param  $fieldName
     * @return null|Field
     */
    public function getFieldFromFieldName($fieldName)
    {
        $fields = $this->getFields();
        /**
         * @var Field[] $field
         */
        $field = array_values(
            array_filter(
                $fields, function (Field $fiedObj) use ($fieldName) {
                    return $fiedObj->getName() === $fieldName;
                }
            )
        );

        return count($field) === 1?$field[0] :null;
    }
}
