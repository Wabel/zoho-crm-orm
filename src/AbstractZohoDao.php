<?php
namespace Wabel\Zoho\CRM;

use GuzzleHttp\Client;
use Wabel\Zoho\CRM\Exception\ZohoCRMException;
use Wabel\Zoho\CRM\Exception\ZohoCRMResponseException;
use Wabel\Zoho\CRM\Request\Response;

/**
 * Base class that provides access to Zoho through Zoho beans.
 *
 */
abstract class AbstractZohoDao
{
    const ON_DUPLICATE_THROW = 1;
    const ON_DUPLICATE_MERGE = 2;
    const MAX_RECORD_RETRIEVE = 200;
    const MAX_SIMULTANEOUS_SAVE = 100;

    /**
     * The class implementing API methods not directly related to a specific module
     *
     * @var ZohoClient
     */
    protected $zohoClient;

    public function __construct(ZohoClient $zohoClient) {
        $this->zohoClient = $zohoClient;
    }

    abstract protected function getModule();
    abstract protected function getBeanClassName();
    abstract protected function getFields();

    protected $flatFields;

    /**
     * Returns a flat list of all fields.
     *
     * @return array The array of field names for a module
     */
    protected function getFlatFields() {
        if ($this->flatFields === null) {
            $this->flatFields = array();
            foreach ($this->getFields() as $cat) {
                $this->flatFields = array_merge($this->flatFields, $cat);
            }
        }
        return $this->flatFields;
    }

