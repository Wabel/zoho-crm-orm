<?php

namespace Wabel\Zoho\CRM;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Wabel\Zoho\CRM\Service\EntitiesGeneratorService;

class ZohoDaoTest extends TestCase
{

    /**
     * @var ZohoClient
     */
    private $zohoClient;

    protected function setUp()
    {
        $this->zohoClient  = new ZohoClient(
            [
                'client_id' => getenv('client_id'),
                'client_secret' => getenv('client_secret'),
                'redirect_uri' => getenv('redirect_uri'),
                'currentUserEmail' => getenv('currentUserEmail'),
                'applicationLogFilePath' => getenv('applicationLogFilePath'),
                'persistence_handler_class' => getenv('persistence_handler_class'),
                'token_persistence_path' => getenv('token_persistence_path'),
            ],
            getenv('timeZone')
        );
        $this->entitiesGeneratorService = new EntitiesGeneratorService($this->zohoClient, new NullLogger());
    }

    /**
     * @depends Wabel\Zoho\CRM\Service\EntitiesGeneratorServiceTest::testGenerateAll
     */
    public function testDaoConstructor()
    {
        $this->assertFileExists(__DIR__.'/DaoGeneratedTest/AccountZohoDao.php');
        $this->assertFileExists(__DIR__.'/DaoGeneratedTest/Account.php');
        $fullQualifyName = 'Wabel\\Zoho\\CRM\\DaoGeneratedTest\\AccountZohoDao';
        include __DIR__.'/DaoGeneratedTest/AccountZohoDao.php';
        include __DIR__.'/DaoGeneratedTest/Account.php';
        $accountZohoDao =  new $fullQualifyName($this->zohoClient);
        $this->assertInstanceOf($fullQualifyName, $accountZohoDao);
        return $accountZohoDao;
    }


    /**
     * @depends testDaoConstructor
     */
    public function testGetZohoClient(AbstractZohoDao $accountZohoDao)
    {
        $this->assertInstanceOf('Wabel\Zoho\CRM\ZohoClient', $accountZohoDao->getZohoClient());
        $this->assertSame($this->zohoClient->getConfigurations(), $accountZohoDao->getZohoClient()->getConfigurations());
    }


    /**
     * @depends testDaoConstructor
     */
    public function testGetFields(AbstractZohoDao $accountZohoDao)
    {
        $fields = $accountZohoDao->getFields();
        $this->assertNotEmpty($accountZohoDao->getFields());
        $this->assertContainsOnlyInstancesOf('Wabel\Zoho\CRM\BeanComponents\Field', $fields);
    }

    /**
     * @depends testDaoConstructor
     */
    public function testGetZCRMModule(AbstractZohoDao $accountZohoDao)
    {
        $this->assertInstanceOf('\ZCRMModule', $accountZohoDao->getZCRMModule());
        $this->assertEquals('Accounts', $accountZohoDao->getZCRMModule()->getAPIName());
    }

    /**
     * @depends testDaoConstructor
     */
    public function testGetFieldFromFieldName(AbstractZohoDao $accountZohoDao)
    {
        $field = $accountZohoDao->getFieldFromFieldName('accountName');
        $this->assertInstanceOf('Wabel\Zoho\CRM\BeanComponents\Field', $field);
        $this->assertEquals('Account_Name', $field->getApiName());
    }

    /**
     * @depends testDaoConstructor
     * @param   AbstractZohoDao $accountZohoDao
     * @return  ZohoBeanInterface[]
     * @throws  Exceptions\ZohoCRMORMException
     */
    public function testCreateBeans(AbstractZohoDao $accountZohoDao)
    {

        $bean1 = $accountZohoDao->create();
        $bean1->setAccountName('Account Name Bean 1');
        $bean2 = $accountZohoDao->create();
        $bean2->setAccountName('Account Name Bean 2');
        $bean3 = $accountZohoDao->create();
        $bean3->setAccountName('Account Name Bean 3');
        $this->assertTrue($bean1->isDirty('accountName'));
        $this->assertTrue($bean2->isDirty('accountName'));
        $this->assertTrue($bean3->isDirty('accountName'));
        return [$bean1, $bean2, $bean3];
    }


