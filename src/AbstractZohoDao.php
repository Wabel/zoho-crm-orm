<?php namespace Wabel\Zoho\CRM;

use GuzzleHttp\Client;
use Wabel\Zoho\CRM\Exception\ZohoCRMException;
use Wabel\Zoho\CRM\Exception\ZohoCRMResponseException;
use Wabel\Zoho\CRM\Request\Response;
use Wabel\Zoho\CRM\Wrapper\AbstractZohoBean;
use Wabel\Zoho\CRM\Wrapper\Element;

/**
 * Base class that provides access to Zoho through Zoho beans.
 *
 */
abstract class AbstractZohoDao
{
    const ON_DUPLICATE_THROW = 1;
    const ON_DUPLICATE_MERGE = 2;

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
     * @return AbstractZohoBean[] The array of Zoho Beans parsed from the response
     */
    protected function getBeansFromResponse(Response $zohoResponse) {

        $beanClass = $this->getBeanClassName();
        $fields = $this->getFlatFields();

        $beanArray = array();

        foreach ($zohoResponse->getRecords() as $record) {

            /** @var AbstractZohoBean $bean */
            $bean = new $beanClass();

            // First, let's fill the ID.
            // The ID is CONTACTID or ACCOUNTID or Id depending on the Zoho type.
            $idName = strtoupper(trim($this->getModule(), "s"))."ID";
            if (isset($record[$idName])) {
                $id = $record[$idName];
            } else {
                $id = $record['Id'];
            }
            $bean->setZohoId($id);

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
     * @param $zohoBeans AbstractZohoBean[]
     * @return \SimpleXMLElement The SimpleXMLElement containing the XML for a request
     */
    protected function toXml($zohoBeans)
    {
        $module = $this->getModule();

        $no = 1;
        $module = new \SimpleXMLElement("<$module/>");

        foreach ($zohoBeans as $zohoBean) {

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
        }

        return $module;
    }

    /**
     * Implements convertLead API method.
     *
     * @param  string   $module  The Zoho module to query
     * @param  string   $leadId  Id of the lead
     * @param  array    $data    xmlData represented as an array
     *                           array will be converted into XML before sending the request
     * @param  array    $params  request parameters
     *                           newFormat 1 (default) - exclude fields with null values in the response
     *                           2 - include fields with null values in the response
     *                           version   1 (default) - use earlier API implementation
     *                           2 - use latest API implementation
     * @return Response The Response object
     */
    /*public function convertLead($module, $leadId, $data, $params = array())
    {
        $params['leadId'] = $leadId;
        $params['newFormat'] = 1;

        return $this->call($module, 'convertLead', $params, $data);
    }*/

    /**
     * Implements deleteRecords API method.
     *
     * @param string $id Zoho Id of the record to delete
     *
     * @return AbstractZohoBean[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMResponseException
     */
    public function delete($id)
    {
        $module = $this->getModule();
        $params['id'] = $id;
        $params['newFormat'] = 1;

        $response =  $this->zohoClient->call($module, 'deleteRecords', $params);
        $this->getBeansFromResponse($response);
    }

    /**
     * Implements getRecordById API method.
     *
     * @param  string $id Zoho Id of the record to retrieve
     * @return AbstractZohoBean[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMResponseException
     */
    public function getById($id)
    {
        $module = $this->getModule();
        $params = array(
            'id' => $id,
            'newFormat' => 2
        );

        $response = $this->zohoClient->call($module, 'getRecordById', $params);

        return $this->getBeansFromResponse($response);

    }

    /**
     * Implements getRecords API method.
     *
     * @param  array    $params  request parameters
     *                           selectColumns     String  Module(optional columns) i.e, leads(Last Name,Website,Email) OR All
     *                           fromIndex	        Integer	Default value 1
     *                           toIndex	          Integer	Default value 20
     *                           Maximum value 200
     *                           sortColumnString	String	If you use the sortColumnString parameter, by default data is sorted in ascending order.
     *                           sortOrderString	  String	Default value - asc
     *                           if you want to sort in descending order, then you have to pass sortOrderString=desc.
     *                           lastModifiedTime	DateTime	Default value: null
     *                           If you specify the time, modified data will be fetched after the configured time.
     *                           newFormat         Integer	1 (default) - exclude fields with null values in the response
     *                           2 - include fields with null values in the response
     *                           version           Integer	1 (default) - use earlier API implementation
     *                           2 - use latest API implementation
     * @return Response The Response object
     */
    /*public function getRecords($params = array())
    {
        $module = $this->getModule();
        $params['newFormat'] = 1;

        return $this->call($module, 'getRecords', $params);
    }*/

    /**
     * Implements getRecords API method.
     *
     * @param string $id Zoho Id of the record to delete
     * @param string $parentModule The parent module of the records
     * @param int $fromIndex The offset from which you want parse Zoho
     * @param int $toIndex The offset to which you want to parse Zoho
     * @return AbstractZohoBean[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMResponseException
     */
    public function getRelatedRecords($id, $parentModule, $fromIndex = null, $toIndex = null)
    {
        $params["id"] = $id;
        $params["parentModule"] = $parentModule;
        $params['newFormat'] = 1;
        if($fromIndex) {
            $params['fromIndex'] = $fromIndex;
        }
        if($toIndex) {
            $params['toIndex'] = $toIndex;
        }

        $module = $this->getModule();

        $response = $this->zohoClient->call($module, 'getRelatedRecords', $params);

        return $this->getBeansFromResponse($response);
    }

    /**
     * Implements searchRecords API method.
     *
     * @param string $searchCondition The search criteria formatted like
     * @param int $fromIndex The offset from which you want parse Zoho
     * @param int $toIndex The offset to which you want to parse Zoho
     * @param \DateTime $lastModifiedTime
     * @param string $selectColumns The list
     * @return AbstractZohoBean[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMResponseException
     */
    public function searchRecords($searchCondition = null, $fromIndex = null, $toIndex = null, \DateTime $lastModifiedTime = null, $selectColumns = null)
    {
        $module = $this->getModule();
        $params = [];
        if ($searchCondition) {
            $params['criteria'] = $searchCondition;
        } else {
            $params['criteria'] = "";
        }
        if ($fromIndex) {
            $params['fromIndex'] = $fromIndex;
        }
        if ($toIndex) {
            $params['toIndex'] = $toIndex;
        }
        if ($lastModifiedTime) {
            $params['lastModifiedTime'] = $lastModifiedTime->format('Y-m-d H:i:s');
        }
        if ($selectColumns) {
            $params['selectColumns'] = $selectColumns;
        }

        $params['newFormat'] = 1;

        try {
            $response = $this->zohoClient->call($module, 'searchRecords', $params);
        } catch (ZohoCRMResponseException $e) {
            // No records found? Let's return an empty array!
            if ($e->getCode() == 4422) {
                return array();
            } else {
                throw $e;
            }
        }

        return $this->getBeansFromResponse($response);
    }

    /**
     * Implements getUsers API method.
     *
     * @param string $type The type of users you want retrieve (among AllUsers, ActiveUsers, DeactiveUsers, AdminUsers and ActiveConfirmedAdmins)
     * @return AbstractZohoBean[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMResponseException
     */
    public function getUsers($type = 'AllUsers')
    {
        switch($type) {
            case 'AllUsers':
            case 'ActiveUsers':
            case 'DeactiveUsers':
            case 'AdminUsers':
            case 'ActiveConfirmedAdmins':
                $params['type'] = $type;
                break;
            default :
                $params['type'] = 'AllUsers';
                break;
        }
        $params['newFormat'] = 1;

        $response = $this->zohoClient->call('Users', 'getUsers', $params);

        return $this->getBeansFromResponse($response);
    }

    /**
     * Implements insertRecords API method.
     *
     * @param AbstractZohoBean[] $beans The Zoho Beans to insert in the CRM
     * @param bool $wfTrigger Whether or not the call should trigger the workflows related to a "created" event
     * @param int $duplicateCheck 1 : Throwing error when a duplicate is found; 2 : Merging with existing duplicate
     * @param bool $isApproval Whether or not to push the record into an approval sandbox first
     * @return AbstractZohoBean[] The array of Zoho Beans parsed from the response
     * @throws ZohoCRMResponseException
     */
    public function insertRecords($beans, $wfTrigger = null, $duplicateCheck = null, $isApproval = null)
    {
        $module = $this->getModule();
        $params['newFormat'] = 1;
        if($wfTrigger) {
            $params['wfTrigger'] = $wfTrigger;
        }
        if($duplicateCheck) {
            $params['duplicateCheck'] = $duplicateCheck;
        }
        if($isApproval) {
            $params['isApproval'] = $isApproval;
        }

        // If there are several beans to insert, we
        //$params['version'] = is_array($beans) ? 4 : 1;
        $params['version'] = 4;
        $xmlData = $this->toXml($beans)->asXML();

        $response = $this->zohoClient->call($module, 'insertRecords', $params, [ 'xmlData' => $xmlData ]);

        $records = $response->getRecords();
        if (count($records) != count($beans)) {
            throw new ZohoCRMException("Error while inserting beans in Zoho. ".count($beans)." passed in parameter, but ".count($records)." returned.");
        }

        $i = 1;
        foreach ($beans as $bean) {
            $record = $records[$i];

            if (substr($record['code'], 0, 1) != "2") {
                // This field is probably in error!
                throw new ZohoCRMException('An error occurred while inserting records: '.$record['details'], $record['code']);
            }

            $bean->setZohoId($record['Id']);

            $i++;
        }
        //return $this->getBeansFromResponse($response);
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
        $module = $this->getModule();
        $params['newFormat'] = 1;

        if($wfTrigger) {
            $params['wfTrigger'] = $wfTrigger;
        }

        $params['version'] = 4;
        $params['newFormat'] = 1;

        $xmlData = $this->toXml($beans)->asXML();

        $response = $this->zohoClient->call($module, 'updateRecords', $params, [ 'xmlData' => $xmlData ]);

        $records = $response->getRecords();
        if (count($records) != count($beans)) {
            throw new ZohoCRMException("Error while inserting beans in Zoho. ".count($beans)." passed in parameter, but ".count($records)." returned.");
        }

        foreach ($records as $record) {
            if (substr($record['code'], 0, 1) != "2") {
                // This field is probably in error!
                throw new ZohoCRMException('An error occurred while inserting records. '.(isset($record['message'])?$record['message']:""), $record['code']);
            }
        }
    }

    /**
     * Implements uploadFile API method.
     *
     * @param string $id unique ID of the record to be updated
     *
     * @param file path $content Pass the File Input Stream of the file
     *
     * @param array $params request parameters
     *                      wfTrigger    Boolean   Set value as true to trigger the workflow rule
     *                      while inserting record into CRM account. By default, this parameter is false.
     *                      newFormat    Integer   1 (default) - exclude fields with "null" values while updating data
     *                      2 - include fields with "null" values while updating data
     *                      version      Integer   1 (default) - use earlier API implementation
     *                      2 - use latest API implementation
     *                      4 - update multiple records in a single API method call
     *
     * @return Response The Response object
     */
    public function uploadFile($module, $id, $content, $params = array())
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Record Id is required and cannot be empty.');
        }
        $params['id'] = $id;
        $params['content'] = $content;

        return $this->zohoClient->call($module, 'uploadFile', $params);
    }

    /**
     * Implements downloadFile API method.
     *
     * @param string $id unique ID of the attachment
     *
     * @return Response The Response object
     */
    public function downloadFile($module, $id, $params = array())
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Record Id is required and cannot be empty.');
        }
        $params['id'] = $id;

        return $this->zohoClient->call($module, 'downloadFile', $params);
    }

    /**
     * Returns a list of modules from Zoho
     */
    public function getModules()
    {
        return $this->zohoClient->call('Info', 'getModules', []);
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

        $insertRecords = [];
        $updateRecords = [];

        foreach ($beans as $bean) {
            if ($bean->getZohoId()) {
                $updateRecords[] = $bean;
            } else {
                $insertRecords[] = $bean;
            }
        }

        if ($updateRecords) {
            $this->updateRecords($updateRecords, $wfTrigger);
        }
        if ($insertRecords) {
            $this->insertRecords($insertRecords, $wfTrigger, $duplicateCheck, $isApproval);
        }
    }
}
