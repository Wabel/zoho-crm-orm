<?php
namespace Wabel\Zoho\CRM\Exception;

/**
 * Zoho CRM Exception triggered when an update fails. For instance, the ZohoID passed for a bean
 * does not exist in Zoho (because the record was deleted)
 *
 */
class ZohoCRMUpdateException extends ZohoCRMException
{
    private $failedBeans;

    /**
     * @param \SplObjectStorage $failedBeans Key: the bean that failed. Value: the associated exception.
     */
    public function __construct(\SplObjectStorage $failedBeans) {
        $this->failedBeans = $failedBeans;
        parent::__construct("Some beans could not be updated in Zoho.");
    }

    /**
     * @return \SplObjectStorage Key: the bean that failed. Value: the associated exception.
     */
    public function getFailedBeans() {
        return $this->failedBeans;
    }
}
