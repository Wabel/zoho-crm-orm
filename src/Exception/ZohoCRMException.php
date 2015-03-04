<?php namespace Wabel\Zoho\CRM\Exception;
use Wabel\Zoho\CRM\Request\Response;

/**
 * Zoho CRM Exception.
 *
 * Standard Exception thrown when using this package
 *
 * @package Wabel\Zoho\CRM\Exception
 * @version 1.0.0
 */
class ZohoCRMException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
