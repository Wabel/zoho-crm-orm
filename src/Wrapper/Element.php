<?php namespace Wabel\Zoho\CRM\Wrapper;

/**
 * Element class for extends Entities
 *
 * @package Wabel\Zoho\CRM\Wrapper
 * @version 1.0.0
 */
abstract class Element
{
    /**
     * The module name to generate
     * @var string $module
     */
    private $module;

    /**
     * The custom attributes of the module
     * @var array $customs
     */
    public $customs;

    public function __construct($module = "Leads", array $fields = [])
    {
        if (!empty($fields)) {
            $this->module = $module;
            $this->deserializeXml($this->serializeXml($fields));
        }
    }

    /**
     * The deserialize method is called during xml parsing,
     * create an object of the xml received based on the entity
     * called
     *
     * FIXME: see if we can get rid of this
     *
     * @param  string    $xmlstr XML string to convert on object
     * @throws Exception If xml data could not be parsed
     * @return boolean
     */
    final public function deserializeXml($xmlstr)
    {
        try {
            $element = new \SimpleXMLElement($xmlstr);
        } catch (\Exception $ex) {
            return false;
        }
        foreach ($element as $name => $value) {
            $name = str_replace(" ", "_", $name);
            if (property_exists(get_class($this), $name)) {
                $this->$name = stripslashes(urldecode(htmlspecialchars_decode($value)));
            } else {
                $this->customs[$name] = $value->__toString();
            }
        }

        return true;
    }

    /**
     * Called during array to xml parsing, create an string
     * of the xml to send for api based on the request values, for sustitution
     * of specials chars use E prefix instead of % for hexadecimal
     *
     * FIXME: see if we can get rid of this
     *
     * @param  array  $fields Fields to convert
     * @return string
     * @todo Use full SimpleXMLRequest
     */
    final public function serializeXml(array $fields)
    {
        $output = '<'.$this->module.'>';
        foreach ($fields as $key => $value) {
            if (empty($value)) {
                continue;
            } // Unnecessary fields
            $key = str_replace([' ', '$', '%5F', '/'], ['_', 'N36', 'E5F', 'E2F'], $key);
            $output .= '<'.$key.'>'.htmlspecialchars($value).'</'.$key.'>';
        }
        $output .= '</'.$this->module.'>';

        return $output;
    }

    /**
     * Returns the module mapping this entity
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }
}
