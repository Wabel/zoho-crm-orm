<?php namespace Wabel\Zoho\CRM\Common;

/**
 * This interface needs to be implemented in your code. Every Bean Class that could be saved on Zoho should implement this Interface.
 *
 * Interface MappingInterface
 * @package Wabel\Zoho\CRM\Common
 */
interface MappingInterface {

    /**
     * Says if the bean passed in argument can be converted into a Zoho Bean
     *
     * @return bool
     */
    public function canHandleBean();


    /**
     * Returns a Zoho Bean based on the bean passed in argument
     *
     * @param
     * @return bool
     */
    public function toZohoBean($applicationBean);


    /**
     * Returns a Zoho Bean based on the bean passed in argument
     *
     * @param
     * @return bool
     */
    public function toApplicationBean($zohoBean);
}