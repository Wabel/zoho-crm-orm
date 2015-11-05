<?php

namespace Wabel\Zoho\CRM;

/**
 * Classes implementing this interface have a ZohoId.
 */
interface ZohoBeanInterface
{
    /**
     * Returns the ZohoId of the bean.
     *
     * @return string
     */
    public function getZohoId();

    /**
     * Sets the ZohoId of the bean.
     *
     * @param string $id
     */
    public function setZohoId($id);

    /**
     * @return \DateTime The time the record was created in Zoho
     */
    public function getCreatedTime();

    /**
     * Sets the time the record was created in Zoho.
     *
     * @param \DateTime $time
     */
    public function setCreatedTime(\DateTime $time);

    /**
     * @return \DateTime The last time the record was modified in Zoho
     */
    public function getModifiedTime();

    /**
     * Sets the last time the record was modified in Zoho.
     *
     * @param \DateTime $time
     */
    public function setModifiedTime(\DateTime $time);

    /**
     * Returns whether a property is changed or not.
     *
     * @param mixed $name
     * @return bool
     */
    public function isDirty($name);
}
