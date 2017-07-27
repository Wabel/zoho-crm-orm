<?php

namespace Wabel\Zoho\CRM;

use GuzzleHttp\Client;
use Wabel\Zoho\CRM\Exception\ZohoCRMResponseException;
use Wabel\Zoho\CRM\Request\Response;

/**
 * Client for provide interface with Zoho CRM.
 *
 * TODO : Add comments (a lot)
 */
class ZohoClient
{
    /**
     * URL for call request.
     *
     * @var string
     */
    protected $BASE_URI = 'https://crm.zoho.com/crm/private';

    /**
     * Token used for session of request.
     *
     * @var string
     */
    protected $authtoken;

    /**
     * Instance of the client.
     *
     * @var Client
     */
    protected $zohoRestClient;

    /**
     * Format selected for get request.
     *
     * @var string
     */
    protected $format;

    /**
     * Module selected for get request.
     *
     * @var string
     */
    protected $module;

    /**
     * Construct.
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

    public function setEuDomain() {
    	$this->BASE_URI = 'https://crm.zoho.eu/crm/private';
    }

    /**
     * Implements convertLead API method.
     *
     * @param $leadId
     * @param $data
     * @param array $params
     *
     * @return Response The Response object
     *
     * @throws ZohoCRMResponseException
     */
    public function convertLead($leadId, $data, $params = array())
    {
        $module = 'Leads';
        $params['leadId'] = $leadId;
        $params['newFormat'] = 1;

        return $this->call($module, 'convertLead', $params, $data);
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
     * @param string $module
     * @param string $id     Id of the record
     *
     * @return Response The Response object
     *
     * @throws ZohoCRMResponseException
     */
    public function deleteRecords($module, $id)
    {
        $params = array(
            'id' => $id,
            'newFormat' => 1,
        );

        return $this->call($module, 'deleteRecords', $params);
    }

    /**
     * Implements getRecordById API method.
     *
     * @param string $module The module to use
     * @param string $id     Id of the record or a list of IDs separated by a semicolon
     *
     * @return Response The Response object
     *
     * @throws ZohoCRMResponseException
     */
    public function getRecordById($module, $id)
    {
        if (strpos($id, ';') === false) {
            $params['id'] = $id;
        } else {
            $params['idlist'] = $id;
        }
        $params['newFormat'] = 1;

        return $this->call($module, 'getRecordById', $params);
    }

    /**
     * Implements getRecords API method.
     *
     * @param $module
     * @param $sortColumnString
     * @param $sortOrderString
     * @param \DateTime $lastModifiedTime
     * @param $selectColumns
     * @param $fromIndex
     * @param $toIndex
     *
     * @return Response The Response object
     *
     * @throws ZohoCRMResponseException
     */
    public function getRecords($module, $sortColumnString = null, $sortOrderString = null, \DateTimeInterface $lastModifiedTime = null, $selectColumns = null, $fromIndex = null, $toIndex = null)
    {
        $params['newFormat'] = 1;
        $params['version'] = 1;
        if ($selectColumns) {
            $params['selectColumns'] = $selectColumns;
        }
        if ($fromIndex) {
            $params['fromIndex'] = $fromIndex;
        }
        if ($toIndex) {
            $params['toIndex'] = $toIndex;
        }
        if ($sortColumnString) {
            $params['sortColumnString'] = $sortColumnString;
        }
        if ($sortOrderString) {
            $params['sortOrderString'] = $sortOrderString;
        }
        if ($lastModifiedTime) {
            $params['lastModifiedTime'] = $lastModifiedTime->format('Y-m-d H:i:s');
        }

        return $this->call($module, 'getRecords', $params);
    }

    /**
     * Implements getDeletedRecordIds API method.

     *
     * @param string             $module
     * @param \DateTimeInterface $lastModifiedTime
     * @param int                $fromIndex
     * @param int                $toIndex
     *
     * @return Response
     *
     * @throws ZohoCRMResponseException
     */
    public function getDeletedRecordIds($module, \DateTimeInterface $lastModifiedTime = null, $fromIndex = null, $toIndex = null)
    {
        $params = [];
        if ($fromIndex) {
            $params['fromIndex'] = $fromIndex;
        }
        if ($toIndex) {
            $params['toIndex'] = $toIndex;
        }
        if ($lastModifiedTime) {
            $params['lastModifiedTime'] = $lastModifiedTime->format('Y-m-d H:i:s');
        }

        return $this->call($module, 'getDeletedRecordIds', $params);
    }

    /**
     * Implements getRecords API method.
     *
     * @param $module
     * @param $id
     * @param $parentModule
     * @param null $fromIndex
     * @param null $toIndex
     *
     * @return Response
     *
     * @throws ZohoCRMResponseException
     */
    public function getRelatedRecords($module, $id, $parentModule, $fromIndex = null, $toIndex = null)
    {
        $params['id'] = $id;
        $params['parentModule'] = $parentModule;
        $params['newFormat'] = 1;
        if ($fromIndex) {
            $params['fromIndex'] = $fromIndex;
        }
        if ($toIndex) {
            $params['toIndex'] = $toIndex;
        }

        return $this->call($module, 'getRelatedRecords', $params);
    }

    /**
     * Implements searchRecords API method.
     *
     * @param string    $module
     * @param string    $searchCondition
     * @param int       $fromIndex
     * @param int       $toIndex
     * @param \DateTime $lastModifiedTime
     * @param null      $selectColumns
     *
     * @return Response
     *
     * @throws ZohoCRMResponseException
     */
    public function searchRecords($module, $searchCondition = null, $fromIndex = null, $toIndex = null, $lastModifiedTime = null, $selectColumns = null)
    {
        if ($searchCondition) {
            $params['criteria'] = $searchCondition;
        } else {
            $params['criteria'] = '()';
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

        return $this->call($module, 'searchRecords', $params);
    }

    /**
     * Implements getUsers API method.
     *
     * @param string $type The type of users you want retrieve (among AllUsers, ActiveUsers, DeactiveUsers, AdminUsers and ActiveConfirmedAdmins)
     *
     * @return Response The array of Zoho Beans parsed from the response
     *
     * @throws ZohoCRMResponseException
     */
    public function getUsers($type = 'AllUsers')
    {
        switch ($type) {
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

        return $this->call('Users', 'getUsers', $params);
    }

    /**
     * Implements insertRecords API method.
     *
     * @param $module
     * @param \SimpleXMLElement$xmlData
     * @param bool $wfTrigger
     * @param int  $duplicateCheck
     * @param bool $isApproval
     *
     * @return Response
     *
     * @throws ZohoCRMResponseException
     */
    public function insertRecords($module, $xmlData, $wfTrigger = null, $duplicateCheck = null, $isApproval = null, $version = 4, $newFormat = 2)
    {
        if ($wfTrigger) {
            $params['wfTrigger'] = 'true';
        }
        if ($duplicateCheck) {
            $params['duplicateCheck'] = $duplicateCheck;
        }
        if ($isApproval) {
            $params['isApproval'] = 'true';
        }
        $params['newFormat'] = $newFormat;
        $params['version'] = $version;

        return $this->call($module, 'insertRecords', $params, ['xmlData' => $xmlData->asXML()]);
    }

    /**
     * Implements updateRecords API method.
     *
     * @param $module
     * @param \SimpleXMLElement $xmlData
     * @param string            $id
     * @param bool              $wfTrigger
     *
     * @return Response
     *
     * @throws ZohoCRMResponseException
     */
    public function updateRecords($module, $xmlData, $id = null, $wfTrigger = null, $version = 4, $newFormat = 2)
    {
        $params['newFormat'] = $newFormat;
        $params['version'] = $version;
        if ($wfTrigger) {
            $params['wfTrigger'] = 'true';
        }
        if ($id) {
            $params['id'] = $id;
        }

        return $this->call($module, 'updateRecords', $params, ['xmlData' => $xmlData->asXML()]);
    }

    /**
     * Implements updateRelatedRecords API method.
     *
     * @param $module
     * @param $relatedModule
     * @param \SimpleXMLElement $xmlData
     * @param string            $id
     * @param bool              $wfTrigger
     *
     * @return Response
     *
     * @throws ZohoCRMResponseException
     */
    public function updateRelatedRecords($module, $relatedModule, $xmlData, $id = null, $wfTrigger = null, $version = 4, $newFormat = 2)
    {
        $params['newFormat'] = $newFormat;
        $params['version'] = $version;
        $params['relatedModule'] = $relatedModule;
        if ($wfTrigger) {
            $params['wfTrigger'] = 'true';
        }
        if ($id) {
            $params['id'] = $id;
        }

        return $this->call($module, 'updateRelatedRecords', $params, ['xmlData' => $xmlData->asXML()]);
    }

    /**
     * Implements uploadFile API method.
     *
     * @param $module
     * @param $id
     * @param $content
     *
     * @return Response
     *
     * @throws ZohoCRMResponseException
     */
    public function uploadFile($module, $id, $content)
    {
        $params['id'] = $id;
        $params['content'] = $content;

        return $this->call($module, 'uploadFile', $params);
    }

    /**
     * Implements downloadFile API method.
     *
     * @param $module
     * @param $id
     *
     * @return Response
     *
     * @throws ZohoCRMResponseException
     */
    public function downloadFile($module, $id)
    {
        $params['id'] = $id;

        return $this->call($module, 'downloadFile', $params);
    }

    /**
     * Returns a list of modules from Zoho.
     */
    public function getModules()
    {
        return $this->call('Info', 'getModules', ['type' => 'api']);
    }
    
    /**
     * Make the call using the client.
     *
     * @param string                   $module  The module to use
     * @param string                   $command Command to call
     * @param array                    $params  Options
     * @param \SimpleXMLElement|string $data    Data to send [optional]
     * @param array                    $options Options to add for configurations [optional]
     *
     * @return Response
     */
    public function call($module, $command, $getParams = array(), $postParams = array())
    {
        $getParams['authtoken'] = $this->authtoken;
        $getParams['scope'] = 'crmapi';

        $uri = $this->getRequestURI($module, $command);
        $response = $this->zohoRestClient->post($uri, ['query'=>$getParams,'body'=> $postParams]);
        $zohoResponse = new Response((string)$response->getBody(), $module, $command);
        if ($zohoResponse->ifSuccess()) {
            return $zohoResponse;
        } else {
            throw new ZohoCRMResponseException($zohoResponse);
        }
    }

    /**
     * Get the current request uri.
     *
     * @param  $module The module to use
     * @param string $command Command for get uri
     *
     * @return string
     */
    protected function getRequestURI($module, $command)
    {
        if (empty($module)) {
            throw new \RuntimeException('Zoho CRM module is not set.');
        }
        $parts = array($this->BASE_URI, $this->format, $module, $command);

        return implode('/', $parts);
    }
}
