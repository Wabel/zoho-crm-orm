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
    public function getCreatedBy_OwnerID();

    /**
     * Sets the  User ID who created the entity in Zoho.
     *
     * @param string $createdByID
     */
    public function setCreatedBy_OwnerID($createdByID);

    /**
     * @return string  User ID who modified the entity  in Zoho
     */
    public function getModifiedBy_OwnerID();

    /**
     * Sets the  User ID who modified the entity in Zoho.
     *
     * @param string $modifiedByID
     */
    public function setModifiedBy_OwnerID($modifiedByID);

    /**
     * @return string the User name who created the entity in Zoho
     */
    public function getCreatedBy_OwnerName();

    /**
     * Sets the  User name who created the entity in Zoho.
     *
     * @param string $name
     */
    public function setCreatedBy_OwnerName($name);

    /**
     * @return string the User name who modified the entity in Zoho
     */
    public function getModifiedBy_OwnerName();

    /**
     * Sets the User name who modified the entity in Zoho
     *
     * @param string $name
     */
    public function setModifiedBy_OwnerName($name);


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
    public function getOwner_OwnerID();

    /**
     * Sets the  Owner ID in Zoho.
     *
     * @param string $ownerID
     */
    public function setOwner_OwnerID($ownerID);

    /**
     * @return string the Owner name in Zoho
     */
    public function getOwner_OwnerName();

    /**
     * Sets the  Owner name in Zoho.
     *
     * @param string $name
     */
    public function setOwner_OwnerName($name);

    /**
     * Returns the wrapped Zoho CRM Record .
     *
     * @return \zcrmsdk\crm\crud\ZCRMRecord
     */
    public function getZCRMRecord();

    /**
     * Sets the Zoho CRM Record for wrapping in the zohoBean Object.
     *
     * @param \zcrmsdk\crm\crud\ZCRMRecord $record
     */
    public function setZCRMRecord(\zcrmsdk\crm\crud\ZCRMRecord $record);

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
     * @param bool   $status
     *
     * @return bool
     */
    public function setDirty($name, $status);
}
