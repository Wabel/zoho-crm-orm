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
     *
     * @return array Array containing each fully qualified dao class name
     */
    public function generateAll($targetDirectory, $namespace)
    {
        $modules = $this->zohoClient->getModules();
        $zohoModules = [];
        foreach ($modules->getRecords() as $module) {
            try {
                $zohoModules[] = $this->generateModule($module['key'], $module['pl'], $module['sl'], $targetDirectory, $namespace);
            } catch (ZohoCRMException $e) {
                $this->logger->notice('Error thrown when retrieving fields for module {module}. Error message: {error}.',
                    [
                        'module' => $module['key'],
                        'error' => $e->getMessage(),
                        'exception' => $e,
                    ]);
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
     * @return string The fully qualified Dao class name
     */
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

        $this->generateBean($fieldRecords, $namespace, $className, $moduleName, $targetDirectory, $moduleSingular);
        $this->generateDao($fieldRecords, $namespace, $className, $daoClassName, $moduleName, $targetDirectory, $moduleSingular, $modulePlural);

        return $namespace.'\\'.$daoClassName;
    }

    public function generateBean($fields, $namespace, $className, $moduleName, $targetDirectory, $moduleSingular)
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

        $usedIdentifiers = [];

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

                $identifier = $this->getUniqueIdentifier($name, $usedIdentifiers);
                $usedIdentifiers[$identifier] = true;

                self::registerProperty($class, $identifier, 'Zoho field '.$name."\n".
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
                    } elseif ($name === $moduleSingular.' Owner') {
                        // Check if this is a "owner" field.
                        $name = 'SMOWNERID';
                        $generateId = true;
                    } else {
                        $mapping = [
                            'Account Name' => 'ACCOUNTID',
                            'Contact Name' => 'CONTACTID',
                            'Parent Account' => 'PARENTACCOUNTID',
                            'Campaign Source' => 'CAMPAIGNID',
                        ];
                        if (isset($mapping[$name])) {
                            $name = $mapping[$name];
                            $generateId = true;
                        } else {
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
        $method->setType('bool');
        $class->setMethod($method);

        $generator = new CodeFileGenerator();
        $code = $generator->generate($class);

        if (!file_put_contents(rtrim($targetDirectory, '/').'/'.$className.'.php', $code)) {
            throw new ZohoCRMException("An error occurred while creating the class $className. Please verify the target directory or the rights of the file.");
        }
    }

    /**
     * Returns a unique identifier from the name.
     *
     * @param $name
     * @param array $usedNames
     */
    private function getUniqueIdentifier($name, array $usedIdentifiers)
    {
        $id = self::camelCase($name);
        if (isset($usedIdentifiers[$id])) {
            $counter = 2;
            while (isset($usedIdentifiers[$id.'_'.$counter])) {
                ++$counter;
            }

            return $id.'_'.$counter;
        } else {
            return $id;
        }
    }

    public function generateDao($fields, $namespace, $className, $daoClassName, $moduleName, $targetDirectory, $moduleSingular, $modulePlural)
    {
        //        if (class_exists($namespace."\\".$className)) {
//            $class = PhpClass::fromReflection(new \ReflectionClass($namespace."\\".$daoClassName));
//        } else {
            $class = PhpClass::create();
//        }

        $class->setName($daoClassName)
            ->setNamespace($namespace)
            ->setParentClassName('\\Wabel\\Zoho\\CRM\\AbstractZohoDao');

        $usedIdentifiers = [];

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
                $identifier = $this->getUniqueIdentifier($name, $usedIdentifiers);
                $usedIdentifiers[$identifier] = true;
                $fields[$key][$name]['getter'] = 'get'.ucfirst($identifier);
                $fields[$key][$name]['setter'] = 'set'.ucfirst($identifier);
                $fields[$key][$name]['name'] = $identifier;

                if ($type === 'Lookup') {
                    $generateId = false;

                    if ($field['customfield']) {
                        $name .= '_ID';
                        $generateId = true;
                    } elseif ($field['label'] === $moduleSingular.' Owner') {
                        // Check if this is a "owner" field.
                        $name = 'SMOWNERID';
                        $generateId = true;
                    } else {
                        $mapping = [
                            'Account Name' => 'ACCOUNTID',
                            'Contact Name' => 'CONTACTID',
                            'Parent Account' => 'PARENTACCOUNTID',
                            'Campaign Source' => 'CAMPAIGNID',
                        ];
                        if (isset($mapping[$field['label']])) {
                            $name = $mapping[$field['label']];
                            $generateId = true;
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

        $class->setMethod(PhpMethod::create('getSingularModuleName')->setBody('return '.var_export($moduleSingular, true).';'));

        $class->setMethod(PhpMethod::create('getPluralModuleName')->setBody('return '.var_export($modulePlural, true).';'));

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
        $str = self::unaccent($str);
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9'.implode('', $noStrip).']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(' ', '', $str);

        return $str;
    }

    /**
     * Unaccent the input string string. An example string like `ÀØėÿᾜὨζὅБю`
     * will be translated to `AOeyIOzoBY`
     *
     * @param string $str
     *
     * @return string unaccented string
     */
    private static function unaccent($str)
    {
        $transliteration = array(
            'Ĳ' => 'I', 'Ö' => 'O','Œ' => 'O','Ü' => 'U','ä' => 'a','æ' => 'a',
            'ĳ' => 'i','ö' => 'o','œ' => 'o','ü' => 'u','ß' => 's','ſ' => 's',
            'À' => 'A','Á' => 'A','Â' => 'A','Ã' => 'A','Ä' => 'A','Å' => 'A',
            'Æ' => 'A','Ā' => 'A','Ą' => 'A','Ă' => 'A','Ç' => 'C','Ć' => 'C',
            'Č' => 'C','Ĉ' => 'C','Ċ' => 'C','Ď' => 'D','Đ' => 'D','È' => 'E',
            'É' => 'E','Ê' => 'E','Ë' => 'E','Ē' => 'E','Ę' => 'E','Ě' => 'E',
            'Ĕ' => 'E','Ė' => 'E','Ĝ' => 'G','Ğ' => 'G','Ġ' => 'G','Ģ' => 'G',
            'Ĥ' => 'H','Ħ' => 'H','Ì' => 'I','Í' => 'I','Î' => 'I','Ï' => 'I',
            'Ī' => 'I','Ĩ' => 'I','Ĭ' => 'I','Į' => 'I','İ' => 'I','Ĵ' => 'J',
            'Ķ' => 'K','Ľ' => 'K','Ĺ' => 'K','Ļ' => 'K','Ŀ' => 'K','Ł' => 'L',
            'Ñ' => 'N','Ń' => 'N','Ň' => 'N','Ņ' => 'N','Ŋ' => 'N','Ò' => 'O',
            'Ó' => 'O','Ô' => 'O','Õ' => 'O','Ø' => 'O','Ō' => 'O','Ő' => 'O',
            'Ŏ' => 'O','Ŕ' => 'R','Ř' => 'R','Ŗ' => 'R','Ś' => 'S','Ş' => 'S',
            'Ŝ' => 'S','Ș' => 'S','Š' => 'S','Ť' => 'T','Ţ' => 'T','Ŧ' => 'T',
            'Ț' => 'T','Ù' => 'U','Ú' => 'U','Û' => 'U','Ū' => 'U','Ů' => 'U',
            'Ű' => 'U','Ŭ' => 'U','Ũ' => 'U','Ų' => 'U','Ŵ' => 'W','Ŷ' => 'Y',
            'Ÿ' => 'Y','Ý' => 'Y','Ź' => 'Z','Ż' => 'Z','Ž' => 'Z','à' => 'a',
            'á' => 'a','â' => 'a','ã' => 'a','ā' => 'a','ą' => 'a','ă' => 'a',
            'å' => 'a','ç' => 'c','ć' => 'c','č' => 'c','ĉ' => 'c','ċ' => 'c',
            'ď' => 'd','đ' => 'd','è' => 'e','é' => 'e','ê' => 'e','ë' => 'e',
            'ē' => 'e','ę' => 'e','ě' => 'e','ĕ' => 'e','ė' => 'e','ƒ' => 'f',
            'ĝ' => 'g','ğ' => 'g','ġ' => 'g','ģ' => 'g','ĥ' => 'h','ħ' => 'h',
            'ì' => 'i','í' => 'i','î' => 'i','ï' => 'i','ī' => 'i','ĩ' => 'i',
            'ĭ' => 'i','į' => 'i','ı' => 'i','ĵ' => 'j','ķ' => 'k','ĸ' => 'k',
            'ł' => 'l','ľ' => 'l','ĺ' => 'l','ļ' => 'l','ŀ' => 'l','ñ' => 'n',
            'ń' => 'n','ň' => 'n','ņ' => 'n','ŉ' => 'n','ŋ' => 'n','ò' => 'o',
            'ó' => 'o','ô' => 'o','õ' => 'o','ø' => 'o','ō' => 'o','ő' => 'o',
            'ŏ' => 'o','ŕ' => 'r','ř' => 'r','ŗ' => 'r','ś' => 's','š' => 's',
            'ť' => 't','ù' => 'u','ú' => 'u','û' => 'u','ū' => 'u','ů' => 'u',
            'ű' => 'u','ŭ' => 'u','ũ' => 'u','ų' => 'u','ŵ' => 'w','ÿ' => 'y',
            'ý' => 'y','ŷ' => 'y','ż' => 'z','ź' => 'z','ž' => 'z','Α' => 'A',
            'Ά' => 'A','Ἀ' => 'A','Ἁ' => 'A','Ἂ' => 'A','Ἃ' => 'A','Ἄ' => 'A',
            'Ἅ' => 'A','Ἆ' => 'A','Ἇ' => 'A','ᾈ' => 'A','ᾉ' => 'A','ᾊ' => 'A',
            'ᾋ' => 'A','ᾌ' => 'A','ᾍ' => 'A','ᾎ' => 'A','ᾏ' => 'A','Ᾰ' => 'A',
            'Ᾱ' => 'A','Ὰ' => 'A','ᾼ' => 'A','Β' => 'B','Γ' => 'G','Δ' => 'D',
            'Ε' => 'E','Έ' => 'E','Ἐ' => 'E','Ἑ' => 'E','Ἒ' => 'E','Ἓ' => 'E',
            'Ἔ' => 'E','Ἕ' => 'E','Ὲ' => 'E','Ζ' => 'Z','Η' => 'I','Ή' => 'I',
            'Ἠ' => 'I','Ἡ' => 'I','Ἢ' => 'I','Ἣ' => 'I','Ἤ' => 'I','Ἥ' => 'I',
            'Ἦ' => 'I','Ἧ' => 'I','ᾘ' => 'I','ᾙ' => 'I','ᾚ' => 'I','ᾛ' => 'I',
            'ᾜ' => 'I','ᾝ' => 'I','ᾞ' => 'I','ᾟ' => 'I','Ὴ' => 'I','ῌ' => 'I',
            'Θ' => 'T','Ι' => 'I','Ί' => 'I','Ϊ' => 'I','Ἰ' => 'I','Ἱ' => 'I',
            'Ἲ' => 'I','Ἳ' => 'I','Ἴ' => 'I','Ἵ' => 'I','Ἶ' => 'I','Ἷ' => 'I',
            'Ῐ' => 'I','Ῑ' => 'I','Ὶ' => 'I','Κ' => 'K','Λ' => 'L','Μ' => 'M',
            'Ν' => 'N','Ξ' => 'K','Ο' => 'O','Ό' => 'O','Ὀ' => 'O','Ὁ' => 'O',
            'Ὂ' => 'O','Ὃ' => 'O','Ὄ' => 'O','Ὅ' => 'O','Ὸ' => 'O','Π' => 'P',
            'Ρ' => 'R','Ῥ' => 'R','Σ' => 'S','Τ' => 'T','Υ' => 'Y','Ύ' => 'Y',
            'Ϋ' => 'Y','Ὑ' => 'Y','Ὓ' => 'Y','Ὕ' => 'Y','Ὗ' => 'Y','Ῠ' => 'Y',
            'Ῡ' => 'Y','Ὺ' => 'Y','Φ' => 'F','Χ' => 'X','Ψ' => 'P','Ω' => 'O',
            'Ώ' => 'O','Ὠ' => 'O','Ὡ' => 'O','Ὢ' => 'O','Ὣ' => 'O','Ὤ' => 'O',
            'Ὥ' => 'O','Ὦ' => 'O','Ὧ' => 'O','ᾨ' => 'O','ᾩ' => 'O','ᾪ' => 'O',
            'ᾫ' => 'O','ᾬ' => 'O','ᾭ' => 'O','ᾮ' => 'O','ᾯ' => 'O','Ὼ' => 'O',
            'ῼ' => 'O','α' => 'a','ά' => 'a','ἀ' => 'a','ἁ' => 'a','ἂ' => 'a',
            'ἃ' => 'a','ἄ' => 'a','ἅ' => 'a','ἆ' => 'a','ἇ' => 'a','ᾀ' => 'a',
            'ᾁ' => 'a','ᾂ' => 'a','ᾃ' => 'a','ᾄ' => 'a','ᾅ' => 'a','ᾆ' => 'a',
            'ᾇ' => 'a','ὰ' => 'a','ᾰ' => 'a','ᾱ' => 'a','ᾲ' => 'a','ᾳ' => 'a',
            'ᾴ' => 'a','ᾶ' => 'a','ᾷ' => 'a','β' => 'b','γ' => 'g','δ' => 'd',
            'ε' => 'e','έ' => 'e','ἐ' => 'e','ἑ' => 'e','ἒ' => 'e','ἓ' => 'e',
            'ἔ' => 'e','ἕ' => 'e','ὲ' => 'e','ζ' => 'z','η' => 'i','ή' => 'i',
            'ἠ' => 'i','ἡ' => 'i','ἢ' => 'i','ἣ' => 'i','ἤ' => 'i','ἥ' => 'i',
            'ἦ' => 'i','ἧ' => 'i','ᾐ' => 'i','ᾑ' => 'i','ᾒ' => 'i','ᾓ' => 'i',
            'ᾔ' => 'i','ᾕ' => 'i','ᾖ' => 'i','ᾗ' => 'i','ὴ' => 'i','ῂ' => 'i',
            'ῃ' => 'i','ῄ' => 'i','ῆ' => 'i','ῇ' => 'i','θ' => 't','ι' => 'i',
            'ί' => 'i','ϊ' => 'i','ΐ' => 'i','ἰ' => 'i','ἱ' => 'i','ἲ' => 'i',
            'ἳ' => 'i','ἴ' => 'i','ἵ' => 'i','ἶ' => 'i','ἷ' => 'i','ὶ' => 'i',
            'ῐ' => 'i','ῑ' => 'i','ῒ' => 'i','ῖ' => 'i','ῗ' => 'i','κ' => 'k',
            'λ' => 'l','μ' => 'm','ν' => 'n','ξ' => 'k','ο' => 'o','ό' => 'o',
            'ὀ' => 'o','ὁ' => 'o','ὂ' => 'o','ὃ' => 'o','ὄ' => 'o','ὅ' => 'o',
            'ὸ' => 'o','π' => 'p','ρ' => 'r','ῤ' => 'r','ῥ' => 'r','σ' => 's',
            'ς' => 's','τ' => 't','υ' => 'y','ύ' => 'y','ϋ' => 'y','ΰ' => 'y',
            'ὐ' => 'y','ὑ' => 'y','ὒ' => 'y','ὓ' => 'y','ὔ' => 'y','ὕ' => 'y',
            'ὖ' => 'y','ὗ' => 'y','ὺ' => 'y','ῠ' => 'y','ῡ' => 'y','ῢ' => 'y',
            'ῦ' => 'y','ῧ' => 'y','φ' => 'f','χ' => 'x','ψ' => 'p','ω' => 'o',
            'ώ' => 'o','ὠ' => 'o','ὡ' => 'o','ὢ' => 'o','ὣ' => 'o','ὤ' => 'o',
            'ὥ' => 'o','ὦ' => 'o','ὧ' => 'o','ᾠ' => 'o','ᾡ' => 'o','ᾢ' => 'o',
            'ᾣ' => 'o','ᾤ' => 'o','ᾥ' => 'o','ᾦ' => 'o','ᾧ' => 'o','ὼ' => 'o',
            'ῲ' => 'o','ῳ' => 'o','ῴ' => 'o','ῶ' => 'o','ῷ' => 'o','А' => 'A',
            'Б' => 'B','В' => 'V','Г' => 'G','Д' => 'D','Е' => 'E','Ё' => 'E',
            'Ж' => 'Z','З' => 'Z','И' => 'I','Й' => 'I','К' => 'K','Л' => 'L',
            'М' => 'M','Н' => 'N','О' => 'O','П' => 'P','Р' => 'R','С' => 'S',
            'Т' => 'T','У' => 'U','Ф' => 'F','Х' => 'K','Ц' => 'T','Ч' => 'C',
            'Ш' => 'S','Щ' => 'S','Ы' => 'Y','Э' => 'E','Ю' => 'Y','Я' => 'Y',
            'а' => 'A','б' => 'B','в' => 'V','г' => 'G','д' => 'D','е' => 'E',
            'ё' => 'E','ж' => 'Z','з' => 'Z','и' => 'I','й' => 'I','к' => 'K',
            'л' => 'L','м' => 'M','н' => 'N','о' => 'O','п' => 'P','р' => 'R',
            'с' => 'S','т' => 'T','у' => 'U','ф' => 'F','х' => 'K','ц' => 'T',
            'ч' => 'C','ш' => 'S','щ' => 'S','ы' => 'Y','э' => 'E','ю' => 'Y',
            'я' => 'Y','ð' => 'd','Ð' => 'D','þ' => 't','Þ' => 'T','ა' => 'a',
            'ბ' => 'b','გ' => 'g','დ' => 'd','ე' => 'e','ვ' => 'v','ზ' => 'z',
            'თ' => 't','ი' => 'i','კ' => 'k','ლ' => 'l','მ' => 'm','ნ' => 'n',
            'ო' => 'o','პ' => 'p','ჟ' => 'z','რ' => 'r','ს' => 's','ტ' => 't',
            'უ' => 'u','ფ' => 'p','ქ' => 'k','ღ' => 'g','ყ' => 'q','შ' => 's',
            'ჩ' => 'c','ც' => 't','ძ' => 'd','წ' => 't','ჭ' => 'c','ხ' => 'k',
            'ჯ' => 'j','ჰ' => 'h'
        );

        return str_replace(
            array_keys($transliteration),
            array_values($transliteration),
            $str
        );
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

        $isDirtyName = 'dirty'.ucfirst($name);
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
                             '$this->dirty'.ucfirst($name)." = true;\n".
                             'return $this;');
            $class->setMethod($method);
        }
    }
}
