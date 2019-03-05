<?php

namespace Wabel\Zoho\CRM\Service;

use gossi\codegen\generator\CodeFileGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpProperty;
use Psr\Log\LoggerInterface;
use Wabel\Zoho\CRM\Exceptions\ZohoCRMORMException;
use Wabel\Zoho\CRM\ZohoClient;

/**
 * This class is in charge of generating Zoho entities.
 */
class EntitiesGeneratorService
{
    private $zohoClient;
    private $logger;

    public static $defaultZohoFields = ['Created_Time','Modified_Time', 'Last_Activity_Time',
        'Created_By', 'Modified_By', 'Owner'];

    public static $defaultORMFields = ['createdTime','modifiedTime', 'lastActivityTime',
        'createdByID', 'modifiedByID', 'createdByName', 'modifiedByName', 'OwnerID', 'OwnerName',
        'ZCRMRecord'
    ];

    public static $defaultORMSystemFields = ['createdTime','modifiedTime', 'lastActivityTime',
        'ZCRMRecord', 'zohoId'
    ];

    public static $defaultDateFields = ['createdTime','modifiedTime', 'lastActivityTime'];

    public function __construct(ZohoClient $zohoClient, LoggerInterface $logger)
    {
        $this->zohoClient = $zohoClient;
        $this->logger = $logger;
    }

    /**
     * Generate ALL entities for all Zoho modules.
     *
     * @param string $targetDirectory
     * @param string $namespace
     *
     * @return array Array containing each fully qualified dao class name
     */
    public function generateAll($targetDirectory, $namespace)
    {
        /**
         * @var $modules \ZCRMModule[]
         */
        $modules = $this->zohoClient->getModules();
        $zohoModules = [];
        foreach ($modules as $module) {
            if($module->isApiSupported()){
                try {
                    $module = $this->generateModule($module->getAPIName(), $module->getPluralLabel(),
                        $module->getSingularLabel(), $targetDirectory, $namespace);
                    if($module){
                        $zohoModules[] = $module;
                    }
                } catch (ZohoCRMORMException $e) {
                    $this->logger->notice('Error thrown when retrieving fields for module {module}. Error message: {error}.',
                        [
                            'module' => $module->getAPIName(),
                            'error' => $e->getMessage(),
                            'exception' => $e,
                        ]);
                }
            }
        }

        return $zohoModules;
    }

