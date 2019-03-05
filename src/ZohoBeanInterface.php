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
     * @return \DateTimeImmutable The time the record was created in Zoho
     */
    public function getCreatedTime();

    /**
     * Sets the time the record was created in Zoho.
     *
     * @param \DateTimeImmutable $time
     */
    public function setCreatedTime(\DateTimeImmutable $time);

    /**
     * @return \DateTimeImmutable The last time the record was modified in Zoho
     */
    public function getModifiedTime();

    /**
     * Sets the last time the record was modified in Zoho.
     *
     * @param \DateTimeImmutable $time
     */
    public function setModifiedTime(\DateTimeImmutable $time);



    /**
     * @return string  User ID who created the entity  in Zoho
     */
    public function getCreatedByOwnerID();

    /**
     * Sets the  User ID who created the entity in Zoho.
     *
     * @param string $createdByID
     */
    public function setCreatedByOwnerID($createdByID);

    /**
     * @return string  User ID who modified the entity  in Zoho
     */
    public function getModifiedByOwnerID();

    /**
     * Sets the  User ID who modified the entity in Zoho.
     *
     * @param string $modifiedByID
     */
    public function setModifiedByOwnerID($modifiedByID);

    /**
     * @return string the User name who created the entity in Zoho
     */
    public function getCreatedByOwnerName();

    /**
     * Sets the  User name who created the entity in Zoho.
     *
     * @param string $name
     */
    public function setCreatedByOwnerName($name);

    /**
     * @return string the User name who modified the entity in Zoho
     */
    public function getModifiedByOwnerName();

    /**
     * Sets the User name who modified the entity in Zoho
     *
     * @param string $name
     */
    public function setModifiedByOwnerName($name);


    /**
     * @return \DateTimeImmutable The last time the record was modified in Zoho
     */
    public function getLastActivityTime();

    /**
     * Sets the last time the record was modified in Zoho.
     *
     * @param \DateTimeImmutable $time
     */
    public function setLastActivityTime(\DateTimeImmutable $time);


    /**
     * @return string the Owner ID in Zoho
     */
    public function getOwnerOwnerID();

    /**
     * Sets the  Owner ID in Zoho.
     *
     * @param string $ownerID
     */
    public function setOwnerOwnerID($ownerID);

    /**
     * @return string the Owner name in Zoho
     */
    public function getOwnerOwnerName();

    /**
     * Sets the  Owner name in Zoho.
     *
     * @param string $name
     */
    public function setOwnerOwnerName($name);

    /**
     * Returns the wrapped Zoho CRM Record .
     *
     * @return \ZCRMRecord
     */
    public function getZCRMRecord();

    /**
     * Sets the Zoho CRM Record for wrapping in the zohoBean Object.
     * @param \ZCRMRecord $record
     */
    public function setZCRMRecord(\ZCRMRecord $record);

    /**
     * Returns whether a property is changed or not.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isDirty($name);


    /**
     * Returns whether a property is changed or not.
     *
     * @param string $name
     * @param bool $status
     *
     * @return bool
     */
    public function setDirty($name, $status);
}