    /**
     * Parse a Zoho Response in order to retrieve one or several ZohoBeans from it
     *
     * @param Response $zohoResponse The response returned by the ZohoClient->call() method
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     */
    protected function getBeansFromResponse(Response $zohoResponse) {

        $beanClass = $this->getBeanClassName();
        $fields = $this->getFlatFields();

        $beanArray = array();

        foreach ($zohoResponse->getRecords() as $record) {

            /** @var ZohoBeanInterface $bean */
            $bean = new $beanClass();

            // First, let's fill the ID.
            // The ID is CONTACTID or ACCOUNTID or Id depending on the Zoho type.
            $idName = strtoupper(rtrim($this->getModule(), "s"))."ID";
            if (isset($record[$idName])) {
                $id = $record[$idName];
            } else {
                $id = $record['Id'];
            }
            $bean->setZohoId($id);
            $bean->setCreatedTime(\DateTime::createFromFormat('Y-m-d H:i:s', $record['Created Time']));
            $bean->setModifiedTime(\DateTime::createFromFormat('Y-m-d H:i:s', $record['Modified Time']));

            foreach ($record as $key=>$value) {
                if (isset($fields[$key])) {
                    $setter = $fields[$key]['setter'];

                    switch ($fields[$key]['type']) {
                        case "Date":
                            $value = \DateTime::createFromFormat('M/d/Y', $value);
                            break;
                        case "DateTime":
                            $value = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
                            break;
                        case "Boolean":
                            $value = ($value == "true");
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
     * Convert an array of ZohoBeans into a SimpleXMLElement
     *
     * @param $zohoBeans ZohoBeanInterface[]
     * @return \SimpleXMLElement The SimpleXMLElement containing the XML for a request
     */
    protected function toXml($zohoBeans)
    {
        $module = $this->getModule();

        $no = 1;
        $module = new \SimpleXMLElement("<$module/>");

        foreach ($zohoBeans as $zohoBean) {
            if (!$zohoBean instanceof ZohoBeanInterface) {
                throw new ZohoCRMException("Zoho beans sent to save must implement the ZohoBeanInterface.");
            }

            $properties = $this->getFlatFields();
            $row = $module->addChild("row");
            $row->addAttribute("no", $no);

            $fl = $row->addChild("FL", $zohoBean->getZohoId());
            $fl->addAttribute("val", "Id");

            foreach ($properties as $name => $params) {
                $getter = $params['getter'];
                $value = $zohoBean->$getter();

                if (!empty($value)) {

                    // We convert the value back to a proper format if the Zoho Type is Date, DateTime or Boolean
                    switch ($params['type']) {
                        case "Date":
                            /** @var \DateTime $value */
                            $value = $value->format('M/d/Y');
                            break;
                        case "DateTime":
                            /** @var \DateTime $value */
                            $value = $value->format('Y-m-d H:i:s');
                            break;
                        case "Boolean":
                            /** @var boolean $value */
                            $value = $value ? "true" : "false";
                            break;
                        default:
                            break;
                    }

                    $fl = $row->addChild("FL", htmlspecialchars($value));
                    $fl->addAttribute("val", $name);
                }
            }
            $no++;
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
     * @param  string $id Zoho Id of the record to retrieve
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMResponseException
     */
    public function getById($id)
    {
        try {
            $module = $this->getModule();

            $response = $this->zohoClient->getRecordById($module, $id);

            return $this->getBeansFromResponse($response);
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
     * @param $fromIndex
     * @param $toIndex
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMResponseException
     */
    public function getRecords($sortColumnString = null, $sortOrderString = null, \DateTime $lastModifiedTime = null, $selectColumns = null, $fromIndex = 1, $toIndex = 200)
    {
        try {
            $toindex = $toIndex > 200 ? 200 : $toIndex;
            $response = $this->zohoClient->getRecords($this->getModule(), $sortColumnString, $sortOrderString, $lastModifiedTime, $selectColumns, $fromIndex, $toIndex);

            if(count($response->getRecords()) == count($toIndex)) {
                return array_merge(
                    $this->getBeansFromResponse($response),
                    $this->getRecords($sortColumnString, $sortOrderString, $lastModifiedTime, $selectColumns, $toindex + 1)
                );
            }
            else {
                return $this->getBeansFromResponse($response);
            }
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
     * @param string $id Zoho Id of the record to delete
     * @param string $parentModule The parent module of the records
     * @param int $fromIndex The offset from which you want parse Zoho
     * @param int $toIndex The offset to which you want to parse Zoho
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMResponseException
     */
    public function getRelatedRecords($id, $parentModule, $fromIndex = null, $toIndex = 200)
    {
        try {
            $response = $this->zohoClient->getRelatedRecords($this->getModule(), $id, $parentModule, $fromIndex, $toIndex);

            if(count($response->getRecords()) == count($toIndex)) {
                return array_merge(
                    $this->getBeansFromResponse($response),
                    $this->getRelatedRecords($id, $parentModule, $toIndex + 1, $toIndex + 200)
                );
            }
            else {
                return $this->getBeansFromResponse($response);
            }
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
     * Implements searchRecords API method.
     *
     * @param string $searchCondition The search criteria formatted like
     * @param int $fromIndex The offset from which you want parse Zoho
     * @param int $toIndex The offset to which you want to parse Zoho
     * @param \DateTime $lastModifiedTime
     * @param string $selectColumns The list
     * @return ZohoBeanInterface[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMResponseException
     */
    public function searchRecords($searchCondition = null, $fromIndex = 1, $toIndex = 200, \DateTime $lastModifiedTime = null, $selectColumns = null)
    {
        try {
            $response = $this->zohoClient->searchRecords($this->getModule(), $searchCondition, $fromIndex, $toIndex, $lastModifiedTime, $selectColumns);

            if(count($response->getRecords()) == count($toIndex)) {
                return array_merge(
                    $this->getBeansFromResponse($response),
                    $this->searchRecords($searchCondition, $toIndex + 1, $toIndex + 200, $lastModifiedTime, $selectColumns)
                );
            }
            else {
                return $this->getBeansFromResponse($response);
            }
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
     * Implements insertRecords API method.
     *
     * @param ZohoBeanInterface[] $beans The Zoho Beans to insert in the CRM
     * @param bool $wfTrigger Whether or not the call should trigger the workflows related to a "created" event
     * @param int $duplicateCheck 1 : Throwing error when a duplicate is found; 2 : Merging with existing duplicate
     * @param bool $isApproval Whether or not to push the record into an approval sandbox first
     * @throws ZohoCRMResponseException
     */
    public function insertRecords($beans, $wfTrigger = null, $duplicateCheck = null, $isApproval = null)
    {
        $records = [];

        // We can't pass more than 100 records to Zoho, so we split the request into pieces of 100
        foreach(array_chunk($beans, 100) AS $beanPool) {
            $xmlData = $this->toXml($beanPool);
            $response = $this->zohoClient->insertRecords($this->getModule(), $xmlData, $wfTrigger, $duplicateCheck, $isApproval);
            $records = array_merge($records, $response->getRecords());
        }
        if (count($records) != count($beans)) {
            throw new ZohoCRMException("Error while inserting beans in Zoho. ".count($beans)." passed in parameter, but ".count($records)." returned.");
        }

        foreach ($beans as $key=>$bean) {
            $record = $records[$key];

            if (substr($record['code'], 0, 1) != "2") {
                // This field is probably in error!
                throw new ZohoCRMException('An error occurred while inserting records: '.$record['details'], $record['code']);
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
     * @param array $beans The list of beans to update.
     * @param bool $wfTrigger Set value as true to trigger the workflow rule in Zoho
     * @return Response The Response object
     * @throws ZohoCRMException
     */
    public function updateRecords(array $beans, $wfTrigger = null)
    {

        $records = [];

        // We can't pass more than 100 records to Zoho, so we split the request into pieces of 100
        foreach(array_chunk($beans, 100) AS $beanPool) {
            $xmlData = $this->toXml($beanPool);

            $response = $this->zohoClient->updateRecords($this->getModule(), $xmlData, null, $wfTrigger);

            $records = array_merge($records, $response->getRecords());
        }
        if (count($records) != count($beans)) {
            throw new ZohoCRMException("Error while inserting beans in Zoho. ".count($beans)." passed in parameter, but ".count($records)." returned.");
        }

        foreach ($beans as $key=>$bean) {
            $record = $records[$key];

            if (substr($record['code'], 0, 1) != "2") {
                // This field is probably in error!
                throw new ZohoCRMException('An error occurred while updating records. '.(isset($record['message'])?$record['message']:""), $record['code']);
            }

            if ($record['Id'] != $bean->getZohoId()) {
                // This field is probably in error!
                throw new ZohoCRMException('An error occurred while updating records. The Zoho ID to update was '.$bean->getZohoId().', returned '.$record['Id']);
            }

            if ($record['Modified Time']) {
                $bean->setModifiedTime(\DateTime::createFromFormat('Y-m-d H:i:s', $record['Modified Time']));
            }
        }
    }

    /**
     * Implements uploadFile API method.
     *
     * @param string $id Zoho Id of the record to retrieve
     * @param string $content The string containing the file
     * @return Response The Response object
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
     */
    public function save($beans, $wfTrigger = false, $duplicateCheck = self::ON_DUPLICATE_THROW, $isApproval = false)
    {
        if (!is_array($beans)) {
            $beans = [ $beans ];
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

        if ($toUpdate) {
            $this->updateRecords($toUpdate, $wfTrigger);
        }
        if ($toInsert) {
            $this->insertRecords($toInsert, $wfTrigger, $duplicateCheck, $isApproval);
        }
    }
}
