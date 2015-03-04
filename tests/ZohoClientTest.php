<?php

class ZohoClientTest extends PHPUnit_Framework_TestCase
{

    public function getClient()
    {
        return new \Wabel\Zoho\CRM\ZohoClient($GLOBALS['auth_token']);
    }

    public function testGetModules()
    {
        $zohoClient = $this->getClient();

        $modules = $zohoClient->getModules();

        $found = false;
        foreach ($modules->getRecords() as $record) {
            if ($record['pl'] == 'Leads') {
                $found = true;
            }
        }


        $this->assertTrue($found);
    }

    public function testGetFields()
    {
        $zohoClient = $this->getClient();

        $fields = $zohoClient->getFields('Leads');

        $this->assertArrayHasKey('Company', $fields->getRecords()['Company Information']);
    }

    public function testDao() {
        require __DIR__.'/generated/Contact.php';
        require __DIR__.'/generated/ContactZohoDao.php';

        $contactZohoDao = new \TestNamespace\ContactZohoDao($this->getClient());

        $lastName = uniqid("Test");
        $email = $lastName."@test.com";

        $contactBean = new \TestNamespace\Contact();
        $contactBean->setFirstName("Testuser");
        $contactBean->setLastName($lastName);
        // Testing special characters.
        $contactBean->setTitle("M&M's épatant");

        $contactZohoDao->save($contactBean);

        $this->assertNotEmpty($contactBean->getZohoId(), "ZohoID must be set in the bean after save.");

        // Second save (to verify the updateRecords method).
        $contactBean->setEmail($email);
        $contactZohoDao->save($contactBean);

        // We need to wait for Zoho to index the record.
        sleep(120);

        $records = $contactZohoDao->searchRecords("(Last Name:$lastName)");

        $this->assertCount(1, $records);
        foreach ($records as $record) {
            $this->assertInstanceOf("\\TestNamespace\\Contact", $record);
            $this->assertEquals("Testuser", $record->getFirstName());
            $this->assertEquals($lastName, $record->getLastName());
            $this->assertEquals($email, $record->getEmail());
            $this->assertEquals("M&M's épatant", $record->getTitle());
        }


        $contactZohoDao->delete($contactBean->getZohoId());
    }
}
