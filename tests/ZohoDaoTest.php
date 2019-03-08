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
        require __DIR__.'/DaoGeneratedTest/AccountZohoDao.php';
        require __DIR__.'/DaoGeneratedTest/Account.php';
        $accountZohoDao =  new $fullQualifyName($this->zohoClient);
        $this->assertInstanceOf($fullQualifyName, $accountZohoDao);
        return $accountZohoDao;
    }


    /**
     * @depends testDaoConstructor
     */
    public function testGetZohoClient(AbstractZohoDao $accountZohoDao){
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

//    /**
//     * @depends testDaoConstructor
//     */
//    public function testAbstractMethodsHasValue(AbstractZohoDao $accountZohoDao){
//        $this->assertEquals('Accounts', $accountZohoDao->getModule());
//        $this->assertEquals('Account', $accountZohoDao->getSingularModuleName());
//        $this->assertEquals('Accounts', $accountZohoDao->getPluralModuleName());
//        $this->assertEquals('Wabel\\Zoho\\CRM\\DaoGeneratedTest\\Account', $accountZohoDao->getBeanClassName());
//
//    }

    /**
     * @depends testDaoConstructor
     */
    public function testGetZCRMModule(AbstractZohoDao $accountZohoDao){
        $this->assertInstanceOf('\ZCRMModule', $accountZohoDao->getZCRMModule());
        $this->assertEquals('Accounts', $accountZohoDao->getZCRMModule()->getAPIName());
    }

    /**
     * @depends testDaoConstructor
     */
    public function testGetFieldFromFieldName(AbstractZohoDao $accountZohoDao){
        $field = $accountZohoDao->getFieldFromFieldName('accountName');
        $this->assertInstanceOf('Wabel\Zoho\CRM\BeanComponents\Field', $field);
        $this->assertEquals('Account_Name', $field->getApiName());
    }

    /**
     * @depends testDaoConstructor
     * @param AbstractZohoDao $accountZohoDao
     * @return ZohoBeanInterface[]
     * @throws Exceptions\ZohoCRMORMException
     */
    public function testCreateBeans(AbstractZohoDao $accountZohoDao){

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
     * @param ZohoBeanInterface[] $beans
     * @return ZohoBeanInterface[]
     */
    public function testSaveInsert(array $beans){
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
     * @param ZohoBeanInterface[] $beans
     * @return ZohoBeanInterface[]
     */
    public function testSaveUpdate(array $beans){
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
        $this->assertEquals('Account Name Bean 1 Modified', $beans[0]->getZCRMRecord()->getFieldValue('Account_Name'));
        $this->assertEquals('Account Name Bean 3 Modified', $beans[2]->getZCRMRecord()->getFieldValue('Account_Name'));
        $beans[1]->setAccountName('Account Name Bean 2 Modified Solo');
        $accountZohoDao->save($beans[1]);
        $this->assertEquals('Account Name Bean 2 Modified Solo', $beans[1]->getZCRMRecord()->getFieldValue('Account_Name'));
        return $beans;
    }

    /**
     * @depends testSaveInsert
     * @param ZohoBeanInterface[] $beans
     * @throws Exceptions\ZohoCRMORMException
     * @throws \ZCRMException
     */
    public function testGetRecords(array $beans){
        $fullQualifyName = 'Wabel\\Zoho\\CRM\\DaoGeneratedTest\\AccountZohoDao';
        /**
         * @var $accountZohoDao AbstractZohoDao
         */
        $accountZohoDao =  new $fullQualifyName($this->zohoClient);
        $dateRecentRecords = (new \DateTime())->sub(new \DateInterval('PT30S'));
        $beanRecords = $accountZohoDao->getRecords(null, null, null, $dateRecentRecords);
        $idsRecord = array_map(function (ZohoBeanInterface $bean){
            return $bean->getZohoId();
        }, $beanRecords);
        $this->assertContains($beans[0]->getZohoId(),$idsRecord);
        $this->assertContains($beans[1]->getZohoId(),$idsRecord);
        $this->assertContains($beans[2]->getZohoId(),$idsRecord);

    }

    /**
     * @depends testSaveUpdate
     * @param ZohoBeanInterface[] $beans
     * @return ZohoBeanInterface[]
     * @throws Exceptions\ZohoCRMORMException
     * @throws \ZCRMException
     */
    public function testGetRecordById(array $beans){
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
     * @param ZohoBeanInterface[] $beans
     * @return ZohoBeanInterface
     * @throws Exceptions\ZohoCRMORMException
     */
    public function testDeleteById(array $beans){
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
     * @param ZohoBeanInterface $beanDeleted
     * @throws \ZCRMException
     */
    public function testGetDeletedRecords(ZohoBeanInterface $beanDeleted){
        $fullQualifyName = 'Wabel\\Zoho\\CRM\\DaoGeneratedTest\\AccountZohoDao';
        /**
         * @var $accountZohoDao AbstractZohoDao
         */
        $dateRecentRecords = (new \DateTime())->sub(new \DateInterval('PT30S'));
        $accountZohoDao =  new $fullQualifyName($this->zohoClient);
        $trashRecords = $accountZohoDao->getDeletedRecordIds($dateRecentRecords);
        $this->assertNotEmpty($trashRecords);
        $idsRecordTrash = array_map(function (\ZCRMTrashRecord $trashRecord){
            return $trashRecord->getEntityId();
        }, $trashRecords);
        $this->assertContains($beanDeleted->getZohoId(), $idsRecordTrash);
    }

}
