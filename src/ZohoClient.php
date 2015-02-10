<?php namespace Wabel\Zoho\CRM;

use GuzzleHttp\Client;
use Wabel\Utils\Zoho\CRM\Exception\ZohoCRMException;
use Wabel\Zoho\CRM\Request\Response;
use Wabel\Zoho\CRM\Wrapper\Element;

/**
 * Client for provide interface with Zoho CRM
 *
 * TODO : Add comments (a lot)
 */
class ZohoClient
{
    /**
     * URL for call request
     *
     * @var string
     */
    const BASE_URI = 'https://crm.zoho.com/crm/private';

    /**
     * Token used for session of request
     *
     * @var string
     */
    protected $authtoken;

    /**
     * Instance of the client
     *
     * @var Client
     */
    protected $zohoRestClient;

    /**
     * Format selected for get request
     *
     * @var string
     */
    protected $format;

    /**
     * Module selected for get request
     *
     * @var string
     */
    protected $module;

    /**
     * Construct
     *
     * @param string $authtoken      Token for connection
     * @param Client $zohoRestClient Guzzl Client for connection [optional]
     */
    public function __construct($authtoken, Client $zohoRestClient = null)
    {
        $this->authtoken = $authtoken;
        // Only XML format is supported for the time being
        $this->format = 'xml';
        $this->zohoRestClient = $zohoRestClient ?: new Client();
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
    public function convertLead($module, $leadId, $data, $params = array())
    {
        $params['leadId'] = $leadId;
        $params['newFormat'] = 1;

        return $this->call($module, 'convertLead', $params, $data);
    }

    /**
     * Implements getCVRecords API method.
     *
     * @param  string   $name    name of the Custom View
     * @param  array    $params  request parameters
     *                           selectColumns     String  Module(optional columns) i.e, leads(Last Name,Website,Email) OR All
     *                           fromIndex         Integer Default value 1
     *                           toIndex           Integer Default value 20
     *                           Maximum value 200
     *                           lastModifiedTime  DateTime  Default value: null
     *                           If you specify the time, modified data will be fetched after the configured time.
     *                           newFormat         Integer 1 (default) - exclude fields with null values in the response
     *                           2 - include fields with null values in the response
     *                           version           Integer 1 (default) - use earlier API implementation
     *                           2 - use latest API implementation
     * @return Response The Response object
     */
    public function getCVRecords($module, $name, $params = array())
    {
        $params['cvName'] = $name;
        $params['newFormat'] = 1;

        return $this->call($module, 'getCVRecords', $params);
    }

    /**
     * Implements getFields API method.
     *
     * @return Response The Response object
     */
    public function getFields($module)
    {
        $params['newFormat'] = 1;

        return $this->call($module, 'getFields', array());
    }

    /**
     * Implements deleteRecords API method.
     *
     * @param string $id Id of the record
     *
     * @return Response The Response object
     */
    public function deleteRecords($module, $id)
    {
        $params['id'] = $id;
        $params['newFormat'] = 1;

        return $this->call($module, 'deleteRecords', $params);
    }

    /**
     * Implements getRecordById API method.
     *
     * @param  string   $id      Id of the record
     * @param  array    $params  request parameters
     *                           newFormat 1 (default) - exclude fields with null values in the response
     *                           2 - include fields with null values in the response
     *                           version   1 (default) - use earlier API implementation
     *                           2 - use latest API implementation
     * @return Response The Response object
     */
    public function getRecordById($module, $id, $params = array())
    {
        $params['id'] = $id;
        if (empty($params['newFormat'])) {
            $params['newFormat'] = 2;
        }

        return $this->call($module, 'getRecordById', $params);
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
    public function getRecords($module, $params = array())
    {
        $params['newFormat'] = 1;

        return $this->call($module, 'getRecords', $params);
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
    public function searchRecords($module, $searchCondition, $params = array())
    {
        $params['criteria'] = $searchCondition;
        $params['newFormat'] = 1;

        return $this->call($module, 'searchRecords', $params);
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
     * Make the call using the client
     *
     * @param  string   $module  The module to use
     * @param  string   $command Command to call
     * @param  array    $params  Options
     * @param  array    $data    Data to send [optional]
     * @param  array    $options Options to add for configurations [optional]
     * @return Response
     */
    protected function call($module, $command, $params, $data = array(), $options = array())
    {
        $uri = $this->getRequestURI($module, $command);
        $content = $this->getRequestContent($params, $data, $options);

        $request = $this->zohoRestClient->createRequest("POST", $uri);
        $request->getBody()->write($content["body"]);
        $query = $request->getQuery();
        foreach ($content["params"] as $param => $value) {
            $query[$param] = $value;
        }

        $response = $this->zohoRestClient->send($request);

        $zohoResponse =  new Response($response->getBody()->__toString(), $module, $command);

        if ($zohoResponse->ifSuccess()) {
            return $zohoResponse;
        } else {
            throw new ZohoCRMException($zohoResponse->getMessage(), $zohoResponse->getCode());
        }

        /*if ($response->isError()) {
            return false;
        } else {
            if ($this->format = "xml") {
                $zohoResponse =  new Response($response->getBody()->__toString(), $module, $command);

                if ($zohoResponse->ifSuccess()) {
                    return $zohoResponse;
                } else {
                    throw new ZohoCRMException($zohoResponse->getMessage(), $zohoResponse->getCode());
                }
            } elseif ($this->format = "json") {
                // FIXME: we should remove json support and do only XML.
                return $response->json();
            }
        }*/
    }

    /**
     * Get the current request uri
     *
     * @param  $module The module to use
     * @param  string $command Command for get uri
     * @return string
     */
    protected function getRequestURI($module, $command)
    {
        if (empty($module)) {
            throw new \RuntimeException('Zoho CRM module is not set.');
        }
        $parts = array(self::BASE_URI, $this->format, $module, $command);

        return implode('/', $parts);
    }

    /**
     * Get the content of the request
     *
     * @param  array  $additionnal_params Params
     * @param  string $data               Data
     * @param  array  $options            Data
     * @return string
     */
    protected function getRequestContent($additionnal_params, $data, $options)
    {
        $body = null;
        $params = array();
        $params['authtoken'] = $this->authtoken;
        $params['scope'] = 'crmapi';

        if (isset($additionnal_params["newFormat"])) {
            $params['newFormat'] = $additionnal_params["newFormat"];
        }
        if (isset($additionnal_params["id"])) {
            $params['id'] = $additionnal_params["id"];
        }
        if (isset($additionnal_params["parentModule"])) {
            $params['parentModule'] = $additionnal_params["parentModule"];
        }
        if (isset($additionnal_params["criteria"])) {
            $params['criteria'] = $additionnal_params["criteria"];
        }
        if (isset($additionnal_params["selectColumns"])) {
            $params['selectColumns'] = $additionnal_params["selectColumns"];
        }
        if (isset($additionnal_params["duplicateCheck"])) {
            $params['duplicateCheck'] = $additionnal_params["duplicateCheck"];
        }
        if (isset($additionnal_params["version"])) {
            $params['version'] = $additionnal_params["version"];
        }
        if (isset($options["postXMLData"]) && $options["postXMLData"]) {
            $body['xmlData'] = $data;
        } else {
            $params['xmlData'] = $data;
        }

        return ["params" => $params, "body" => $body];
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
