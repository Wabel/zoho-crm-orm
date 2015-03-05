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
}