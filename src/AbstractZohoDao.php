<?php namespace Wabel\Zoho\CRM;

use GuzzleHttp\Client;
use Wabel\Zoho\CRM\Exception\ZohoCRMException;
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
     * @param Response $zohoResponse
     * TODO Mike : Make it private ?
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
     * Convert a ZohoBean or an array of ZohoBeans into XML
     *
     * @param $zohoBeans AbstractZohoBean|AbstractZohoBean[]
     * @return \SimpleXMLElement
     */
    public function toXml($zohoBeans)
    {
        $module = $this->getModule();

        $no = 1;
        $module = new \SimpleXMLElement("<$module/>");

        foreach ($zohoBeans as $zohoBean) {

            $properties = $this->getFields();
            $row = $module->addChild("row");
            $row->addAttribute("no", $no);

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

//                    if (htmlspecialchars($value) !== $value) {
//                        htmlentities($value);
//                    }

                    $fl = $row->addChild("FL", $value);
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
     * @param string $id Id of the record
     *
     * @return Response The Response object
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
     * @param  string   $id      Id of the record
     * @return
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
     * @param string $searchCondition search condition in the format (fieldName:searchString)
     *                                e.g. (Email:*@sample.com*)
     * @param array  $params          request parameters
     *                                selectColumns String  Module(columns) e.g. Leads(Last Name,Website,Email)
     *                                Note: do not use any extra spaces when listing column names
     *                                fromIndex	    Integer	Default value 1
     *                                toIndex	      Integer	Default value 20
     *                                Maximum value 200
     *                                newFormat     Integer 1 (default) - exclude fields with null values in the response
     *                                2 - include fields with null values in the response
     *                                version       Integer 1 (default) - use earlier API implementation
     *                                2 - use latest API implementation
     *
     * @return Response The Response object
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

        $response = $this->zohoClient->call($module, 'searchRecords', $params);

        return $this->getBeansFromResponse($response);
    }

    /**
     * Implements getUsers API method.
     *
     *  @param string  $type       type of the user to return. Possible values:
     *                              AllUsers - all users (both active and inactive)
     *                              ActiveUsers - only active users
     *                              DeactiveUsers - only deactivated users
     *                              AdminUsers - all users with admin privileges
     *                              ActiveConfirmedAdmins - users with admin privileges that are confirmed
     * @param integer $newFormat 1 (default) - exclude fields with null values in the response
     *                           2 - include fields with null values in the response
     *
     * @return Response The Response object
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
     * @param array $data   xmlData represented as an array
     *                      array will be converted into XML before sending the request
     * @param array $params request parameters
     *                      wfTrigger	      Boolean	Set value as true to trigger the workflow rule
     *                      while inserting record into CRM account. By default, this parameter is false.
     *                      duplicateCheck	Integer	Set value as "1" to check the duplicate records and throw an
     *                      error response or "2" to check the duplicate records, if exists, update the same.
     *                      isApproval	    Boolean	By default, records are inserted directly . To keep the records in approval mode,
     *                      set value as true. You can use this parameters for Leads, Contacts, and Cases module.
     *                      newFormat       Integer	1 (default) - exclude fields with null values in the response
     *                      2 - include fields with null values in the response
     *                      version         Integer	1 (default) - use earlier API implementation
     *                      2 - use latest API implementation
     *                      4 - enable duplicate check functionality for multiple records.
     *                      It's recommended to use version 4 for inserting multiple records
     *                      even when duplicate check is turned off.
     *
     * @param  array    $options Options to add for configurations [optional]
     * @return Response The Response object
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
        // TODO Convert beans to XML to inject them in the request
        $xmlData = $this->toXml($beans);

        $response = $this->zohoClient->call($module, 'insertRecords', $params, $xmlData);

        return $this->getBeansFromResponse($response);
    }

    /**
     * Implements updateRecords API method.
     *
     * @param string $id     unique ID of the record to be updated
     * @param array  $data   xmlData represented as an array
     *                       array will be converted into XML before sending the request
     * @param array  $params request parameters
     *                       wfTrigger    Boolean   Set value as true to trigger the workflow rule
     *                       while inserting record into CRM account. By default, this parameter is false.
     *                       newFormat    Integer   1 (default) - exclude fields with "null" values while updating data
     *                       2 - include fields with "null" values while updating data
     *                       version      Integer   1 (default) - use earlier API implementation
     *                       2 - use latest API implementation
     *                       4 - update multiple records in a single API method call
     *
     * @param  array    $options Options to add for configurations [optional]
     * @return Response The Response object
     * @todo Use full SimpleXMLRequest in data to check number easily and set default parameters
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

        $xmlData = $this->toXml($beans);

        return $this->zohoClient->call($module, 'updateRecords', $params, $xmlData);
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
            if ($bean->getSohoId()) {
                $updateRecords[] = $bean;
            } else {
                $insertRecords[] = $bean;
            }
        }

        $this->updateRecords($updateRecords);
        $this->insertRecords($insertRecords, $wfTrigger, $duplicateCheck, $isApproval);
    }
}
