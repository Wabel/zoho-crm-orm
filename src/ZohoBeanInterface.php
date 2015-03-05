<?php
namespace Wabel\Zoho\CRM;

/**
 * Classes implementing this interface have a ZohoId.
 */
interface ZohoBeanInterface {

    /**
     * Returns the ZohoId of the bean.
     * @return string
     */
    public function getZohoId();

    /**
     * Sets the ZohoId of the bean.
     * @param string $id
     */
    public function setZohoId($id);

    /**
     * @return \DateTime The last time the record was modified in Zoho
     */
    public function getModifiedTime();

    /**
     * Sets the last time the record was modified in Zoho
     * @param \DateTime $time
     */
    public function setModifiedTime(\DateTime $time);
}