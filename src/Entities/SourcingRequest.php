<?php namespace Wabel\Zoho\CRM\Entities;

use Wabel\Zoho\CRM\Wrapper\Element;

/**
 * Entity for accounts inside Zoho
 * This class only have default parameters
 *
 * @package Wabel\Zoho\CRM\Entities
 * @version 1.0.0
 */
class SourcingRequest extends Element
{
	/**
	 * Name of the SourcingRequest
	 * 
	 * @var string
	 */
	private $CustomModule3_Name;

	/**
	 * Name of the SourcingRequest's account owner
	 *
	 * @var string
	 */
	private $CustomModule3_Owner;

	/**
	 * Currency of the SourcingRequest
	 *
	 * @var string
	 */
	private $Currency;

	/**
	 * Email of the SourcingRequest referent
	 *
	 * @var string
	 */
	private $Email;

	/**
	 * Echange rate of the SourcingRequest
	 *
	 * @var string
	 */
	private $Exchange_Rate;

	/**
	 * Last action on the SourcingRequest
	 *
	 * @var string
	 */
	private $Last_Activity_Time;

	/**
	 * Secondary Email of the SourcingRequest
	 *
	 * @var string
	 */
	private $Secondary_Email;

    public function __construct(array $fields = []) {
        parent::__construct("CustomModule3", $fields);
    }

	/**
	 * Getter
	 * 
	 * @return mixed
	 */
	public function __get($property)
	{
		return isset($this->$property)?$this->$property :null;
	}

	/**
	 * Setter
	 *
	 * @param string $property Name of the property to set the value
	 * @param mixed $value Value for the property
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		$this->$property = $value;
		return $this->$property;
	}	
}