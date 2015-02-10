<?php namespace Wabel\Zoho\CRM\Entities;

use Wabel\Zoho\CRM\Wrapper\Element;

/**
 * Entity for contacts inside Zoho
 * This class only have default parameters
 *
 * @version 1.0.0
 */
class Contact extends Element
{
    /**
	 * Currency used by the Contact.
	 * 
	 * @var string
	 */
	public $Currency;

    /**
	 * Currency used by the Contact.
	 *
	 * @var int
	 */
	public $Exchange_Rate;

    /**
	 * Birthdate of the Contact.
	 *
	 * @var string
	 */
	public $Date_of_Birth;

    /**
	 * Department of the Contact.
	 *
	 * @var string
	 */
	public $Department;

    /**
	 * Zoho Account Name of the Contact.
	 *
	 * @var string
	 */
	public $Account_Name;

    /**
     * Name of the user to whom the Contact is assigned.
     *
     * @var string
     */
    private $Contact_Owner;

    /**
	 * Define if the user should be added to QuickBook.
	 *
	 * @var bool
	 */
	public $Add_To_QuickBooks;

    /**
	 * Home phone number of the Contact
	 *
	 * @var bool
	 */
	public $Home_Phone;

    /**
	 * Name of this Contact's assistant.
	 *
	 * @var string
	 */
	public $Assistant;

    /**
	 * Phone of this Contact's assistant.
	 *
	 * @var string
	 */
	public $Asst_Phone;

    /**
	 * Zoho CRM user to whom the Contact is assigned.
	 *
	 * @var string
	 */
	public $Lead_Owner;

	/**
	 * Salutation for the Contact
	 * 
	 * @var string
	 */
	public $Salutation;

	/**
	 * First name of the Contact
	 * 
	 * @var string
	 */
	public $First_Name;

	/**
	 * The job position of the Contact
	 * 
	 * @var string
	 */
	public $Title;

	/**
	 * Last name of the Contact
	 * 
	 * @var string
	 */	
	public $Last_Name;

	/**
	 * Source of the lead, that is, from where the Contact is generated
	 * 
	 * @var string
	 */
	public $Lead_Souce;

	/**
	 * Phone number of the lead
	 * 
	 * @var string
	 */
	public $Phone;

	/**
	 * Modile number of the lead
	 * 
	 * @var string
	 */	
	public $Mobile;

	/**
	 * Fax number of the Contact
	 * 
	 * @var string
	 */	
	public $Fax;

	/**
	 * Email address of the Contact
	 * 
	 * @var string
	 */	
	public $Email;

	/**
	 * Secundary email address of the Contact
	 * 
	 * @var string
	 */	
	public $Secundary_Email;

	/**
	 * Skype ID of the Contact. Currently skype ID
	 * can be in the range of 6 to 32 characters
	 * 
	 * @var string
	 */
	public $Skype_ID;

	/**
	 * Remove Contact from your mailing list so that they will
	 * not receive any emails from your Zoho CRM account
	 * 
	 * @var string
	 */
	public $Email_Opt_Out;

	/**
	 * Street address of the Contact
	 * 
	 * @var string
	 */
	public $Other_Street;

	/**
	 * Name of the city where the Contact lives
	 * 
	 * @var string
	 */
	public $Other_City;

    /**
     * Phone number of the Contact
     *
     * @var string
     */
    public $Other_Phone;

	/**
	 * Name of the state where the Contact lives
	 * 
	 * @var string
	 */
	public $Other_State;

	/**
	 * Postal code of the Contact's address
	 * 
	 * @var string
	 */
	public $Other_Zip_Code;

	/**
	 * Name of the Contact's country
	 * 
	 * @var string
	 */
	public $Other_Country;

	/**
	 * Street address of the Contact
	 *
	 * @var string
	 */
	public $Mailing_Street;

	/**
	 * Name of the city where the Contact lives
	 *
	 * @var string
	 */
	public $Mailing_City;

	/**
	 * Name of the state where the Contact lives
	 *
	 * @var string
	 */
	public $Mailing_State;

	/**
	 * Postal code of the Contact's address
	 *
	 * @var string
	 */
	public $Mailing_Zip_Code;

	/**
	 * Name of the Contact's country
	 *
	 * @var string
	 */
	public $Mailing_Country;

	/**
	 * Other details about the Contact
	 * 
	 * @var string
	 */
	public $Description;

	/**
	 * Last Activity Time of the Contact
	 *
	 * @var string
	 */
	public $Last_Activity_Time;

	/**
	 * Who the Contact is reporting to
	 *
	 * @var string
	 */
	public $Reports_To;

	/**
	 * Twitter alias of the Contact
	 *
	 * @var string
	 */
	public $Twitter;

    public function __construct(array $fields = []) {
        parent::__construct("Contacts", $fields);
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