    /**
     * @depends testCreateBeans
     * @param   ZohoBeanInterface[] $beans
     * @return  ZohoBeanInterface[]
     */
    public function testSaveInsert(array $beans)
    {
        $fullQualifyName = 'Wabel\\Zoho\\CRM\\DaoGeneratedTest\\AccountZohoDao';
        /**
         * @var $accountZohoDao AbstractZohoDao
         */
        $accountZohoDao =  new $fullQualifyName($this->zohoClient);
        $accountZohoDao->save($beans);
        $this->assertFalse($beans[0]->isDirty('accountName'));
        $this->assertFalse($beans[1]->isDirty('accountName'));
        $this->assertFalse($beans[2]->isDirty('accountName'));
        $this->assertNotEmpty($beans[0]->getZohoId());
        $this->assertNotEmpty($beans[1]->getZohoId());
        $this->assertNotEmpty($beans[2]->getZohoId());
        $this->assertEquals($beans[0]->getZohoId(), $beans[0]->getZCRMRecord()->getEntityId());
        $this->assertEquals($beans[1]->getZohoId(), $beans[1]->getZCRMRecord()->getEntityId());
        $this->assertEquals($beans[2]->getZohoId(), $beans[2]->getZCRMRecord()->getEntityId());
        $this->assertEquals('Account Name Bean 1', $beans[0]->getZCRMRecord()->getFieldValue('Account_Name'));
        $this->assertEquals('Account Name Bean 2', $beans[1]->getZCRMRecord()->getFieldValue('Account_Name'));
        $this->assertEquals('Account Name Bean 3', $beans[2]->getZCRMRecord()->getFieldValue('Account_Name'));
        return $beans;
    }

    /**
     * @depends testSaveInsert
     * @param   ZohoBeanInterface[] $beans
     * @return  ZohoBeanInterface[]
     */
    public function testSaveUpdate(array $beans)
    {
        $beans[0]->setAccountName('Account Name Bean 1 Modified');
        $beans[2]->setAccountName('Account Name Bean 3 Modified');
        $this->assertTrue($beans[0]->isDirty('accountName'));
        $this->assertTrue($beans[2]->isDirty('accountName'));
        $fullQualifyName = 'Wabel\\Zoho\\CRM\\DaoGeneratedTest\\AccountZohoDao';
        /**
         * @var $accountZohoDao AbstractZohoDao
         */
        $accountZohoDao =  new $fullQualifyName($this->zohoClient);
        $accountZohoDao->save([$beans[0], $beans[2]]);
        $this->assertFalse($beans[0]->isDirty('accountName'));
        $this->assertFalse($beans[2]->isDirty('accountName'));
        $record0 = $this->zohoClient->getRecordById($accountZohoDao->getModule(), $beans[0]->getZohoId());
        $record2 = $this->zohoClient->getRecordById($accountZohoDao->getModule(), $beans[2]->getZohoId());
        $this->assertEquals('Account Name Bean 1 Modified', $record0->getFieldValue('Account_Name'));
        $this->assertEquals('Account Name Bean 3 Modified', $record2->getFieldValue('Account_Name'));
        $beans[1]->setAccountName('Account Name Bean 2 Modified Solo');
        //Test Update Owner field
        $beans[1]->setOwnerOwnerID(getenv('userid_test'));
        $accountZohoDao->save($beans[1]);
        $record1 = $this->zohoClient->getRecordById($accountZohoDao->getModule(), $beans[1]->getZohoId());
        $this->assertEquals('Account Name Bean 2 Modified Solo', $record1->getFieldValue('Account_Name'));
        $this->assertEquals(getenv('userid_test'), $record1->getOwner()->getId());
        return $beans;
    }

    /**
     * @depends testSaveInsert
     * @param   ZohoBeanInterface[] $beans
     * @throws  Exceptions\ZohoCRMORMException
     * @throws  \ZCRMException
     */
    public function testGetRecords(array $beans)
    {
        $fullQualifyName = 'Wabel\\Zoho\\CRM\\DaoGeneratedTest\\AccountZohoDao';
        /**
         * @var $accountZohoDao AbstractZohoDao
         */
        $accountZohoDao =  new $fullQualifyName($this->zohoClient);
        $dateRecentRecords = (new \DateTime())->sub(new \DateInterval('PT30S'));
        $beanRecords = $accountZohoDao->getRecords(null, null, null, $dateRecentRecords);
        $idsRecord = array_map(
            function (ZohoBeanInterface $bean) {
                return $bean->getZohoId();
            }, $beanRecords
        );
        $this->assertContains($beans[0]->getZohoId(), $idsRecord);
        $this->assertContains($beans[1]->getZohoId(), $idsRecord);
        $this->assertContains($beans[2]->getZohoId(), $idsRecord);

    }

