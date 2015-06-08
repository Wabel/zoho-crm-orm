<?php
namespace Wabel\Zoho\CRM\Exception;
use Wabel\Zoho\CRM\ZohoBeanInterface;

/**
 * Zoho CRM Exception triggered when an update fails. For instance, the ZohoID passed for a bean
 * does not exist in Zoho (because the record was deleted)
 *
 */
class ZohoCRMUpdateException extends ZohoCRMException
{
    private $failedBeans;
    private $errorMessage = "Some beans could not be updated in Zoho.";

    /**
     * @param \SplObjectStorage $failedBeans Key: the bean that failed. Value: the associated exception.
     */
    public function __construct(\SplObjectStorage $failedBeans) {
        $this->failedBeans = $failedBeans;

        /**
         * Building the error message by iterating the SplObjectStorage
         * @var ZohoBeanInterface $bean
         * @var ZohoCRMException $error
         */
        foreach($this->failedBeans AS $key) {
            $this->errorMessage .= "\n"."[".$this->getFailedBeans()->current()->getZohoId()."] ".$this->getFailedBeans()->getInfo()->getMessage();
        }

        // Repositioning the pointer at the beginning
        $this->failedBeans->rewind();

        parent::__construct($this->errorMessage);
    }

    /**
     * @return \SplObjectStorage Key: the bean that failed. Value: the associated exception.
     */
    public function getFailedBeans() {
        return $this->failedBeans;
    }
}