    /**
     * Generate a dao for a zoho module.
     *
     * @param string $moduleName
     * @param string $modulePlural
     * @param string $moduleSingular
     * @param string $targetDirectory
     * @param string $namespace
     *
     * @return string|null The fully qualified Dao class name
     */
    public function generateModule($moduleName, $modulePlural, $moduleSingular, $targetDirectory, $namespace)
    {
        $fieldRecords = $this->zohoClient->getFields($moduleName);

        if(!$fieldRecords){
            return null;
        }
        if (!file_exists($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $namespace = trim($namespace, '\\');
        $className = self::upperCamelCase($moduleSingular);
        $daoClassName = $className.'ZohoDao';


        // Case is a reserved keyword. Let's rename it.
        if ($className === 'Case') {
            $className = 'ZohoCase';
        }

        $this->generateBean($fieldRecords, $namespace, $className, $moduleName, $targetDirectory, $moduleSingular);
        $this->generateDao($fieldRecords, $namespace, $className, $daoClassName, $moduleName, $targetDirectory, $moduleSingular, $modulePlural);

        return $namespace.'\\'.$daoClassName;
    }

    /**
     * @param \ZCRMField[] $ZCRMfields
     * @param string $namespace
     * @param string $className
     * @param string $moduleName
     * @param string $targetDirectory
     * @param string $moduleSingular
     * @throws ZohoCRMORMException
     */
    public function generateBean(array $ZCRMfields, $namespace, $className, $moduleName, $targetDirectory, $moduleSingular)
    {

        $class = PhpClass::create();
        $class->setName($className)
            ->setNamespace($namespace)
            ->addInterface('\\Wabel\\Zoho\\CRM\\ZohoBeanInterface')
            ->setMethod(PhpMethod::create('__construct'));

        // Let's add the ZohoID property
        self::registerProperty($class, 'zohoId', "The ID of this record in Zoho\nType: string\n", 'string');

        foreach ($ZCRMfields as $ZCRMfield) {
            $name = self::camelCase($ZCRMfield->getApiName());
            $apiName = $ZCRMfield->getApiName();
            $type = $ZCRMfield->getDataType();
            $isreadonly = $ZCRMfield->isReadOnly();
            $maxlength = $ZCRMfield->getLength();
            $label = $ZCRMfield->getFieldLabel();
            $customfield = $ZCRMfield->isCustomField();
            $nullable = false;
            switch ($type) {
                case 'datetime':
                case 'date':
                    $phpType = '\\DateTimeInterface';
                    $nullable = true;
                    break;
                case 'boolean':
                    $phpType = 'bool';
                    break;
                case 'bigint':
                case 'integer':
                    $phpType = 'int';
                    break;
                case 'autonumber':
                case 'bigint':
                case 'integer':
                    $phpType = 'int';
                    break;
                case 'currency':
                case 'decimal':
                case 'double':
                case 'percent':
                    $phpType = 'float';
                    break;
                case 'multiselectpicklist':
                    $phpType = 'string[]';
                    $nullable = true;
                    break;
                case 'picklist':
                    $phpType = 'string';
                    $nullable = true;
                    break;
                case 'ownerlookup':
                    $name = self::camelCase($name.'_OwnerID');
                    $phpType = 'string';
                    break;
                case 'lookup':
                    $name = self::camelCase($name.'_ID');
                    $phpType = 'string';
                    break;
                case 'multiselectlookup':
                    continue 2;
                    break;
                case 'userlookup':
                    $name = self::camelCase($name.'_UserID');
                    $phpType = 'string';
                    $nullable = true;
                    break;
                case 'multiuserlookup':
                    //@Todo: It's a hypothetical field name based on zoho fields architecture
                    $name = self::camelCase($name.'_UserIDs');
                    $phpType = 'string[]';
                    $nullable = true;
                    break;
                case 'fileupload':
                    $phpType = 'text';
                    break;
                case 'consent_lookup':
                case 'profileimage':
                case 'ALARM':
                case 'RRULE':
                case 'event_reminder':
                    //@Todo: We have to see how we can work with it
                    continue 2;
                    break;
                default:
                    $phpType = 'string';
                    break;
            }
            if(in_array($name, self::$defaultDateFields)){
                //Zoho provides these fields by ZCRMRecord::getFieldValue() but also by method in ZCRMRecord
                $phpType = '\\DateTimeImmutable';
                $nullable = true;
            }

            self::registerProperty($class, $name, 'Zoho field '.$label."\n".
                'Field API Name: '.$apiName."\n".
                'Type: '.$type."\n".
                'Read only: '.($isreadonly ? 'true' : 'false')."\n".
                'Max length: '.$maxlength."\n".
                'Custom field: '.($customfield ? 'true' : 'false')."\n", $phpType, $nullable);
        }

        /**
         * If Zoho provides them we don't have to create them again
         */
        self::registerProperty($class, 'createdTime', "The time the record was created in Zoho\nType: DateTimeImmutable\n", '\\DateTimeImmutable', true);
        self::registerProperty($class, 'modifiedTime', "The last time the record was modified in Zoho\nType: DateTimeImmutable\n", '\\DateTimeImmutable', true);
        self::registerProperty($class, 'lastActivityTime', "The last activity time the record or a related record was modified in Zoho\nType: DateTimeImmutable\n", '\\DateTimeImmutable', true);
        self::registerProperty($class, 'createdByOwnerID', "The user id who created the entity in Zoho\nType: string\n", 'string');
        self::registerProperty($class, 'modifiedByOwnerID', "The user id who modified the entity in Zoho\nType: string\n", 'string');
        self::registerProperty($class, 'createdByOwnerName', "The user id who created the entity in Zoho\nType: string\n", 'string');
        self::registerProperty($class, 'modifiedByOwnerName', "The user id who modified the entity in Zoho\nType: string\n", 'string');
        self::registerProperty($class, 'ownerOwnerID', "Owner ID in Zoho: string\n", 'string');
        self::registerProperty($class, 'ownerOwnerName', "Owner Name in Zoho: string\n", 'string');
        self::registerProperty($class, 'ZCRMRecord', "The Wrapped Zoho CRM Record\nType: ZCRMRecord\n", '\\ZCRMRecord');
        $methodIsDirty = PhpMethod::create('isDirty');
        $methodIsDirty->setDescription('Returns whether a property is changed or not.');
        $methodIsDirty->addParameter(PhpParameter::create('name'));
        $methodIsDirty->setBody("\$propertyName = 'dirty'.ucfirst(\$name);\nreturn \$this->\$propertyName;");
        $methodIsDirty->setType('bool');
        $class->setMethod($methodIsDirty);
        $methodSetDirty = PhpMethod::create('setDirty');
        $methodSetDirty->setDescription('Returns whether a property is changed or not.');
        $fieldNameParameter = PhpParameter::create('name');
        $fieldNameParameter->setType('string');
        $methodSetDirty->addParameter($fieldNameParameter);
        $fieldStatusParameter = PhpParameter::create('status');
        $fieldStatusParameter->setType('bool');
        $methodSetDirty->addParameter($fieldStatusParameter);
        $methodSetDirty->setBody("\$propertyName = 'dirty'.ucfirst(\$name);\n\$this->\$propertyName = \$status;");
        $methodSetDirty->setType('bool');
        $class->setMethod($methodSetDirty);

        $generator = new CodeFileGenerator();
        $code = $generator->generate($class);

        if (!file_put_contents(rtrim($targetDirectory, '/').'/'.$className.'.php', $code)) {
            throw new ZohoCRMORMException("An error occurred while creating the class $className. Please verify the target directory or the rights of the file.");
        }
    }

    /**
     * @param \ZCRMPickListValue[] $pickListFieldValues
     * @return array
     */
    public static function ZCRMPickListValueListToArray(array $pickListFieldValues){
        return array_map(function (\ZCRMPickListValue $pickListValue){
            return [
                'displayValue' => $pickListValue->getDisplayValue(),
                'sequenceNumber' => $pickListValue->getSequenceNumber(),
                'actualValue' => $pickListValue->getActualValue(),
                'maps' => $pickListValue->getMaps(),
            ];
        },$pickListFieldValues);
    }
    /**
     * @param \ZCRMField[] $ZCRMfields
     * @param string $namespace
     * @param string $className
     * @param string $daoClassName
     * @param string $moduleName
     * @param string $targetDirectory
     * @param string $moduleSingular
     * @param string $modulePlural
     * @throws ZohoCRMORMException
     */
    public function generateDao(array $ZCRMfields, $namespace, $className, $daoClassName, $moduleName, $targetDirectory, $moduleSingular, $modulePlural)
    {
        $class = PhpClass::create();

        $class->setName($daoClassName)
            ->setNamespace($namespace)
            ->setParentClassName('\\Wabel\\Zoho\\CRM\\AbstractZohoDao');

        $fields = [];
        foreach ($ZCRMfields as $ZCRMfield) {
            $name = $ZCRMfield->getApiName();
            $apiName = $ZCRMfield->getApiName();
            $type = $ZCRMfield->getDataType();
            $system =false;
            $lookupModuleName = null;
            if(in_array($ZCRMfield->getApiName(), self::$defaultZohoFields)){
                $system = true;
            }

            switch ($type) {
                case 'datetime':
                case 'date':
                    $phpType = '\\DateTime';
                    break;
                case 'boolean':
                    $phpType = 'bool';
                    break;
                case 'bigint':
                case 'integer':
                    $phpType = 'int';
                    break;
                case 'autonumber':
                case 'bigint':
                case 'integer':
                    $phpType = 'int';
                    break;
                case 'currency':
                case 'decimal':
                case 'double':
                case 'percent':
                    $phpType = 'float';
                    break;
                case 'multiselectpicklist':
                    $fields[$name]['values']  = self::ZCRMPickListValueListToArray($ZCRMfield->getPickListFieldValues());
                    $phpType = 'string[]';
                    break;
                case 'picklist':
                    $fields[$name]['values']  = self::ZCRMPickListValueListToArray($ZCRMfield->getPickListFieldValues());
                    $phpType = 'string';
                    break;
                case 'ownerlookup':
                    $name = self::camelCase($name.'_OwnerID');
                    $phpType = 'string';
                    break;
                case 'lookup':
                    $name = self::camelCase($name.'_ID');
                    $phpType = 'string';
                    $lookupModuleName = $ZCRMfield->getLookupField() ? $ZCRMfield->getLookupField()->getModule():null;
                    break;
                case 'multiselectlookup':
                    continue 2;
                    break;
                case 'userlookup':
                    $name = self::camelCase($name.'_UserID');
                    $phpType = 'string';
                    break;
                case 'multiuserlookup':
                    //@Todo: It's a hypothetical field name based on zoho fields architecture
                    continue 2;
                    break;
                case 'fileupload':
                case 'consent_lookup':
                case 'profileimage':
                case 'ALARM':
                case 'RRULE':
                case 'event_reminder':
                    //@Todo: We have to see how we can work with it
                    continue 2;
                    break;
                default:
                    $phpType = 'string';
                    break;
            }

            $fields[$name]['phpType'] = $phpType;
            $fields[$name]['getter'] = 'get'.ucfirst(self::camelCase($name));
            $fields[$name]['setter'] = 'set'.ucfirst(self::camelCase($name));
            $fields[$name]['name'] = self::camelCase($name);
            $fields[$name]['apiName'] = $apiName;
            $fields[$name]['customfield'] = $ZCRMfield->isCustomField();
            $fields[$name]['req'] = $ZCRMfield->isMandatory();
            $fields[$name]['type'] = $ZCRMfield->getDataType();
            $fields[$name]['isreadonly'] = $ZCRMfield->isReadOnly();
            $fields[$name]['maxlength']  = $ZCRMfield->getLength();
            $fields[$name]['label']  = $ZCRMfield->getFieldLabel();
            $fields[$name]['dv']  = $ZCRMfield->getDefaultValue();
            $fields[$name]['system'] = $system;
            $fields[$name]['lookupModuleName'] = $lookupModuleName;
        }

        $class->setMethod(PhpMethod::create('getModule')->setBody('return '.var_export($moduleName, true).';'));

        $class->setMethod(PhpMethod::create('getSingularModuleName')->setBody('return '.var_export($moduleSingular, true).';'));

        $class->setMethod(PhpMethod::create('getPluralModuleName')->setBody('return '.var_export($modulePlural, true).';'));

        $class->setMethod(PhpMethod::create('getFieldsDetails')->setBody('return '.var_export($fields, true).';'));

        $class->setMethod(PhpMethod::create('getBeanClassName')->setBody('return '.var_export($namespace.'\\'.$className, true).';'));

        $generator = new CodeFileGenerator();
        $code = $generator->generate($class);

        if (!file_put_contents(rtrim($targetDirectory, '/').'/'.$daoClassName.'.php', $code)) {
            throw new ZohoCRMORMException("An error occurred while creating the DAO $daoClassName. Please verify the target directory exists or the rights of the file.");
        }
    }

    private static function camelCase($str, array $noStrip = [])
    {
        $str = self::upperCamelCase($str, $noStrip);
        $str = lcfirst($str);

        return $str;
    }

    private static function upperCamelCase($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9'.implode('', $noStrip).']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(' ', '', $str);

        return $str;
    }

    private static function registerProperty(PhpClass $class, $name, $description, $type, $nullable = false)
    {
        if (!$class->hasProperty($name)) {
            $property = PhpProperty::create($name);
            $property->setDescription($description);
            $property->setType($type);
            $property->setVisibility('protected');

            $class->setProperty($property);
        }
        

        $isDirtyName = 'dirty'.ucfirst($name);
        if (!$class->hasProperty($isDirtyName) && !in_array($name,self::$defaultORMSystemFields)) {
            $dirtyProperty = PhpProperty::create($isDirtyName);
            $dirtyProperty->setDescription("Whether '$name' has been changed or not.");
            $dirtyProperty->setType('bool');
            $dirtyProperty->setVisibility('protected');
            $dirtyProperty->setDefaultValue(false);

            $class->setProperty($dirtyProperty);
        }

        $getterName = 'get'.ucfirst($name);
        $getterDescription = 'Get '.lcfirst($description);
        $setterName = 'set'.ucfirst($name);
        $setterDescription = 'Set '.lcfirst($description);

        if (!$class->hasMethod($getterName)) {
            $method = PhpMethod::create($getterName);
            $method->setDescription($getterDescription);
            $method->setBody("return \$this->{$name};");
            $class->setMethod($method);
        }

        if (!$class->hasMethod($setterName)) {
            $method = PhpMethod::create($setterName);
            $method->setDescription($setterDescription);
            $returnType = $type;
            if(strpos($returnType,'[]') !== false){
                $returnType = 'array';
            }
            $parameter = PhpParameter::create($name)->setType($returnType);
            if($returnType === 'array'){
                $parameter->setDescription('An array like '.$type);
            }
            if ($nullable) {
                $parameter->setValue(null);
            }
            $method->addParameter($parameter);
            $method->setBody("\$this->{$name} = \${$name};\n".
                (!in_array($name,self::$defaultORMSystemFields)?'$this->dirty'.ucfirst($name)." = true;\n":"").
                             'return $this;');
            $class->setMethod($method);
        }
    }
}