    /**
     * @depends testSaveUpdate
     * @param   ZohoBeanInterface[] $beans
     * @return  ZohoBeanInterface[]
     * @throws  Exceptions\ZohoCRMORMException
     * @throws  \ZCRMException
     */
    public function testGetRecordById(array $beans)
    {
        $fullQualifyName = 'Wabel\\Zoho\\CRM\\DaoGeneratedTest\\AccountZohoDao';
        /**
         * @var $accountZohoDao AbstractZohoDao
         */
        $accountZohoDao =  new $fullQualifyName($this->zohoClient);
        $beanRecord = $accountZohoDao->getById($beans[1]->getZohoId());
        $this->assertNotSame($beans[1], $beanRecord);
        $this->assertEquals($beans[1]->getZohoId(), $beanRecord->getZohoId());
        return $beans;
    }

    /**
     * @depends testGetRecordById
     * @param   ZohoBeanInterface[] $beans
     * @return  ZohoBeanInterface
     * @throws  Exceptions\ZohoCRMORMException
     */
    public function testDeleteById(array $beans)
    {
        $fullQualifyName = 'Wabel\\Zoho\\CRM\\DaoGeneratedTest\\AccountZohoDao';
        /**
         * @var $accountZohoDao AbstractZohoDao
         */
        $accountZohoDao =  new $fullQualifyName($this->zohoClient);
        $beanDeleted = $accountZohoDao->delete($beans[2]->getZohoId());
        $this->assertNotEmpty($beanDeleted);
        $this->assertEquals($beans[2]->getZohoId(), $beanDeleted[0]->getZohoId());
        return $beans[2];
    }

    /**
     * @depends testDeleteById
     * @param   ZohoBeanInterface $beanDeleted
     * @throws  \ZCRMException
     */
    public function testGetDeletedRecords(ZohoBeanInterface $beanDeleted)
    {
        $fullQualifyName = 'Wabel\\Zoho\\CRM\\DaoGeneratedTest\\AccountZohoDao';
        /**
         * @var $accountZohoDao AbstractZohoDao
         */
        $dateRecentRecords = (new \DateTime())->sub(new \DateInterval('PT30S'));
        $accountZohoDao =  new $fullQualifyName($this->zohoClient);
        $trashRecords = $accountZohoDao->getDeletedRecordIds($dateRecentRecords);
        $this->assertNotEmpty($trashRecords);
        $idsRecordTrash = array_map(
            function (\ZCRMTrashRecord $trashRecord) {
                return $trashRecord->getEntityId();
            }, $trashRecords
        );
        $this->assertContains($beanDeleted->getZohoId(), $idsRecordTrash);
    }



    /**
     * @depends Wabel\Zoho\CRM\Service\EntitiesGeneratorServiceTest::testGenerateAll
     * @return  AbstractZohoDao
     */
    public function testInitCustomModule()
    {
        $customModuleSingularName = getenv('custom_module_singular_name');
        $this->assertFileExists(__DIR__.'/DaoGeneratedTest/'.$customModuleSingularName.'ZohoDao.php');
        $this->assertFileExists(__DIR__.'/DaoGeneratedTest/'.$customModuleSingularName.'.php');
        $fullQualifyName = 'Wabel\\Zoho\\CRM\\DaoGeneratedTest\\'.$customModuleSingularName.'ZohoDao';
        include __DIR__.'/DaoGeneratedTest/'.$customModuleSingularName.'ZohoDao.php';
        include __DIR__.'/DaoGeneratedTest/'.$customModuleSingularName.'.php';
        /**
         * @var $customZohoDao AbstractZohoDao
         */
        $customZohoDao =  new $fullQualifyName($this->zohoClient);
        return $customZohoDao;
    }

