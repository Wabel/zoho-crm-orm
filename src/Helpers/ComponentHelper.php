<?php

namespace Wabel\Zoho\CRM\Helpers;


use Wabel\Zoho\CRM\BeanComponents\Field;

class ComponentHelper
{

    /**
     * @param array $fieldConfigurations
     * @return Field
     */
    public static function createFieldFromArray(array $fieldConfigurations){
        $field = new Field();
        foreach ($fieldConfigurations as $keyFieldConfiguration => $valueFieldConfiguration){
            $method = 'set'.ucfirst($keyFieldConfiguration);
            $field->{$method}($valueFieldConfiguration);
        }
        return $field;
    }
}