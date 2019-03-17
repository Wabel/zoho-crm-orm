[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Wabel/zoho-crm-orm/badges/quality-score.png?b=1.2)](https://scrutinizer-ci.com/g/Wabel/zoho-crm-orm/?branch=1.2)
[![Build Status](https://travis-ci.org/Wabel/zoho-crm-orm.svg?branch=1.2)](https://travis-ci.org/Wabel/zoho-crm-orm)
[![Coverage Status](https://coveralls.io/repos/Wabel/zoho-crm-orm/badge.svg?branch=1.2)](https://coveralls.io/r/Wabel/zoho-crm-orm?branch=1.2)

Wabel's Zoho-CRM ORM
====================

Heavily forked from [mctekk's work](https://github.com/mctekk/zohocrm)

What is this?
-------------

This project is a PHP connector to Zoho CRM. Use this connector to access ZohoCRM data from your PHP application.

Who is it different from other connectors?
------------------------------------------

Unlike other Zoho CRM clients, the Zoho-CRM ORM uses a **code generator** to generate Beans and DAOs to easily access
Zoho objects from PHP.

Beans and DAOs?
---------------

A small bit of code is better than a long phrase, here you go with a sample:

```php
use \Wabel\Zoho\CRM\ZohoClient;

// The ZohoClient class is the low level class used to access Zoho.
$zohoClient = new ZohoClient($configuration, 'Europe/Paris');

// Use the "DAO" class to write to some module of Zoho.
// Each module (even custom ones) has its own class.
$contactZohoDao = new ContactZohoDao($zohoClient);

// For each DAO, there is a bean associated.
$contact = new Contact();
$contact->setLastName("Doe");
$contact->setFirstName("John");

// Use the "save" method to save the bean.
$contactDao->save($contact);

// Use the "searchRecords" method to fetch data from Zoho. 
$records = $contactDao->searchRecords("(Last Name:FooBar)");
foreach ($records as $record) {
    // Each record is a "Contact" object.
    echo $record->getLastName();
}

// Get Records from the dao
$contactDao->getRecords()
```

What you must always remember:

- **Beans** are used to map records in Zoho. There is one class per Zoho module
- **DAOs** are used to send beans to Zoho. There is one DAO per Zoho module

But how do I generate Beans and DAOs?
-------------------------------------

There are several techniques.

Using pure PHP code:

```php
use \Wabel\Zoho\CRM\ZohoClient;

// The ZohoClient class is the low level class used to access Zoho.
$zohoClient = new ZohoClient($zohoAuthToken);

// The EntitiesGeneratorService class is in charge of generating Beans and DAOs.
$entitiesGenerator = new EntitiesGeneratorService($client);

// The target directory we will write into.
$directory = __DIR__.'/src/TestNamespace/';
// The namespace for the beans and DAOs.
$namespace = 'TestNamespace';
// That returns an array containing each created Dao by using the fully qualified class name
$generator->generateAll($directory, $namespace);
```

Targetting the correct Zoho API
-------------------------------

Out of the box, the client will point to the `https://crm.zoho.com/crm/private` endpoint.
If your endpoint is different (some users are pointing to `https://crm.zoho.eu/crm/private`), you can
use the third parameter of the `Client` constructor:


```php
$zohoClient = new ZohoClient([
    'client_id' => 'xxxxxxxxxxxxxxxxxxxxxx',
     'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'redirect_uri' => 'http://xxxxxxxxx.com/bakcxxxx',
    'currentUserEmail' => 'xxxxx@test.fr',
    'applicationLogFilePath' => '/xxx/xxx/',
    'sandbox' => true or false,
    'apiBaseUrl' => '',
    'apiVersion' => '',
    'access_type' => '',
    'accounts_url' => '',
    'persistence_handler_class' => '',
    'token_persistence_path' => ''
], 'Europe/Paris);
```  


Setting up unit tests
---------------------

Interested in contributing? You can easily set up the unit tests environment:
Read how to change the client configuration - read [Configuration](https://github.com/zoho/zcrm-php-sdk)
- copy the `phpunit.xml.dist` file into `phpunit.xml`
- change the stored environment variable `client_secret`
- change the stored environment variable `redirect_uri`
- change the stored environment variable `currentUserEmail`
- change the stored environment variable `applicationLogFilePath`
- change the stored environment variable `persistence_handler_class`
- change the stored environment variable `token_persistence_path`
- change the stored environment variable `userid_test`
- change the stored environment variable `timeZone`
- change the stored environment variable `custom_module_singular_name`
- change the stored environment variable `custom_module_mandatory_field_name`
- change the stored environment variable `custom_module_picklist_field_name`
- change the stored environment variable `custom_module_picklist_field_value1`
- change the stored environment variable `custom_module_picklist_field_value2`
- change the stored environment variable `custom_module_date_field_name`
- change the stored environment variable `custom_module_text_field_name`



TODO
---------------

Implement searchRecords()