    /**
     * @depends testInitCustomModule
     * @param   AbstractZohoDao $customZohoDao
     * @return  ZohoBeanInterface[]
     * @throws  Exceptions\ZohoCRMORMException
     */
    public function testCustomModuleCreateBeans(AbstractZohoDao $customZohoDao)
    {

        $fieldMandatoryRecordName = $customZohoDao->getFieldFromFieldName(getenv('custom_module_mandatory_field_name'));
        $fieldPickList = $customZohoDao->getFieldFromFieldName(getenv('custom_module_picklist_field_name'));
        $fieldDate = $customZohoDao->getFieldFromFieldName(getenv('custom_module_date_field_name'));
        $fieldText = $customZohoDao->getFieldFromFieldName(getenv('custom_module_text_field_name'));
        $bean1 = $customZohoDao->create();
        $bean1->{$fieldMandatoryRecordName->getSetter()}('Custom Name Bean 1');
        $bean1->{$fieldPickList->getSetter()}(getenv('custom_module_picklist_field_value1'));
        $bean1->{$fieldDate->getSetter()}($this->randomDateInRange(new \DateTime(), (new \DateTime())->add(new \DateInterval('P10D'))));
        $bean1->{$fieldText->getSetter()}('Custom Text 1');

        $bean2 = $customZohoDao->create();
        $bean2->{$fieldMandatoryRecordName->getSetter()}('Custom Name Bean 2');
        $bean2->{$fieldPickList->getSetter()}(getenv('custom_module_picklist_field_value2'));
        $bean2->{$fieldDate->getSetter()}($this->randomDateInRange(new \DateTime(), (new \DateTime())->add(new \DateInterval('P10D'))));
        $bean2->{$fieldText->getSetter()}('Custom Text 2');

        $this->assertTrue($bean1->isDirty($fieldMandatoryRecordName->getName()));
        $this->assertTrue($bean2->isDirty($fieldMandatoryRecordName->getName()));
        $this->assertTrue($bean1->isDirty($fieldPickList->getName()));
        $this->assertTrue($bean2->isDirty($fieldPickList->getName()));
        $this->assertTrue($bean1->isDirty($fieldDate->getName()));
        $this->assertTrue($bean2->isDirty($fieldDate->getName()));
        $this->assertTrue($bean1->isDirty($fieldText->getName()));
        $this->assertTrue($bean2->isDirty($fieldText->getName()));
        return ['dao' => $customZohoDao ,'beans' => [$bean1, $bean2]];
    }

    private function randomDateInRange(\DateTime $start, \DateTime $end)
    {
        $randomTimestamp = mt_rand($start->getTimestamp(), $end->getTimestamp());
        $randomDate = new \DateTime();
        $randomDate->setTimestamp($randomTimestamp);
        return $randomDate;
    }

    /**
     * @depends testCustomModuleCreateBeans
     * @param   array $customZohoDaoBeans
     * @return  ZohoBeanInterface[]
     */
    public function testCustomModuleSaveInsert(array $customZohoDaoBeans)
    {

        /**
         * @var $customZohoDao AbstractZohoDao
         */
        $customZohoDao = $customZohoDaoBeans['dao'];

        /**
         * @var $beans ZohoBeanInterface[]
         */
        $beans = $customZohoDaoBeans['beans'];
        $customZohoDao->save($beans);

        $this->assertFalse($beans[0]->isDirty(getenv('custom_module_mandatory_field_name')));
        $this->assertFalse($beans[0]->isDirty(getenv('custom_module_picklist_field_name')));
        $this->assertFalse($beans[0]->isDirty(getenv('custom_module_date_field_name')));
        $this->assertFalse($beans[0]->isDirty(getenv('custom_module_text_field_name')));
        $this->assertFalse($beans[1]->isDirty(getenv('custom_module_mandatory_field_name')));
        $this->assertFalse($beans[1]->isDirty(getenv('custom_module_picklist_field_name')));
        $this->assertFalse($beans[1]->isDirty(getenv('custom_module_date_field_name')));
        $this->assertFalse($beans[1]->isDirty(getenv('custom_module_text_field_name')));

        $this->assertNotEmpty($beans[0]->getZohoId());
        $this->assertNotEmpty($beans[1]->getZohoId());

        $this->assertEquals($beans[0]->getZohoId(), $beans[0]->getZCRMRecord()->getEntityId());
        $this->assertEquals($beans[1]->getZohoId(), $beans[1]->getZCRMRecord()->getEntityId());

        return ['dao' => $customZohoDao ,'beans' => $beans];
    }

