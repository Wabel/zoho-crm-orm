<?php

namespace Wabel\Zoho\CRM\Service;

use gossi\codegen\generator\CodeFileGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpProperty;
use Psr\Log\LoggerInterface;
use Wabel\Zoho\CRM\ZohoClient;
use Wabel\Zoho\CRM\Exception\ZohoCRMException;

/**
 * This class is in charge of generating Zoho entities.
 */
class EntitiesGeneratorService
{
    private $zohoClient;
    private $logger;

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
     */
    public function generateAll($targetDirectory, $namespace)
    {
        $modules = $this->zohoClient->getModules();
        foreach ($modules->getRecords() as $module) {
            try {
                $this->generateModule($module['key'], $module['pl'], $module['sl'], $targetDirectory, $namespace);
            } catch (ZohoCRMException $e) {
                $this->logger->notice('Error thrown when retrieving fields for module {module}. Error message: {error}.',
                    [
                        'module' => $module['key'],
                        'error' => $e->getMessage(),
                        'exception' => $e,
                    ]);
            }
        }
    }

    public function generateModule($moduleName, $modulePlural, $moduleSingular, $targetDirectory, $namespace)
    {
        $fields = $this->zohoClient->getFields($moduleName);

        if (!file_exists($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $namespace = trim($namespace, '\\');
        $className = self::upperCamelCase($moduleSingular);
        $daoClassName = $className.'ZohoDao';

        $fieldRecords = $fields->getRecords();

        $this->generateBean($fieldRecords, $namespace, $className, $moduleName, $targetDirectory);
        $this->generateDao($fieldRecords, $namespace, $className, $daoClassName, $moduleName, $targetDirectory);
    }

    public function generateBean($fields, $namespace, $className, $moduleName, $targetDirectory)
    {

//        if (class_exists($namespace."\\".$className)) {
//            $class = PhpClass::fromReflection(new \ReflectionClass($namespace."\\".$className));
//        } else {
            $class = PhpClass::create();
//        }

        $class->setName($className)
            ->setNamespace($namespace)
            ->addInterface('\\Wabel\\Zoho\\CRM\\ZohoBeanInterface')
            ->setMethod(PhpMethod::create('__construct'));

        // Let's add the ZohoID property
        self::registerProperty($class, 'zohoId', "The ID of this record in Zoho\nType: string\n", 'string');

        foreach ($fields as &$fieldCategory) {
            foreach ($fieldCategory as $name => &$field) {
                $req = $field['req'];
                $type = $field['type'];
                $isreadonly = $field['isreadonly'];
                $maxlength = $field['maxlength'];
                $label = $field['label'];
                $dv = $field['dv'];
                $customfield = $field['customfield'];

                switch ($type) {
                    case 'DateTime':
                    case 'Date':
                        $phpType = '\\DateTime';
                        break;
                    case 'Boolean':
                        $phpType = 'bool';
                        break;
                    case 'Integer':
                        $phpType = 'int';
                        break;
                    default:
                        $phpType = 'string';
                        break;
                }

                $field['phpType'] = $phpType;

                self::registerProperty($class, self::camelCase($name), 'Zoho field '.$name."\n".
                    'Type: '.$type."\n".
                    'Read only: '.($isreadonly ? 'true' : 'false')."\n".
                    'Max length: '.$maxlength."\n".
                    'Custom field: '.($customfield ? 'true' : 'false')."\n", $phpType);

                // Adds a ID field for lookups
                if ($type === 'Lookup') {
                    $generateId = false;

                    if ($customfield) {
                        $name .= '_ID';
                        $generateId = true;
                    } else {
                        switch ($name) {
                            //TODO : To be completed with known lookup fields that are not custom fields but default in Zoho
                            case 'Account Name' :
                                $name = 'ACCOUNTID';
                                $generateId = true;
                                break;
                            case 'Contact Name' :
                                $name = 'CONTACTID';
                                $generateId = true;
                                break;
                            default :
                                $this->logger->warning('Unable to set a ID for the field {name} of the {module} module', [
                                    'name' => $name,
                                    'module' => $moduleName,
                                ]);
                        }
                    }

                    if ($generateId) {
                        $req = false;
                        $type = 'Lookup ID';
                        $isreadonly = true;
                        $maxlength = $field['maxlength'];
                        $label = $field['label'];
                        $dv = $field['dv'];

                        $field['phpType'] = $phpType;

                        self::registerProperty($class, ($customfield ? self::camelCase($name) : $name), 'Zoho field '.$name."\n".
                            'Type: '.$type."\n".
                            'Read only: '.($isreadonly ? 'true' : 'false')."\n".
                            'Max length: '.$maxlength."\n".
                            'Custom field: '.($customfield ? 'true' : 'false')."\n", 'string');
                    }
                }
            }
        }

        self::registerProperty($class, 'createdTime', "The time the record was created in Zoho\nType: DateTime\n", '\\DateTime');
        self::registerProperty($class, 'modifiedTime', "The last time the record was modified in Zoho\nType: DateTime\n", '\\DateTime');

        $method = PhpMethod::create('isDirty');
        $method->setDescription('Returns whether a property is changed or not.');
        $method->addParameter(PhpParameter::create('name'));
        $method->setBody("\$propertyName = 'dirty'.ucfirst(\$name);\nreturn \$this->\$propertyName;");
        $method->setType("bool");
        $class->setMethod($method);

        $generator = new CodeFileGenerator();
        $code = $generator->generate($class);

        if (!file_put_contents(rtrim($targetDirectory, '/').'/'.$className.'.php', $code)) {
            throw new ZohoCRMException("An error occurred while creating the class $className. Please verify the target directory or the rights of the file.");
        }
    }

    public function generateDao($fields, $namespace, $className, $daoClassName, $moduleName, $targetDirectory)
    {
        //        if (class_exists($namespace."\\".$className)) {
//            $class = PhpClass::fromReflection(new \ReflectionClass($namespace."\\".$daoClassName));
//        } else {
            $class = PhpClass::create();
//        }

        $class->setName($daoClassName)
            ->setNamespace($namespace)
            ->setParentClassName('\\Wabel\\Zoho\\CRM\\AbstractZohoDao');

        foreach ($fields as $key => $fieldCategory) {
            foreach ($fieldCategory as $name => $field) {
                $type = $field['type'];

                switch ($type) {
                    case 'DateTime':
                    case 'Date':
                        $phpType = '\\DateTime';
                        break;
                    case 'Boolean':
                        $phpType = 'bool';
                        break;
                    case 'Integer':
                        $phpType = 'int';
                        break;
                    default:
                        $phpType = 'string';
                        break;
                }

                $fields[$key][$name]['phpType'] = $phpType;
                $fields[$key][$name]['getter'] = 'get'.ucfirst(self::camelCase($name));
                $fields[$key][$name]['setter'] = 'set'.ucfirst(self::camelCase($name));
                $fields[$key][$name]['name'] = self::camelCase($name);

                if ($type === 'Lookup') {
                    $generateId = false;

                    if ($field['customfield']) {
                        $name .= '_ID';
                        $generateId = true;
                    } else {
                        switch ($field['label']) {
                            //TODO : To be completed with known lookup fields that are not custom fields but default in Zoho
                            case 'Account Name' :
                                $name = 'ACCOUNTID';
                                $generateId = true;
                                break;
                            case 'Contact Name' :
                                $name = 'CONTACTID';
                                $generateId = true;
                                break;
                        }
                    }
                    if ($generateId) {
                        $fields[$key][$name]['req'] = false;
                        $fields[$key][$name]['type'] = 'Lookup ID';
                        $fields[$key][$name]['isreadonly'] = true;
                        $fields[$key][$name]['maxlength'] = 100;
                        $fields[$key][$name]['label'] = $name;
                        $fields[$key][$name]['dv'] = $name;
                        $fields[$key][$name]['customfield'] = true;
                        $fields[$key][$name]['phpType'] = $phpType;
                        $fields[$key][$name]['getter'] = 'get'.ucfirst(self::camelCase($name));
                        $fields[$key][$name]['setter'] = 'set'.ucfirst(self::camelCase($name));
                        $fields[$key][$name]['name'] = self::camelCase($name);
                    }
                }
            }
        }

        $class->setMethod(PhpMethod::create('getModule')->setBody('return '.var_export($moduleName, true).';'));

        $class->setMethod(PhpMethod::create('getFields')->setBody('return '.var_export($fields, true).';'));

        $class->setMethod(PhpMethod::create('getBeanClassName')->setBody('return '.var_export($namespace.'\\'.$className, true).';'));

        $generator = new CodeFileGenerator();
        $code = $generator->generate($class);

        if (!file_put_contents(rtrim($targetDirectory, '/').'/'.$daoClassName.'.php', $code)) {
            throw new ZohoCRMException("An error occurred while creating the DAO $daoClassName. Please verify the target directory exists or the rights of the file.");
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

    private static function registerProperty(PhpClass $class, $name, $description, $type)
    {
        if (!$class->hasProperty($name)) {
            $property = PhpProperty::create($name);
            $property->setDescription($description);
            $property->setType($type);
            $property->setVisibility('protected');

            $class->setProperty($property);
        }

        $isDirtyName = "dirty".ucfirst($name);
        if (!$class->hasProperty($isDirtyName)) {
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
            $method->addParameter(PhpParameter::create($name)->setType($type));
            $method->setBody("\$this->{$name} = \${$name};\n".
                             "\$this->dirty".ucfirst($name)." = true;\n".
                             "return \$this;");
            $class->setMethod($method);
        }
    }
}
