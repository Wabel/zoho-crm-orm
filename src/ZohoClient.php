<?php namespace Wabel\Zoho\CRM;

use Guzzle\Http\Client;
use Wabel\Zoho\CRM\Common\HttpClientInterface;
use Wabel\Zoho\CRM\Exception\ZohoCRMException;
use Wabel\Zoho\CRM\Request\HttpClient;
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
	 * @param string $authtoken Token for connection
	 * @param Client $zohoRestClient Guzzl Client for connection [optional]
	 */
	public function __construct($authtoken, Client $zohoRestClient = null)
	{
		$this->authtoken = $authtoken;
		// Only XML format is supported for the time being
		$this->format = 'xml';
		$this->zohoRestClient = $zohoRestClient ?: new Client(self::BASE_URI);
		return $this;
	}

	/**
	 * Implements convertLead API method.
	 *
	 * @param string $leadId  Id of the lead
	 * @param array $data     xmlData represented as an array
	 *                        array will be converted into XML before sending the request
	 * @param array $params   request parameters
	 *                        newFormat 1 (default) - exclude fields with null values in the response
	 *                                  2 - include fields with null values in the response
	 *                        version   1 (default) - use earlier API implementation
	 *                                  2 - use latest API implementation
	 * @param array $options Options to add for configurations [optional]
	 * @return Response The Response object
	 */
	public function convertLead($leadId, $data, $params = array(), $options = array())
	{
		$params['leadId'] = $leadId;
		$params['newFormat'] = 1;
		return $this->call('convertLead', $params, $data);
	}

	/**
	 * Implements getCVRecords API method.
	 *
	 * @param string $name    name of the Custom View
	 * @param array  $params  request parameters
	 *                        selectColumns     String  Module(optional columns) i.e, leads(Last Name,Website,Email) OR All
	 *                        fromIndex         Integer Default value 1
	 *                        toIndex           Integer Default value 20
	 *                                                  Maximum value 200
	 *                        lastModifiedTime  DateTime  Default value: null
	 *                                                    If you specify the time, modified data will be fetched after the configured time.
	 *                        newFormat         Integer 1 (default) - exclude fields with null values in the response
	 *                                                  2 - include fields with null values in the response
	 *                        version           Integer 1 (default) - use earlier API implementation
	 *                                                   2 - use latest API implementation
	 * @param array $options Options to add for configurations [optional]
	 * @return Response The Response object
	 */
	public function getCVRecords($name, $params = array(), $options = array())
	{
		$params['cvName'] = $name;
		$params['newFormat'] = 1;
		return $this->call('getCVRecords', $params);
	}

	/**
	 * Implements getFields API method.
	 *
	 * @return Response The Response object
	 */
	public function getFields()
	{
		$params['newFormat'] = 1;
		return $this->call('getFields', array());
	}

	/**
	 * Implements deleteRecords API method.
	 *
	 * @param string $id      Id of the record
	 *
	 * @return Response The Response object
	 */
	public function deleteRecords($id)
	{
		$params['id'] = $id;
		$params['newFormat'] = 1;
		return $this->call('deleteRecords', $params);
	}

	/**
	 * Implements getRecordById API method.
	 *
	 * @param string $id      Id of the record
	 * @param array $params   request parameters
	 *                        newFormat 1 (default) - exclude fields with null values in the response
	 *                                  2 - include fields with null values in the response
	 *                        version   1 (default) - use earlier API implementation
	 *                                  2 - use latest API implementation
	 * @param array $options Options to add for configurations [optional]
	 * @return Response The Response object
	 */
	public function getRecordById($id, $params = array(), $options = array())
	{
		$params['id'] = $id;
        if(empty($params['newFormat']))
            $params['newFormat'] = 2;
		return $this->call('getRecordById', $params);
	}

	/**
	 * Implements getRecords API method.
	 *
	 * @param array $params   request parameters
	 *                        selectColumns     String  Module(optional columns) i.e, leads(Last Name,Website,Email) OR All
	 *                        fromIndex	        Integer	Default value 1
	 *                        toIndex	          Integer	Default value 20
	 *                                                  Maximum value 200
	 *                        sortColumnString	String	If you use the sortColumnString parameter, by default data is sorted in ascending order.
	 *                        sortOrderString	  String	Default value - asc
	 *                                          if you want to sort in descending order, then you have to pass sortOrderString=desc.
	 *                        lastModifiedTime	DateTime	Default value: null
	 *                                          If you specify the time, modified data will be fetched after the configured time.
	 *                        newFormat         Integer	1 (default) - exclude fields with null values in the response
	 *                                                  2 - include fields with null values in the response
	 *                        version           Integer	1 (default) - use earlier API implementation
	 *                                                  2 - use latest API implementation
	 * @param array $options Options to add for configurations [optional]
	 * @return Response The Response object
	 */
	public function getRecords($params = array(), $options = array())
	{
		$params['newFormat'] = 1;
		return $this->call('getRecords', $params);
	}

	/**
	 * Implements getRecords API method.
	 *
	 * @param array $params   request parameters
	 *                        selectColumns     String  Module(optional columns) i.e, leads(Last Name,Website,Email) OR All
	 *                        fromIndex	        Integer	Default value 1
	 *                        toIndex	          Integer	Default value 20
	 *                                                  Maximum value 200
	 *                        sortColumnString	String	If you use the sortColumnString parameter, by default data is sorted in ascending order.
	 *                        sortOrderString	  String	Default value - asc
	 *                                          if you want to sort in descending order, then you have to pass sortOrderString=desc.
	 *                        lastModifiedTime	DateTime	Default value: null
	 *                                          If you specify the time, modified data will be fetched after the configured time.
	 *                        newFormat         Integer	1 (default) - exclude fields with null values in the response
	 *                                                  2 - include fields with null values in the response
	 *                        version           Integer	1 (default) - use earlier API implementation
	 *                                                  2 - use latest API implementation
	 * @param array $options Options to add for configurations [optional]
	 * @return Response The Response object
	 */
	public function getRelatedRecords($id, $parentModule, $params = array(), $options = array())
	{
        $params["id"] = $id;
        $params["parentModule"] = $parentModule;
		$params['newFormat'] = 1;
		return $this->call('getRelatedRecords', $params);
	}

	/**
	 * Implements searchRecords API method.
	 *
	 * @param string $searchCondition search condition in the format (fieldName:searchString)
	 *                                e.g. (Email:*@sample.com*)
	 * @param array $params           request parameters
	 *                                selectColumns String  Module(columns) e.g. Leads(Last Name,Website,Email)
	 *                                                      Note: do not use any extra spaces when listing column names
	 *                                fromIndex	    Integer	Default value 1
	 *                                toIndex	      Integer	Default value 20
	 *                                                      Maximum value 200
	 *                                newFormat     Integer 1 (default) - exclude fields with null values in the response
	 *                                                      2 - include fields with null values in the response
	 *                                version       Integer 1 (default) - use earlier API implementation
	 *                                                      2 - use latest API implementation
	 *
	 * @return Response The Response object
	 */
	public function searchRecords($searchCondition, $params = array(), $options = array())
	{
		$params['criteria'] = $searchCondition;
		$params['newFormat'] = 1;
		return $this->call('searchRecords', $params);
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
	 * @param integer $newFormat  1 (default) - exclude fields with null values in the response
	 *                            2 - include fields with null values in the response
	 *
	 * @return Response The Response object
	 */
	public function getUsers($type = 'AllUsers', $newFormat = 1)
	{
		$params['type'] = $type;
		$params['newFormat'] = $newFormat;
		return $this->call('getUsers', $params);
	}

	/**
	 * Implements insertRecords API method.
	 *
	 * @param array $data     xmlData represented as an array
	 *                        array will be converted into XML before sending the request
	 * @param array $params   request parameters
	 *                        wfTrigger	      Boolean	Set value as true to trigger the workflow rule
	 *                                          while inserting record into CRM account. By default, this parameter is false.
	 *                        duplicateCheck	Integer	Set value as "1" to check the duplicate records and throw an
	 *                                                error response or "2" to check the duplicate records, if exists, update the same.
	 *                        isApproval	    Boolean	By default, records are inserted directly . To keep the records in approval mode,
	 *                                                set value as true. You can use this parameters for Leads, Contacts, and Cases module.
	 *                        newFormat       Integer	1 (default) - exclude fields with null values in the response
	 *                                                2 - include fields with null values in the response
	 *                        version         Integer	1 (default) - use earlier API implementation
	 *                                                2 - use latest API implementation
	 *                                                4 - enable duplicate check functionality for multiple records.
	 *                                                It's recommended to use version 4 for inserting multiple records
	 *                                                even when duplicate check is turned off.
	 *
	 * @param array $options Options to add for configurations [optional]
	 * @return Response The Response object
     * @todo Use full SimpleXMLRequest in data to check number easily and set default parameters
	 */
	public function insertRecords($data, $params = array(), $options = array())
	{
    if (!isset($params['duplicateCheck'])) {
        $params['duplicateCheck'] = 2;
    }
    if (!$params['version']) {
        // Version 4 is mandatory for updating multiple records.
        $params['version'] = 4;
    }
	$params['newFormat'] = 1;
    return $this->call('insertRecords', $params, $data, $options);
	}

	/**
	 * Implements updateRecords API method.
	 *
	 * @param string $id       unique ID of the record to be updated
	 * @param array  $data     xmlData represented as an array
	 *                         array will be converted into XML before sending the request
	 * @param array  $params   request parameters
	 *                         wfTrigger    Boolean   Set value as true to trigger the workflow rule
	 *                                                while inserting record into CRM account. By default, this parameter is false.
	 *                         newFormat    Integer   1 (default) - exclude fields with "null" values while updating data
	 *                                                2 - include fields with "null" values while updating data
	 *                         version      Integer   1 (default) - use earlier API implementation
	 *                                                2 - use latest API implementation
	 *                                                4 - update multiple records in a single API method call
	 *
	 * @param array $options Options to add for configurations [optional]
	 * @return Response The Response object
     * @todo Use full SimpleXMLRequest in data to check number easily and set default parameters
	 */
	public function updateRecords($data, $id = null, $params = array(), $options = array())
	{
        if ($id) {
            $params['id'] = $id;
            $params['version'] = isset($params['version']) ? $params['version'] : 1;
        }
		elseif (!isset($params['version']) || $params['version'] == 4) {
            $params['version'] = 4;
            if(!isset($options['postXMLData'])) {
                $options['postXMLData'] = true;
            }
		}
        else {
            throw new \InvalidArgumentException('Record Id is required and cannot be empty.');
        }
		$params['newFormat'] = 1;

		return $this->call('updateRecords', $params, $data, $options);
	}

	/**
	 * Implements uploadFile API method.
	 *
	 * @param string 			$id       	 unique ID of the record to be updated
	 *
	 * @param file path		 	$content     Pass the File Input Stream of the file
	 *
	 * @param array  $params   request parameters
	 *                         wfTrigger    Boolean   Set value as true to trigger the workflow rule
	 *                                                while inserting record into CRM account. By default, this parameter is false.
	 *                         newFormat    Integer   1 (default) - exclude fields with "null" values while updating data
	 *                                                2 - include fields with "null" values while updating data
	 *                         version      Integer   1 (default) - use earlier API implementation
	 *                                                2 - use latest API implementation
	 *                                                4 - update multiple records in a single API method call
	 *
	 * @return Response The Response object
	 */
	public function uploadFile($id, $content, $params = array())
	{
		if (empty($id)) {
			throw new \InvalidArgumentException('Record Id is required and cannot be empty.');
		}
		$params['id'] = $id;
		$params['content'] = $content;
		return $this->call('uploadFile', $params);
	}

	/**
	 * Implements downloadFile API method.
	 *
	 * @param string $id unique ID of the attachment
	 *
	 * @return Response The Response object
	 */
	public function downloadFile($id, $params = array())
	{
		if (empty($id)) {
			throw new \InvalidArgumentException('Record Id is required and cannot be empty.');
		}
		$params['id'] = $id;
		return $this->call('downloadFile', $params);
	}

	/**
	 * Get the module
	 *
	 * @return string
	 */
	public function getModule()
	{
		return $this->module;
	}

	/**
	 * Set the model
	 *
	 * @param string $module Module to use
	 */
	public function setModule($module)
	{
		$this->module = $module;
	}

	/**
	 * Make the call using the client
	 *
	 * @param string $command Command to call
	 * @param string $params Options
	 * @param array $data Data to send [optional]
	 * @param array $options Options to add for configurations [optional]
	 * @return Response
	 */
	protected function call($command, $params, $data = array(), $options = array())
	{
		$uri = $this->getRequestURI($command);
		$content = $this->getRequestContent($params, $data, $options);

        $request = $this->zohoRestClient->createRequest("POST", $uri, null, $content["body"]);
        $query = $request->getQuery();
        foreach($content["params"] as $param => $value) {
            $query[$param] = $value;
        }

        $response = $request->send();
        if($response->isError()) {
            return false;
        }
        else {
            if($this->format = "xml") {
                try {
                    $zohoResponse =  new Response($response->getBody()->__toString(), $this->module, $command);

                    if($zohoResponse->ifSuccess()) {
                        return $zohoResponse;
                    }
                    else {
                        return ['code' => $zohoResponse->getCode(), "message" => $zohoResponse->getMessage()];
                    }
                }
                catch(\Exception $e) {
                    return ['code' => $e->getCode(), "message" => $e->getMessage()."<br><br>".$e->getTraceAsString()];
                }
            }
            elseif($this->format = "json") {
                return $response->json();
            }
        }
	}

	/**
	 * Get the current request uri
	 *
	 * @param string $command Command for get uri
	 * @return string
	 */
	protected function getRequestURI($command)
	{
		if (empty($this->module)) {
			throw new \RuntimeException('Zoho CRM module is not set.');
		} $parts = array($this->zohoRestClient->getBaseUrl(), $this->format, $this->module, $command);
		return implode('/', $parts);
	}

	/**
	 * Get the content of the request
	 *
	 * @param array $additionnal_params Params
	 * @param string $data Data
	 * @param array $options Data
	 * @return string
	 */
	protected function getRequestContent($additionnal_params, $data, $options)
	{
        $body = null;
        $params['authtoken'] = $this->authtoken;
		$params['scope'] = 'crmapi';

        if($additionnal_params["newFormat"]) {
            $params['newFormat'] = $additionnal_params["newFormat"];
        }
        if($additionnal_params["id"]) {
            $params['id'] = $additionnal_params["id"];
        }
        if($additionnal_params["parentModule"]) {
            $params['parentModule'] = $additionnal_params["parentModule"];
        }
        if($additionnal_params["criteria"]) {
            $params['criteria'] = $additionnal_params["criteria"];
        }
        if($additionnal_params["selectColumns"]) {
            $params['selectColumns'] = $additionnal_params["selectColumns"];
        }
        if($additionnal_params["duplicateCheck"]) {
            $params['duplicateCheck'] = $additionnal_params["duplicateCheck"];
        }
        if($additionnal_params["version"]) {
            $params['version'] = $additionnal_params["version"];
        }
		if (isset($options["postXMLData"]) && $options["postXMLData"]) {
			$body['xmlData'] = $data;
        }
        else {
            $params['xmlData'] = $data;
        }
		return ["params" => $params, "body" => $body];
	}

	/**
	 * Convert from array to XML
	 *
	 * @param array $data Data to convert
	 * @return XML
	 */
	protected function toXML($data)
	{
		$root = isset($data['root']) ? $data['root'] : $this->module;
		$no = 1;
		$xml = '<'. $root .'>';
		if (isset($data['options'])) {
			$xml .= '<row no="'. $no .'">';
			foreach ($data['options'] as $key => $value) {
				$xml .= '<option val="'. $key .'">'. $value .'</option>';
			}
			$xml .= '</row>';
			$no++;
		}
		foreach ($data['records'] as $row) {
			$xml .= '<row no="'. $no .'">';
			foreach ($row as $key => $value) {
				if (is_array($value)) {
					$xml .= '<FL val="'. $key .'">';
					foreach ($value as $k => $v) {
						list($tag, $attribute) = explode(' ', $k);
						$xml .= '<'. $tag .' no="'. $attribute .'">';
						foreach ($v as $kk => $vv) {
							$xml .= '<FL val="'. $kk .'"><![CDATA['. $vv .']]></FL>';
						}
						$xml .= '</'. $tag .'>';
					}
					$xml .= '</FL>';
				} else {
					$xml .= '<FL val="'. $key .'"><![CDATA['. $value .']]></FL>';
				}
			}
			$xml .= '</row>';
			$no++;
		}
		$xml .= '</'. $root .'>';
		return $xml;
	}

	/**
	 * Convert an entity into XML
	 *
	 * @param Element $entity Element with values on fields setted
	 * @return string XML created
	 * @todo
	 		- Add iteration for multiples entities and creation of xml with collection
	 */
	public function mapEntity(Element $entity, $no = null)
	{
		if(empty($this->module))
			throw new \Exception("Invalid module, it must be setted before map the entity", 1);
		$element = new \ReflectionObject($entity);
		$properties = $element->getProperties();
        $xml = $no ? '' : '<'.$this->module.'>'."\n";
		$xml .= '<row no="'. ($no ? $no : '1') .'">'."\n";
		foreach ($properties as $property)
		{
			$propName = $property->getName();
			$propValue = $entity->$propName;
            // Avoid the $this->moduele attribute
			if(!empty($propValue) && $propName !== "module") {

                // Dealing with the customs zoho attributes
                if($propName === "customs" && is_array($propValue)) {
                    foreach($propValue as $name => $value) {
                        if(htmlspecialchars($value) !== $value) {
                            $value = htmlspecialchars($value);
                        }
                        $xml .= '<FL val="'.str_replace(['_', 'N36', 'E5F'], [' ', '$', '_'], $name).'">'.$value.'</FL>'."\n";
                    }
                }
                else {
                    if(htmlspecialchars($propValue) !== $propValue && $propName !== "Account Name") {
                        $propValue = htmlspecialchars($propValue);
                    }
                    $xml .= '<FL val="'.str_replace(['_', 'N36', 'E5F'], [' ', '$', '_'], $propName).'">'.$propValue.'</FL>'."\n";
                }
            }
		} $xml .= '</row>'."\n";
        $xml .= $no ? '' :  '</'.$this->module.'>';
		return $xml;
	}

	/**
	 * Convert an array of entities into XML
	 *
	 * @param Element[] $entities Element with values on fields setted
	 * @return string XML created
	 */
	public function mapEntities(array $entities)
	{
		if(empty($this->module))
			throw new \Exception("Invalid module, it must be setted before map the entity", 1);

        $no = 1;
        $xml = '<'.$this->module.'>'."\n";
        foreach($entities as $entity) {
            $xml .= $this->mapEntity($entity, $no++);
        }
        $xml .= '</'.$this->module.'>';
        return $xml;
	}
}
