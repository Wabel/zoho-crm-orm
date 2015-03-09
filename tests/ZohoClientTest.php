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

        $this->assertArrayHasKey('Lead Owner', $fields->getRecords()['Lead Information']);
    }

    /**
     * @throws Exception
     * @throws \Wabel\Zoho\CRM\Exception\ZohoCRMResponseException
     */
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

        $multipleContact = [];
        // Now, let's test multiple saves under 100.
        for($i = 0; $i < 98; $i++) {
            $multipleContact["contact"][$i] = new \TestNamespace\Contact();
            $multipleContact["lastName"][$i] = uniqid("Test");
            $multipleContact["email"][$i] = $multipleContact["lastName"][$i]."@test.com";
            $multipleContact["contact"][$i]->setLastName($multipleContact["lastName"][$i]);
            $multipleContact["contact"][$i]->setFirstName("TestMultipleUser");
        }
        $contactZohoDao->save($multipleContact["contact"]);


        for($i = 0; $i < 98; $i++) {
            $multipleContact["contact"][$i]->setEmail($multipleContact["email"][$i]);
        }
        $contactZohoDao->save($multipleContact["contact"]);

        // Now, let's test multiple saves over 100.
        $multiplePoolContact = [];
        for($i = 0; $i < 302; $i++) {
            $multiplePoolContact["contact"][$i] = new \TestNamespace\Contact();
            $multiplePoolContact["lastName"][$i] = uniqid("Test");
            $multiplePoolContact["email"][$i] = $multiplePoolContact["lastName"][$i]."@test.com";
            $multiplePoolContact["contact"][$i]->setLastName($multiplePoolContact["lastName"][$i]);
            $multiplePoolContact["contact"][$i]->setFirstName("TestMultiplePoolUser");
        }
        $contactZohoDao->save($multiplePoolContact["contact"]);


        for($i = 0; $i < 302; $i++) {
            $multiplePoolContact["contact"][$i]->setEmail($multiplePoolContact["email"][$i]);
        }
        $beforePoolMultiple = new DateTime();
        $contactZohoDao->save($multiplePoolContact["contact"]);



        // We need to wait for Zoho to index the record.
        sleep(120);

        // Test if the unique Contact has been well saved and is deleted
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

        $records = $contactZohoDao->searchRecords("(Last Name:$lastName)");
        $this->assertCount(0, $records);

        // Test if the 98 Contacts has been well saved and are deleted
        $records = $contactZohoDao->searchRecords("(First Name:TestMultipleUser)");

        $this->assertCount(98, $records);
        foreach ($records as $key=>$record) {
            $this->assertInstanceOf("\\TestNamespace\\Contact", $record);
            $this->assertEquals("TestMultipleUser", $record->getFirstName());
            $contactZohoDao->delete($record->getZohoId());
        }

        $records = $contactZohoDao->searchRecords("(First Name:TestMultipleUser)");
        $this->assertCount(0, $records);

        // Test if the 302 Contacts has been well saved and are deleted
        $records = $contactZohoDao->getRecords("Modified Time", "asc", $beforePoolMultiple);

        $this->assertCount(302, $records);
        foreach ($records as $key=>$record) {
            $this->assertInstanceOf("\\TestNamespace\\Contact", $record);
            $this->assertEquals("TestMultiplePoolUser", $record->getFirstName());
            $this->assertEquals($multiplePoolContact["lastName"][$key], $record->getLastName());
            $this->assertEquals($multiplePoolContact["email"][$key], $record->getEmail());
            $contactZohoDao->delete($record->getZohoId());
        }

        $records = $contactZohoDao->searchRecords("(First Name:TestMultiplePoolUser)");
        $this->assertCount(0, $records);
    }
}
