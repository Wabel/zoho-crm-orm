<?php
namespace Wabel\Zoho\CRM\Service;

use gossi\codegen\generator\CodeFileGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpProperty;
use Wabel\Zoho\CRM\ZohoClient;
use Wabel\Zoho\CRM\Exception\ZohoCRMException;

/**
 * This class is in charge of generating Zoho entities.
 */
class EntitiesGeneratorService {

    private $zohoClient;

    public function __construct(ZohoClient $zohoClient) {
        $this->zohoClient = $zohoClient;
    }

    /**
     * Generate ALL entities for all Zoho modules.
     *
     * @param string $targetDirectory
     * @param string $namespace
     */
    public function generateAll($targetDirectory, $namespace) {
        $modules = $this->zohoClient->getModules();
        foreach ($modules->getRecords() as $module) {
            try {
                $this->generateModule($module['key'], $module['pl'], $module['sl'], $targetDirectory, $namespace);
            } catch (ZohoCRMException $e) {
                error_log("Error thrown when retrieving fields for module ".$module['key'].". Error message: ".$e);
            }
        }
    }

    public function generateModule($moduleName, $modulePlural, $moduleSingular, $targetDirectory, $namespace) {
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

    public function generateBean($fields, $namespace, $className, $moduleName, $targetDirectory) {
        if (class_exists($namespace."\\".$className)) {
            $class = PhpClass::fromReflection(new \ReflectionClass($namespace."\\".$className));
        } else {
            $class = PhpClass::create();
        }

        $class->setName($className)
            ->setNamespace($namespace)
            ->setMethod(PhpMethod::create('__construct'));

        // Let's add the ZohoID property
        self::registerProperty($class, "zohoId, "The ID of this record in Zoho\nType: string\n", "string");

        foreach ($fields as $fieldCategory) {
            foreach ($fieldCategory as $name=>$field) {
                $req = $field['req'];
                $type = $field['type'];
                $isreadonly = $field['isreadonly'];
                $maxlength = $field['maxlength'];
                $label = $field['label'];
                $dv = $field['dv'];
                $customfield = $field['customfield'];

                $phpType = (($type=="DateTime" || $type=="Date")?"\\DateTime":"string");

                self::registerProperty($class, self::camelCase($name), "Zoho field ".$name."\n".
                    "Type: ".$type."\n".
                    "Read only: ".($isreadonly?"true":"false")."\n".
                    "Max length: ".$maxlength."\n".
                    "Custom field: ".($customfield?"true":"false")."\n", $phpType);
            }

        }

        $generator = new CodeFileGenerator();
        $code = $generator->generate($class);

        file_put_contents(rtrim($targetDirectory,'/').'/'.$className.".php", $code);
    }

    public function generateDao($fields, $namespace, $className, $daoClassName, $moduleName, $targetDirectory) {
        if (class_exists($namespace."\\".$daoClassName)) {
            $class = PhpClass::fromReflection(new \ReflectionClass($namespace."\\".$daoClassName));
        } else {
            $class = PhpClass::create();
        }

        $class->setName($daoClassName)
            ->setNamespace($namespace)
            ->setParentClassName('\\Wabel\\Zoho\\CRM\\AbstractZohoDao');


        $class->setMethod(PhpMethod::create('getModule')->setBody('return '.var_export($moduleName, true).';'));

        $class->setMethod(PhpMethod::create('getFields')->setBody('return '.var_export($fields, true).';'));

        $class->setMethod(PhpMethod::create('getBeanClassName')->setBody('return '.var_export($className, true).';'));

        $generator = new CodeFileGenerator();
        $code = $generator->generate($class);

        file_put_contents(rtrim($targetDirectory,'/').'/'.$daoClassName.".php", $code);
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
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);

        return $str;
    }

    private static function registerProperty(PhpClass $class, $name, $description, $type) {
        /*if (!$class->hasProperty($name)) {
            $property = PhpProperty::create($name);
            $property->setDescription($description);
            $property->setType($type);
            $property->setVisibility("protected");

            $class->setProperty($property);
        }*/

        $getterName = "get".ucfirst($name);
        $getterDescription = "Get ".lcfirst($description);
        $setterName = "set".ucfirst($name);
        $setterDescription = "Set ".lcfirst($description);

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
                             "return \$this;");
            $class->setMethod($method);
        }
    }
}