    /**
     * @depends testCustomModuleSaveInsert
     * @param   array $customZohoDaoBeans
     * @throws  \Exception
     */
    public function testCustomModuleSaveUpdate(array $customZohoDaoBeans)
    {

        /**
         * @var $customZohoDao AbstractZohoDao
         */
        $customZohoDao = $customZohoDaoBeans['dao'];

        /**
         * @var $beans ZohoBeanInterface[]
         */
        $beans = $customZohoDaoBeans['beans'];

        $fieldMandatoryRecordName = $customZohoDao->getFieldFromFieldName(getenv('custom_module_mandatory_field_name'));
        $fieldPickList = $customZohoDao->getFieldFromFieldName(getenv('custom_module_picklist_field_name'));
        $fieldDate = $customZohoDao->getFieldFromFieldName(getenv('custom_module_date_field_name'));
        $fieldText = $customZohoDao->getFieldFromFieldName(getenv('custom_module_text_field_name'));

        $beans[0]->{$fieldMandatoryRecordName->getSetter()}('Custom Name Bean 1 - Modified');
        $beans[0]->{$fieldPickList->getSetter()}(getenv('custom_module_picklist_field_value2'));
        $beans[0]->{$fieldDate->getSetter()}($this->randomDateInRange(new \DateTime(), (new \DateTime())->add(new \DateInterval('P10D'))));
        $beans[0]->{$fieldText->getSetter()}('Custom Text 1- Modified');

        $beans[1]->{$fieldMandatoryRecordName->getSetter()}('Custom Name Bean 2 - Modified');
        $beans[1]->{$fieldPickList->getSetter()}(getenv('custom_module_picklist_field_value1'));
        $beans[1]->{$fieldDate->getSetter()}($this->randomDateInRange(new \DateTime(), (new \DateTime())->add(new \DateInterval('P10D'))));

        $this->assertTrue($beans[0]->isDirty(getenv('custom_module_mandatory_field_name')));
        $this->assertTrue($beans[0]->isDirty(getenv('custom_module_picklist_field_name')));
        $this->assertTrue($beans[0]->isDirty(getenv('custom_module_date_field_name')));
        $this->assertTrue($beans[0]->isDirty(getenv('custom_module_text_field_name')));

        $this->assertTrue($beans[1]->isDirty(getenv('custom_module_mandatory_field_name')));
        $this->assertTrue($beans[1]->isDirty(getenv('custom_module_picklist_field_name')));
        $this->assertTrue($beans[1]->isDirty(getenv('custom_module_date_field_name')));
        $this->assertFalse($beans[1]->isDirty(getenv('custom_module_text_field_name')));;

        $customZohoDao->save($beans);

        $this->assertFalse($beans[0]->isDirty(getenv('custom_module_mandatory_field_name')));
        $this->assertFalse($beans[0]->isDirty(getenv('custom_module_picklist_field_name')));
        $this->assertFalse($beans[0]->isDirty(getenv('custom_module_date_field_name')));
        $this->assertFalse($beans[0]->isDirty(getenv('custom_module_text_field_name')));

        $this->assertFalse($beans[1]->isDirty(getenv('custom_module_mandatory_field_name')));
        $this->assertFalse($beans[1]->isDirty(getenv('custom_module_picklist_field_name')));
        $this->assertFalse($beans[1]->isDirty(getenv('custom_module_date_field_name')));

        $record0 = $this->zohoClient->getRecordById($customZohoDao->getModule(), $beans[0]->getZohoId());
        $record1 = $this->zohoClient->getRecordById($customZohoDao->getModule(), $beans[1]->getZohoId());

        $this->assertEquals($beans[0]->{$fieldMandatoryRecordName->getGetter()}(), $record0->getFieldValue($fieldMandatoryRecordName->getApiName()));
        $this->assertEquals($beans[0]->{$fieldPickList->getGetter()}(), $record0->getFieldValue($fieldPickList->getApiName()));
        $this->assertEquals($beans[0]->{$fieldDate->getGetter()}()->format('Y-m-d'), $record0->getFieldValue($fieldDate->getApiName()));
        $this->assertEquals($beans[0]->{$fieldText->getGetter()}(), $record0->getFieldValue($fieldText->getApiName()));

        $this->assertEquals($beans[1]->{$fieldMandatoryRecordName->getGetter()}(), $record1->getFieldValue($fieldMandatoryRecordName->getApiName()));
        $this->assertEquals($beans[1]->{$fieldPickList->getGetter()}(), $record1->getFieldValue($fieldPickList->getApiName()));
        $this->assertEquals($beans[1]->{$fieldDate->getGetter()}()->format('Y-m-d'), $record1->getFieldValue($fieldDate->getApiName()));

    }

}
