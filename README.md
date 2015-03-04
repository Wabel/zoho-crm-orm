[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Wabel/zoho-crm-orm/badges/quality-score.png?b=1.0)](https://scrutinizer-ci.com/g/Wabel/zoho-crm-orm/?branch=1.0)

Wabel's Zoho-CRM ORM
====================

Work in progress.
Forked from [mctekk's work](https://github.com/mctekk/zohocrm)

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

$generator->generateAll($directory, $namespace);
```

