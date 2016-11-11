<?php

namespace Wabel\Zoho\CRM;

use Wabel\Zoho\CRM\Exception\ZohoCRMException;
use Wabel\Zoho\CRM\Exception\ZohoCRMResponseException;
use Wabel\Zoho\CRM\Exception\ZohoCRMUpdateException;
use Wabel\Zoho\CRM\Request\Response;

/**
 * Base class that provides access to Zoho through Zoho beans.
 */
abstract class AbstractZohoDao
{
    const ON_DUPLICATE_THROW = 1;
    const ON_DUPLICATE_MERGE = 2;
    const MAX_GET_RECORDS = 200;
    const MAX_GET_RECORDS_BY_ID = 100;
    const MAX_SIMULTANEOUS_SAVE = 100;

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
    abstract public function getFields();

    protected $flatFields;

    /**
     * Returns a flat list of all fields.
     *
     * @return array The array of field names for a module
     */
    protected function getFlatFields()
    {
        if ($this->flatFields === null) {
            $this->flatFields = array();
            foreach ($this->getFields() as $cat) {
                $this->flatFields = array_merge($this->flatFields, $cat);
            }
        }

        return $this->flatFields;
    }

    /**
     * Parse a Zoho Response in order to retrieve one or several ZohoBeans from it.
     *
     * @param Response $zohoResponse The response returned by the ZohoClient->call() method
     *
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     */
    protected function getBeansFromResponse(Response $zohoResponse)
    {
        $beanClass = $this->getBeanClassName();
        $fields = $this->getFlatFields();

        $beanArray = array();

        foreach ($zohoResponse->getRecords() as $record) {

            /** @var ZohoBeanInterface $bean */
            $bean = new $beanClass();

            // First, let's fill the ID.
            // The ID is CONTACTID or ACCOUNTID or Id depending on the Zoho type.

            $idName = strtoupper(rtrim($this->getModule(), 's'));
            if (isset($record[$idName.'ID'])) {
                $id = $record[$idName.'ID'];
            } elseif (isset($record[$idName.'_ID'])) {
                $id = $record[$idName.'_ID'];
            } elseif (isset($record['ACTIVITYID'])) {
                $id = $record['ACTIVITYID']; //There is no good way to parse this with the dynamic beans and daos
            } elseif (isset($record['BOOKID'])) {
                $id = $record['BOOKID']; //There is no good way to parse this with the dynamic beans and daos
            } else {
                $id = $record['Id'];
            }
            $bean->setZohoId($id);
            $bean->setCreatedTime(\DateTime::createFromFormat('Y-m-d H:i:s', $record['Created Time']));
            $bean->setModifiedTime(\DateTime::createFromFormat('Y-m-d H:i:s', $record['Modified Time']));

            foreach ($record as $key => $value) {
                if (isset($fields[$key])) {
                    $setter = $fields[$key]['setter'];

                    switch ($fields[$key]['type']) {
                        case 'Date':
                            if ($dateObj = \DateTime::createFromFormat('M/d/Y', $value)) {
                                $value = $dateObj;
                            } elseif ($dateObj = \DateTime::createFromFormat('Y-m-d', $value)) {
                                $value = $dateObj;
                            } else {
                                throw new ZohoCRMException('Unable to convert the Date field "'.$key."\" into a DateTime PHP object from the the record $id of the module ".$this->getModule().'.');
                            }
                            break;
                        case 'DateTime':
                            if (!$dateObj = \DateTime::createFromFormat('Y-m-d H:i:s', $value)) {
                                $dateObj = \DateTime::createFromFormat('Y-m-d', $value);
                            }

                            $value = $dateObj;

                            break;
                        case 'Boolean':
                            $value = ($value == 'true');
                            break;
                        default:
                            break;
                    }
                    $bean->$setter($value);
                }
            }

            $beanArray[] = $bean;
        }

        return $beanArray;
    }

