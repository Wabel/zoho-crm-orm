<?php namespace Wabel\Zoho\CRM;

use GuzzleHttp\Client;
use Wabel\Zoho\CRM\Exception\ZohoCRMException;
use Wabel\Zoho\CRM\Request\Response;
use Wabel\Zoho\CRM\Wrapper\Element;

/**
 * Base class that provides access to Zoho through Zoho beans.
 *
 */
abstract class AbstractZohoDao
{
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

        return $this->zohoClient->call($module, 'deleteRecords', $params);
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

        $records = $response->getRecords();

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
    public function getRelatedRecords($module, $id, $parentModule, $params = array())
    {
        $params["id"] = $id;
        $params["parentModule"] = $parentModule;
        $params['newFormat'] = 1;

        return $this->call($module, 'getRelatedRecords', $params);
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

        $results = $this->zohoClient->call($module, 'searchRecords', $params);

        $beanClass = $this->getBeanClassName();
        $fields = $this->getFlatFields();
        //var_dump($results);
        foreach ($fields as $recordArray) {

            $bean = new $beanClass();

            // First, let's fill the ID.
            // The ID is CONTACTID or ACCOUNTID or Id depending on the Zoho type.
            if (isset($recordArray['CONTACTID'])) {
                $id = $recordArray['CONTACTID'];
            } elseif (isset($recordArray['ACCOUNTID'])) {
                $id = $recordArray['ACCOUNTID'];
            } else {
                $id = $recordArray['Id'];
            }
            $bean->setZohoId($id);

            foreach ($recordArray as $key=>$value) {
                if (isset($fields[$key])) {
                    // TODO HERE!
                }
            }


        }
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
    public function getUsers($type = 'AllUsers', $newFormat = 1)
    {
        $params['type'] = $type;
        $params['newFormat'] = $newFormat;

        return $this->call('Users', 'getUsers', $params);
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
     * @todo Use full SimpleXMLRequest in data to check number easily and set default parameters
     */
    public function insertRecords($module, $data, $params = array(), $options = array())
    {
        if (!isset($params['duplicateCheck'])) {
            $params['duplicateCheck'] = 2;
        }
        if (!$params['version']) {
            // Version 4 is mandatory for updating multiple records.
        $params['version'] = 4;
        }
        $params['newFormat'] = 1;

        return $this->call($module, 'insertRecords', $params, $data, $options);
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
    public function updateRecords($module, $data, $id = null, $params = array(), $options = array())
    {
        if ($id) {
            $params['id'] = $id;
            $params['version'] = isset($params['version']) ? $params['version'] : 1;
        } elseif (!isset($params['version']) || $params['version'] == 4) {
            $params['version'] = 4;
            if (!isset($options['postXMLData'])) {
                $options['postXMLData'] = true;
            }
        } else {
            throw new \InvalidArgumentException('Record Id is required and cannot be empty.');
        }
        $params['newFormat'] = 1;

        return $this->call($module, 'updateRecords', $params, $data, $options);
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

        return $this->call($module, 'uploadFile', $params);
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

        return $this->call($module, 'downloadFile', $params);
    }

    /**
     * Returns a list of modules from Zoho
     */
    public function getModules()
    {
        return $this->call('Info', 'getModules', []);
    }


    /**
     * Convert an entity into XML
     *
     * @param  Element $entity Element with values on fields setted
     * @return string  XML created
     * @todo
     - Add iteration for multiples entities and creation of xml with collection
     */
    public function mapEntity(Element $entity, $no = null)
    {
        if (!$entity->getModule()) {
            throw new \Exception("Invalid module returned by entity", 1);
        }
        $element = new \ReflectionObject($entity);
        $properties = $element->getProperties();
        $xml = $no ? '' : '<'.$entity->getModule().'>'."\n";
        $xml .= '<row no="'.($no ? $no : '1').'">'."\n";
        foreach ($properties as $property) {
            $propName = $property->getName();
            $propValue = $entity->$propName;

            if (!empty($propValue) && $propName !== "module") {

                // Dealing with the customs zoho attributes
                if ($propName === "customs" && is_array($propValue)) {
                    foreach ($propValue as $name => $value) {
                        if (htmlspecialchars($value) !== $value) {
                            $value = htmlspecialchars($value);
                        }
                        $xml .= '<FL val="'.str_replace(['_', 'N36', 'E5F'], [' ', '$', '_'], $name).'">'.$value.'</FL>'."\n";
                    }
                } else {
                    if (htmlspecialchars($propValue) !== $propValue && $propName !== "Account Name") {
                        $propValue = htmlspecialchars($propValue);
                    }
                    $xml .= '<FL val="'.str_replace(['_', 'N36', 'E5F'], [' ', '$', '_'], $propName).'">'.$propValue.'</FL>'."\n";
                }
            }
        }
        $xml .= '</row>'."\n";
        $xml .= $no ? '' :  '</'.$entity->getModule().'>';

        return $xml;
    }

    /**
     * Convert an array of entities into XML
     *
     * @param  Element[] $entities Element with values on fields setted
     * @return string    XML created
     */
    public function mapEntities(array $entities)
    {
        if (count($entities) === 0) {
            throw new ZohoCRMException("mapEntities called with empty array.");
        }
        $firstEntity = reset($entities);
        $module = $firstEntity->getModule();

        $no = 1;
        $xml = '<'.$module.'>'."\n";
        foreach ($entities as $entity) {
            $xml .= $this->mapEntity($entity, $no++);
        }
        $xml .= '</'.$module.'>';

        return $xml;
    }
}
