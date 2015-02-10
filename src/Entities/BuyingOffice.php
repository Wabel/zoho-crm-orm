<?php namespace Wabel\Zoho\CRM\Entities;

use Wabel\Zoho\CRM\Wrapper\Element;

/**
 * Entity for accounts inside Zoho
 * This class only have default parameters
 *
 * @package Wabel\Zoho\CRM\Entities
 * @version 1.0.0
 */
class BuyingOffice extends Element
{
	/**
	 * Name of the BuyingOffice
	 * 
	 * @var string
	 */
	private $CustomModule4_Name;

	/**
	 * Name of the BuyingOffice's account owner
	 *
	 * @var string
	 */
	private $CustomModule4_Owner;

	/**
	 * Time of the BuyingOffice last activity on the platform
	 *
	 * @var string
	 */
	private $Last_Activity_Time;

    public function __construct(array $fields = []) {
        parent::__construct("CustomModule4", $fields);
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