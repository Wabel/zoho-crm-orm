<?php namespace Wabel\Zoho\CRM\Request;

use Wabel\Zoho\CRM\Common\FactoryInterface;
use Wabel\Zoho\CRM\Request\Response;

/**
 * Interface for create response objects
 *
 * @package Wabel\Zoho\CRM\Request
 * @implements FactoryInterface
 * @version 1.0.0
 */
class Factory implements FactoryInterface
{
  public function createResponse($xml, $module, $method)
  {
    return new Response($xml, $module, $method);
  }
}