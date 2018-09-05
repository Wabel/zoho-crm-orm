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
$zohoClient = new ZohoClient($zohoAuthToken);

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

Setting up unit tests
---------------------

Interested in contributing? You can easily set up the unit tests environment:

- copy the `phpunit.xml.dist` file into `phpunit.xml`
- change the stored `auth_token`
- run the tests: `vendor/bin/phpunit`


Troubleshooting
---------------

- I'm saving a bean (using the `save` method of the DAO) and searching for it afterwards (using `searchRecords`). The bean is not returned.  
  This is a Zoho issue. Zoho takes about one minute to index the records you insert. So you must wait about one minute
  before the Zoho bean you saved will be findable using the `searchRecords` method.