    /**
     * Convert an array of ZohoBeans into a SimpleXMLElement.
     *
     * @param $zohoBeans ZohoBeanInterface[]
     *
     * @return \SimpleXMLElement The SimpleXMLElement containing the XML for a request
     */
    public function toXml($zohoBeans)
    {
        $module = $this->getModule();

        $no = 1;
        $module = new \SimpleXMLElement("<$module/>");

        foreach ($zohoBeans as $zohoBean) {
            if (!$zohoBean instanceof ZohoBeanInterface) {
                throw new ZohoCRMException('Zoho beans sent to save must implement the ZohoBeanInterface.');
            }

            $properties = $this->getFlatFields();
            $row = $module->addChild('row');
            $row->addAttribute('no', $no);

            $fl = $row->addChild('FL', $zohoBean->getZohoId());
            $fl->addAttribute('val', 'Id');

            foreach ($properties as $name => $params) {
                $camelCaseName = $params['name'];
                $isDirty = $zohoBean->isDirty($camelCaseName);
                if (!$isDirty) {
                    continue;
                }

                $getter = $params['getter'];
                $value = $zohoBean->$getter();

                if (!empty($value) || is_bool($value)) {

                    // We convert the value back to a proper format if the Zoho Type is Date, DateTime or Boolean
                    switch ($params['type']) {
                        case 'Date':
                            /** @var \DateTime $value */
                            $value = $value->format('m/d/Y');
                            break;
                        case 'DateTime':
                            /** @var \DateTime $value */
                            $value = $value->format('Y-m-d H:i:s');
                            break;
                        case 'Boolean':
                            /** @var bool $value */
                            $value = $value ? 'true' : 'false';
                            break;
                        default:
                            break;
                    }
                }

                $fl = $row->addChild('FL', htmlspecialchars($value));
                $fl->addAttribute('val', $name);
            }
            ++$no;
        }

        return $module;
    }

    /**
     * Implements deleteRecords API method.
     *
     * @param string $id Zoho Id of the record to delete
     *
     * @throws ZohoCRMResponseException
     */
    public function delete($id)
    {
        $this->zohoClient->deleteRecords($this->getModule(), $id);
    }

