<?php

use Wabel\Zoho\CRM\ZohoClient;

class ZohoClientTest extends PHPUnit_Framework_TestCase
{
    public function getClient()
    {
        return new ZohoClient(ZohoClient::COM_BASE_URI, $GLOBALS['auth_token']);
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
    public function testDao()
    {
        require_once __DIR__.'/generated/Contact.php';
        require_once __DIR__.'/generated/ContactZohoDao.php';

        $contactZohoDao = new \TestNamespace\ContactZohoDao($this->getClient());

        // First, let's clean up the records (in case a previous test did not run correctly).
        $records = $contactZohoDao->searchRecords('(First Name:TestMultiplePoolUser)');
        foreach ($records as $record) {
            $contactZohoDao->delete($record->getZohoId());
        }

//
//        $lastName = uniqid("Test");
//        $email = $lastName."@test.com";
//
//        $contactBean = new \TestNamespace\Contact();
//        $contactBean->setFirstName("Testuser");
//        $contactBean->setLastName($lastName);
//        // Testing special characters.
//        $contactBean->setTitle("M&M's épatant");
//
//        $contactZohoDao->save($contactBean);
//
//        $this->assertNotEmpty($contactBean->getZohoId(), "ZohoID must be set in the bean after save.");
//
//        // Second save (to verify the updateRecords method).
//        $contactBean->setEmail($email);
//        $contactZohoDao->save($contactBean);
//        $record = $contactZohoDao->getById($contactBean->getZohoId());
//        $this->assertEquals($record[0]->getEmail(), $email);
//
//        $multipleContact = [];
//        // Now, let's test multiple saves under 100.
//        for($i = 0; $i < 98; $i++) {
//            $multipleContact["contact"][$i] = new \TestNamespace\Contact();
//            $multipleContact["lastName"][$i] = uniqid("Test");
//            $multipleContact["email"][$i] = $multipleContact["lastName"][$i]."@test.com";
//            $multipleContact["contact"][$i]->setLastName($multipleContact["lastName"][$i]);
//            $multipleContact["contact"][$i]->setFirstName("TestMultipleUser");
//        }
//        $contactZohoDao->save($multipleContact["contact"]);
//
//
//        for($i = 0; $i < 98; $i++) {
//            $multipleContact["contact"][$i]->setEmail($multipleContact["email"][$i]);
//        }
//        $contactZohoDao->save($multipleContact["contact"]);

        // Now, let's test multiple saves over 100.
        $multiplePoolContact = [];
        for ($i = 0; $i < 302; ++$i) {
            $multiplePoolContact['contact']['key'.$i] = new \TestNamespace\Contact();
            $multiplePoolContact['lastName']['key'.$i] = uniqid('Test');
            $multiplePoolContact['email']['key'.$i] = $multiplePoolContact['lastName']['key'.$i].'@test.com';
            $multiplePoolContact['contact']['key'.$i]->setLastName($multiplePoolContact['lastName']['key'.$i]);
            $multiplePoolContact['contact']['key'.$i]->setFirstName('TestMultiplePoolUser');
        }
        $contactZohoDao->save($multiplePoolContact['contact']);

        for ($i = 0; $i < 302; ++$i) {
            $multiplePoolContact['contact']['key'.$i]->setEmail($multiplePoolContact['email']['key'.$i]);
        }
        $beforePoolMultiple = new DateTime();
        $contactZohoDao->save($multiplePoolContact['contact']);

        for ($i = 0; $i < 302; ++$i) {
            $this->assertNotNull($multiplePoolContact['contact']['key'.$i]->getZohoId());
        }

        // We need to wait for Zoho to index the record.
        sleep(120);

        // Test if the unique Contact has been well saved and is deleted
//        $records = $contactZohoDao->searchRecords("(Last Name:$lastName)");
//
//        $this->assertCount(1, $records);
//        foreach ($records as $record) {
//            $this->assertInstanceOf("\\TestNamespace\\Contact", $record);
//            $this->assertEquals("Testuser", $record->getFirstName());
//            $this->assertEquals($lastName, $record->getLastName());
//            $this->assertEquals($email, $record->getEmail());
//            $this->assertEquals("M&M's épatant", $record->getTitle());
//        }
//
//        $contactZohoDao->delete($contactBean->getZohoId());
//
//        $records = $contactZohoDao->searchRecords("(Last Name:$lastName)");
//        $this->assertCount(0, $records);
//
//        // Test if the 98 Contacts has been well saved and are deleted
//        $records = $contactZohoDao->searchRecords("(First Name:TestMultipleUser)");
//
//        $this->assertCount(98, $records);
//        foreach ($records as $key=>$record) {
//            $this->assertInstanceOf("\\TestNamespace\\Contact", $record);
//            $this->assertEquals("TestMultipleUser", $record->getFirstName());
//            $contactZohoDao->delete($record->getZohoId());
//        }
//
//        $records = $contactZohoDao->searchRecords("(First Name:TestMultipleUser)");
//        $this->assertCount(0, $records);

        $records = $contactZohoDao->searchRecords('(First Name:TestMultiplePoolUser)');
        $this->assertCount(302, $records);

        $modifiedTime = $records[0]->getModifiedTime()->sub(DateInterval::createFromDateString('10 seconds'));

        // Test getRecords with a limit
        $records = $contactZohoDao->getRecords(null, null, $modifiedTime, null, 2);
        $this->assertCount(2, $records);

        // Test if the 302 Contacts has been well saved and are deleted
        //$records = $contactZohoDao->getRecords("Modified Time", "asc", $modifiedTime);
        $records = $contactZohoDao->getRecords(null, null, $modifiedTime);

        $beforeDeleteTime = new DateTimeImmutable();

        $this->assertCount(302, $records);
        foreach ($records as $key => $record) {
            $this->assertInstanceOf('\\TestNamespace\\Contact', $record);
            $this->assertEquals('TestMultiplePoolUser', $record->getFirstName());
            $this->assertContains($record->getLastName(), $multiplePoolContact['lastName']);
            $this->assertContains($record->getEmail(), $multiplePoolContact['email']);
            $contactZohoDao->delete($record->getZohoId());
        }

        $records2 = $contactZohoDao->searchRecords('(First Name:TestMultiplePoolUser)');
        $this->assertCount(0, $records2);

        $deletedRecords = $contactZohoDao->getDeletedRecordIds($beforeDeleteTime->sub(new DateInterval('PT3M')));
        // Let's check that each deleted record is present in the deleted record list.
        foreach ($records as $record) {
            $this->assertContains($record->getZohoId(), $deletedRecords);
        }
    }

    /**
     * @throws Exception
     * @throws \Wabel\Zoho\CRM\Exception\ZohoCRMResponseException
     */
    public function testDaoUpdateException()
    {
        require_once __DIR__.'/generated/Contact.php';
        require_once __DIR__.'/generated/ContactZohoDao.php';

        $contactZohoDao = new \TestNamespace\ContactZohoDao($this->getClient());

        // Now, let's try to update records that do not exist.
        $notExistingRecords = array();

        $contact1 = new \TestNamespace\Contact();
        $contact1->setZohoId('123495843958439432');
        $contact1->setLastName('I DONT EXIST');
        $contact1->setFirstName('I DONT EXIST');
        $contact1->setEmail('i.dont@exist.com');
        $notExistingRecords[] = $contact1;

        $contact2 = new \TestNamespace\Contact();
        $contact2->setZohoId('54349584395439432');
        $contact2->setLastName('I DONT EXIST AT ALL');
        $contact2->setFirstName('I DONT EXIST AT ALL');
        $contact2->setEmail('i.dont@existatall.com');
        $notExistingRecords[] = $contact2;

        $updateExceptionTriggered = false;
        try {
            $contactZohoDao->updateRecords($notExistingRecords);
        } catch (\Wabel\Zoho\CRM\Exception\ZohoCRMUpdateException $updateException) {
            $updateExceptionTriggered = true;
            $failedBeans = $updateException->getFailedBeans();
            $this->assertTrue($failedBeans->offsetExists($contact1));
            $this->assertTrue($failedBeans->offsetExists($contact2));
            $innerException = $failedBeans->offsetGet($contact1);
            $this->assertEquals('401.2', $innerException->getZohoCode());
            $this->assertEquals(2, $failedBeans->count());
        }

        $this->assertTrue($updateExceptionTriggered);
    }

    protected function tearDown()
    {
        if (class_exists('\TestNamespace\ContactZohoDao')) {
            $contactZohoDao = new \TestNamespace\ContactZohoDao($this->getClient());
            // Let's end by removing past inserted clients:
            $pastContacts = $contactZohoDao->searchRecords('(First Name:TestMultiplePoolUser)');
            foreach ($pastContacts as $pastContact) {
                $contactZohoDao->delete($pastContact->getZohoId());
            }
        }
    }
}