    /**
     * Implements getRecordById API method.
     *
     * @param string|array $id Zoho Id of the record to retrieve OR an array of IDs
     *
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     *
     * @throws ZohoCRMResponseException
     */
    public function getById($id)
    {
        try {
            $module = $this->getModule();
            $beans = [];

            // If there's several IDs to process, we divide them by pools of 100 and implode them before requesting
            if (is_array($id)) {
                foreach (array_chunk($id, self::MAX_GET_RECORDS_BY_ID) as $pool) {
                    $idlist = implode(';', $pool);
                    $response = $this->zohoClient->getRecordById($module, $idlist);
                    $beans = array_merge($beans, $this->getBeansFromResponse($response));
                }
            }
            // if not, we simply request our record
            else {
                $response = $this->zohoClient->getRecordById($module, $id);
                $beans = $this->getBeansFromResponse($response);
                $beans = array_shift($beans);
            }

            return $beans;
        } catch (ZohoCRMResponseException $e) {
            // No records found? Let's return an empty array!
            if ($e->getCode() == 4422) {
                return array();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Implements getRecords API method.
     *
     * @param $sortColumnString
     * @param $sortOrderString
     * @param \DateTime $lastModifiedTime
     * @param $selectColumns
     * @param $limit
     *
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     *
     * @throws ZohoCRMResponseException
     */
    public function getRecords($sortColumnString = null, $sortOrderString = null, \DateTime $lastModifiedTime = null, $selectColumns = null, $limit = null)
    {
        $globalResponse = array();

        do {
            $fromIndex = count($globalResponse) + 1;
            $toIndex = $fromIndex + self::MAX_GET_RECORDS - 1;

            if ($limit) {
                $toIndex = min($limit - 1, $toIndex);
            }

            $beans = $this->requestRecords($sortColumnString, $sortOrderString, $lastModifiedTime, $selectColumns, $fromIndex, $toIndex);

            $globalResponse = array_merge($globalResponse, $beans);
        } while (count($beans) == self::MAX_GET_RECORDS);

        return $globalResponse;
    }

    /**
     * Implements getRecords API method.
     *
     * @param $sortColumnString
     * @param $sortOrderString
     * @param \DateTime $lastModifiedTime
     * @param $selectColumns
     * @param $limit
     *
     * @param null $offset
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     *
     * @throws ZohoCRMResponseException
     */
    public function getPaginatedRecords($sortColumnString = null, $sortOrderString = null, \DateTime $lastModifiedTime = null, $selectColumns = null, $limit = null, $offset = null)
    {
        $globalResponse = array();

        do {
            $fromIndex = $offset + count($globalResponse) + 1;
            $toIndex = $fromIndex + self::MAX_GET_RECORDS - 1;

            if ($limit) {
                $toIndex = min($fromIndex + $limit - 1, $toIndex);
            }

            $beans = $this->requestRecords($sortColumnString, $sortOrderString, $lastModifiedTime, $selectColumns, $fromIndex, $toIndex);

            $globalResponse = array_merge($globalResponse, $beans);
        } while (count($globalResponse) < $limit && !empty($beans));

        return $globalResponse;
    }

    private function requestRecords($sortColumnString, $sortOrderString, $lastModifiedTime, $selectColumns, $fromIndex, $toIndex)
    {
        try {
            $response = $this->zohoClient->getRecords($this->getModule(), $sortColumnString, $sortOrderString, $lastModifiedTime, $selectColumns, $fromIndex, $toIndex);
            $beans = $this->getBeansFromResponse($response);
        } catch (ZohoCRMResponseException $e) {
            // No records found? Let's return an empty array!
            if ($e->getCode() == 4422) {
                $beans = array();
            } else {
                throw $e;
            }
        }

        return $beans;
    }

    /**
     * Returns the list of deleted records.
     *
     * @param \DateTimeInterface|null $lastModifiedTime
     * @param int                     $limit
     *
     * @return array
     *
     * @throws ZohoCRMResponseException
     * @throws \Exception
     */
    public function getDeletedRecordIds(\DateTimeInterface $lastModifiedTime = null, $limit = null)
    {
        $globalDeletedIDs = array();

        do {
            try {
                $fromIndex = count($globalDeletedIDs) + 1;
                $toIndex = $fromIndex + self::MAX_GET_RECORDS - 1;

                if ($limit) {
                    $toIndex = min($limit - 1, $toIndex);
                }

                $response = $this->zohoClient->getDeletedRecordIds($this->getModule(), $lastModifiedTime, $fromIndex, $toIndex);
                $deletedIDs = $response->getDeletedIds();
            } catch (ZohoCRMResponseException $e) {
                // No records found? Let's return an empty array!
                if ($e->getZohoCode() == 4422) {
                    $deletedIDs = array();
                } else {
                    throw $e;
                }
            }

            $globalDeletedIDs = array_merge($globalDeletedIDs, $deletedIDs);
        } while (count($deletedIDs) == self::MAX_GET_RECORDS);

        return $globalDeletedIDs;
    }

    /**
     * Implements getRecords API method.
     *
     * @param string $id           Zoho Id of the record to delete
     * @param string $parentModule The parent module of the records
     * @param int    $limit        The max number of records to fetch
     *
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     *
     * @throws ZohoCRMResponseException
     */
    public function getRelatedRecords($id, $parentModule, $limit = null)
    {
        $globalResponse = array();

        do {
            try {
                $fromIndex = count($globalResponse) + 1;
                $toIndex = $fromIndex + self::MAX_GET_RECORDS - 1;

                if ($limit) {
                    $toIndex = min($limit - 1, $toIndex);
                }

                $response = $this->zohoClient->getRelatedRecords($this->getModule(), $id, $parentModule, $fromIndex, $toIndex);
                $beans = $this->getBeansFromResponse($response);
            } catch (ZohoCRMResponseException $e) {
                // No records found? Let's return an empty array!
                if ($e->getCode() == 4422) {
                    $beans = array();
                } else {
                    throw $e;
                }
            }

            $globalResponse = array_merge($globalResponse, $beans);
        } while (count($beans) == self::MAX_GET_RECORDS);

        return $globalResponse;
    }

    /**
     * Implements searchRecords API method.
     *
     * @param string    $searchCondition  The search criteria formatted like
     * @param int       $limit            The maximum number of beans returned from Zoho
     * @param \DateTime $lastModifiedTime
     * @param string    $selectColumns    The list
     *
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     *
     * @throws ZohoCRMResponseException
     */
    public function searchRecords($searchCondition = null, $limit = null, \DateTime $lastModifiedTime = null, $selectColumns = null)
    {
        $globalResponse = array();

        do {
            try {
                $fromIndex = count($globalResponse) + 1;
                $toIndex = $fromIndex + self::MAX_GET_RECORDS - 1;

                if ($limit) {
                    $toIndex = min($limit - 1, $toIndex);
                }

                $response = $this->zohoClient->searchRecords($this->getModule(), $searchCondition, $fromIndex, $toIndex, $lastModifiedTime, $selectColumns);
                $beans = $this->getBeansFromResponse($response);
            } catch (ZohoCRMResponseException $e) {
                // No records found? Let's return an empty array!
                if ($e->getCode() == 4422) {
                    $beans = array();
                } else {
                    throw $e;
                }
            }

            $globalResponse = array_merge($globalResponse, $beans);
        } while (count($beans) == self::MAX_GET_RECORDS);

        return $globalResponse;
    }

    /**
     * Implements insertRecords API method.
     *
     * WARNING : When setting wfTrigger to true, this method will use an API call per bean
     * passed in argument. This is caused by Zoho limitation which forbids triggering any
     * workflow when inserting several beans simultaneously.
     *
     * @param ZohoBeanInterface[] $beans          The Zoho Beans to insert in the CRM
     * @param bool                $wfTrigger      Whether or not the call should trigger the workflows related to a "created" event
     * @param int                 $duplicateCheck 1 : Throwing error when a duplicate is found; 2 : Merging with existing duplicate
     * @param bool                $isApproval     Whether or not to push the record into an approval sandbox first
     *
     * @throws ZohoCRMResponseException
     */
    public function insertRecords($beans, $wfTrigger = null, $duplicateCheck = 2, $isApproval = null)
    {
        $records = [];

        if ($wfTrigger) {
            // If we trigger workflows, we trigger the insert of beans one by one.
            foreach ($beans as $bean) {
                $xmlData = $this->toXml([$bean]);
                $response = $this->zohoClient->insertRecords($this->getModule(), $xmlData, $wfTrigger, $duplicateCheck, $isApproval);
                $records = array_merge($records, $response->getRecords());
            }
        } else {
            // We can't pass more than 100 records to Zoho, so we split the request into pieces of 100
            foreach (array_chunk($beans, 100) as $beanPool) {
                $xmlData = $this->toXml($beanPool);
                $response = $this->zohoClient->insertRecords($this->getModule(), $xmlData, $wfTrigger, $duplicateCheck, $isApproval);
                $records = array_merge($records, $response->getRecords());
            }
        }
        if (count($records) != count($beans)) {
            throw new ZohoCRMException('Error while inserting beans in Zoho. '.count($beans).' passed in parameter, but '.count($records).' returned.');
        }

        foreach ($beans as $key => $bean) {
            $record = $records[$key];

            if ($wfTrigger && (!isset($record['Id']) || empty($record['Id']))) {
                // This field is probably in error!
                throw new ZohoCRMException('An error occurred while inserting records and triggering workflow: '.$record['message'], $record['code']);
            } elseif (!$wfTrigger && substr($record['code'], 0, 1) != '2') {
                // This field is probably in error!
                throw new ZohoCRMException('An error occurred while inserting records: '.$record['message'], $record['code']);
            }

            $bean->setZohoId($record['Id']);
            $bean->setCreatedTime(\DateTime::createFromFormat('Y-m-d H:i:s', $record['Created Time']));
            if ($record['Modified Time']) {
                $bean->setModifiedTime(\DateTime::createFromFormat('Y-m-d H:i:s', $record['Modified Time']));
            }
        }
    }

    /**
     * Implements updateRecords API method.
     *
     * @param array $beans     The list of beans to update.
     * @param bool  $wfTrigger Set value as true to trigger the workflow rule in Zoho
     *
     * @return Response The Response object
     *
     * @throws ZohoCRMException
     */
    public function updateRecords(array $beans, $wfTrigger = null)
    {
        $records = [];

        if ($wfTrigger) {
            // If we trigger workflows, we trigger the insert of beans one by one.
            foreach ($beans as $bean) {
                $xmlData = $this->toXml([$bean]);
                $response = $this->zohoClient->updateRecords($this->getModule(), $xmlData, $bean->getZohoId(), $wfTrigger);
                $records = array_merge($records, $response->getRecords());
            }
        } else {
            // We can't pass more than 100 records to Zoho, so we split the request into pieces of 100
            foreach (array_chunk($beans, 100) as $beanPool) {
                $xmlData = $this->toXml($beanPool);
                $response = $this->zohoClient->updateRecords($this->getModule(), $xmlData, null, $wfTrigger);
                $records = array_merge($records, $response->getRecords());
            }
        }
        if (count($records) != count($beans)) {
            throw new ZohoCRMException('Error while inserting beans in Zoho. '.count($beans).' passed in parameter, but '.count($records).' returned.');
        }

        $exceptions = new \SplObjectStorage();

        foreach ($beans as $key => $bean) {
            $record = $records[$key];

            if ($wfTrigger && (!isset($record['Id']) || empty($record['Id']))) {
                // This field is probably in error!
                throw new ZohoCRMException('An error occurred while updating records and triggering workflow: '.$record['message'], $record['code']);
            } elseif (!$wfTrigger && substr($record['code'], 0, 1) != '2') {
                // This field is probably in error!
                $exceptions->attach($bean, new ZohoCRMException('An error occurred while updating records. '.(isset($record['message']) ? $record['message'] : ''), $record['code']));
                continue;
            }

            if ($record['Id'] != $bean->getZohoId()) {
                // This field is probably in error!
                $exceptions->attach($bean, new ZohoCRMException('An error occurred while updating records. The Zoho ID to update was '.$bean->getZohoId().', returned '.$record['Id']));
                continue;
            }

            if ($record['Modified Time']) {
                $bean->setModifiedTime(\DateTime::createFromFormat('Y-m-d H:i:s', $record['Modified Time']));
            }
        }
        if ($exceptions->count() != 0) {
            throw new ZohoCRMUpdateException($exceptions);
        }
    }

    /**
     * Implements uploadFile API method.
     *
     * @param string $id      Zoho Id of the record to retrieve
     * @param string $content The string containing the file
     *
     * @return Response The Response object
     *
     * @throws ZohoCRMResponseException
     */
    public function uploadFile($id, $content)
    {
        return $this->zohoClient->uploadFile($this->getModule(), $id, $content);
    }

    /**
     * Implements downloadFile API method.
     *
     * @param string $id unique ID of the attachment
     *
     * @return Response The Response object
     */
    public function downloadFile($id)
    {
        return $this->zohoClient->downloadFile($this->getModule(), $id);
    }

    /**
     * Saves the bean or array of beans passed in Zoho.
     * It will perform an insert if the bean has no ZohoID or an update if the bean has a ZohoID.
     *
     * @param array|object $beans A bean or an array of beans.
     *
     * TODO: isApproval is not used by each module.
     * TODO: wfTrigger only usable for a single record update/insert.
     */
    public function save($beans, $wfTrigger = false, $duplicateCheck = self::ON_DUPLICATE_MERGE, $isApproval = false)
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
            $this->insertRecords($toInsert, $wfTrigger, $duplicateCheck, $isApproval);
        }
        if ($toUpdate) {
            $this->updateRecords($toUpdate, $wfTrigger);
        }
    }
}
