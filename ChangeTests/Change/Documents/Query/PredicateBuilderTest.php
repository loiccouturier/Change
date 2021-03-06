<?php
namespace ChangeTests\Change\Documents\Query;

use Change\Documents\Query\PredicateBuilder;
use ChangeTests\Change\TestAssets\TestCase;

class PredicateBuilderTest extends TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function setUp()
	{
		parent::setUp();
		$this->getApplicationServices()->getTransactionManager()->begin();
	}

	protected function tearDown()
	{
		$this->getApplicationServices()->getTransactionManager()->commit();
		parent::tearDown();
	}

	public function testInitializeDB()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$dm = $this->getApplicationServices()->getDocumentManager();
		$basicDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$basicDoc->initialize(1000);
		$basicDoc->setPStr('Test 1000');
		$basicDoc->setPInt(1001);
		$basicDoc->setPFloat(5.0);
		$basicDoc->setPDocId(1002);
		$basicDoc->save();

		$basicDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$basicDoc->initialize(1001);
		$basicDoc->setPStr('1001 Test');
		$basicDoc->setPInt(1001);
		$basicDoc->setPDocId(1000);
		$basicDoc->save();

		$basicDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$basicDoc->initialize(1002);
		$basicDoc->setPStr('1002');
		$basicDoc->setPInt(7);
		$basicDoc->setPDocId(1000);
		$basicDoc->save();

		/* @var $localizedDoc \Project\Tests\Documents\Localized */
		$dm->pushLCID('fr_FR');
		$localizedDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$localizedDoc->initialize(1005);
		$localizedDoc->setPStr('test 1005');
		$localizedDoc->getCurrentLocalization()->setPLStr('text un');
		$localizedDoc->setPInt(1001);
		$localizedDoc->getCurrentLocalization()->setPLInt(1001);
		$localizedDoc->setPDocInst($dm->getDocumentInstance(1000));
		$localizedDoc->setPDocArr(array($dm->getDocumentInstance(1000), $dm->getDocumentInstance(1002)));
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$localizedDoc->getCurrentLocalization()->setPLStr('text one');
		$localizedDoc->getCurrentLocalization()->setPLInt(1002);
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$localizedDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$localizedDoc->initialize(1006);
		$localizedDoc->setPStr('test 1006');
		$localizedDoc->getCurrentLocalization()->setPLStr('text two');
		$localizedDoc->getCurrentLocalization()->setPLInt(7);
		$localizedDoc->setPDocInst($dm->getDocumentInstance(1001));
		$localizedDoc->setPDocArr(array($dm->getDocumentInstance(1002)));
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$localizedDoc->getCurrentLocalization()->setPLStr('text one');
		$localizedDoc->save();
		$dm->popLCID();

		$treeManager = $this->getApplicationServices()->getTreeManager();
		/* @var $folderDoc \Rbs\Generic\Documents\Folder */
		$folderDoc = $dm->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
		$folderDoc->setLabel('Root');
		$folderDoc->initialize(2000);
		$folderDoc->save();
		$rn = $treeManager->insertRootNode($folderDoc, 'Project_Tests');

		$folderDoc = $dm->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
		$folderDoc->setLabel('F 1');
		$folderDoc->initialize(2001);
		$folderDoc->save();
		$treeManager->insertNode($rn, $folderDoc);
		$treeManager->insertNode($rn, $dm->getDocumentInstance(1000));

		$folderDoc = $dm->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
		$folderDoc->setLabel('F 10');
		$folderDoc->initialize(2010);
		$folderDoc->save();
		$n = $treeManager->insertNode($treeManager->getNodeById(2001), $folderDoc);
		$treeManager->insertNode($n, $dm->getDocumentInstance(1001));
		$treeManager->insertNode($n, $dm->getDocumentInstance(1002));

		$folderDoc = $dm->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
		$folderDoc->setLabel('F 11');
		$folderDoc->initialize(2011);
		$folderDoc->save();
		$treeManager->insertNode($n, $folderDoc, $treeManager->getNodeById(1002));

		/* @var $corDoc \Project\Tests\Documents\Correction */
		$corDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Correction');
		$corDoc->initialize(3000);
		$corDoc->setLabel('C0');
		$corDoc->save();

		$corDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Correction');
		$corDoc->initialize(3001);
		$corDoc->setLabel('C1');
		$corDoc->save();
		$corDoc->getCurrentLocalization()->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_PUBLISHABLE);
		$corDoc->save();
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testConstruct()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = new PredicateBuilder($builder);
		$this->assertNotNull($pb);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testLogicAnd()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();

		$predicate = $pb->logicAnd($pb->eq('id', 1001), $pb->eq('pInt', 1001));
		$this->assertInstanceOf('\Change\Db\Query\Predicates\Conjunction', $predicate);

		$this->assertCount(2, $predicate->getArguments());
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1001, $ids);

		$predicate = $pb->logicAnd(array($pb->eq('id', 1001), $pb->eq('pInt', 1001)));
		$this->assertInstanceOf('\Change\Db\Query\Predicates\Conjunction', $predicate);

		$this->assertCount(2, $predicate->getArguments());

		$predicate = $pb->logicAnd(array($pb->eq('id', 1001), $pb->eq('pInt', 1001), $pb->eq('id', 1001)));
		$this->assertCount(3, $predicate->getArguments());

		try
		{

			$pb->logicAnd(11);
			$this->fail('InvalidArgumentException expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$str = 'Argument 1 must be a valid InterfaceSQLFragment';
			$this->assertEquals($str, $e->getMessage());
		}
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testLogicOr()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();

		$predicate = $pb->logicOr($pb->eq('id', 1001), $pb->eq('pInt', 1001));
		$this->assertInstanceOf('\Change\Db\Query\Predicates\Disjunction', $predicate);

		$this->assertCount(2, $predicate->getArguments());
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertContains(1000, $ids);
		$this->assertContains(1001, $ids);

		$predicate = $pb->logicOr(array($pb->eq('id', 1001), $pb->eq('pInt', 1001)));
		$this->assertInstanceOf('\Change\Db\Query\Predicates\Disjunction', $predicate);

		$this->assertCount(2, $predicate->getArguments());

		$predicate = $pb->logicOr(array($pb->eq('id', 1001), $pb->eq('pInt', 1001), $pb->eq('id', 1001)));
		$this->assertCount(3, $predicate->getArguments());

		try
		{

			$pb->logicOr(11);
			$this->fail('InvalidArgumentException expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$str = 'Argument 1 must be a valid InterfaceSQLFragment';
			$this->assertEquals($str, $e->getMessage());
		}
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testEq()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->eq('id', 1000);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1000, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Localized');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->eq('pLStr', 'text un');
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1005, $ids);

		try
		{
			$pb->eq('invalid', 'text un');
			$this->fail('InvalidArgumentException expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$str = 'Argument 1 must be a valid property';
			$this->assertEquals($str, $e->getMessage());
		}
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testNeq()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->neq('id', 1000);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertContains(1001, $ids);
		$this->assertContains(1002, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testGt()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->gt('id', 1001);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1002, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testLt()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->lt('id', 1001);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1000, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testGte()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->gte('id', 1001);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertContains(1001, $ids);
		$this->assertContains(1002, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testLte()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->lte('id', 1001);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertContains(1001, $ids);
		$this->assertContains(1000, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testLike()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->like('pStr', 'test');
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertContains(1001, $ids);
		$this->assertContains(1000, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->like('pStr', 'test', \Change\Db\Query\Predicates\Like::ANYWHERE, true);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(0, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->like('pStr', 'test', \Change\Db\Query\Predicates\Like::BEGIN);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1000, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->like('pStr', 'test', \Change\Db\Query\Predicates\Like::END);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1001, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testIn()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->in('id', 1000, 2000, 1002);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertContains(1000, $ids);
		$this->assertContains(1002, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->in('id', array('A', 1000, '1001'));
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertContains(1000, $ids);
		$this->assertContains(1001, $ids);

		try
		{

			$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
			$pb = $builder->getPredicateBuilder();
			$predicate = $pb->in('id', array());
			$builder->andPredicates($predicate);
			$builder->getDocuments()->ids();
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$str = 'Right Hand Expression must be a ExpressionList with one element or more';
			$this->assertEquals($str, $e->getMessage());
		}
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testNotIn()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->notIn('id', 1000, 2000, 1002);
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1001, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testIsNull()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->isNull('pFloat');
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertContains(1001, $ids);
		$this->assertContains(1002, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->isNull('pDocArr');
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(3, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Localized');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->isNull('pDocArr');
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(0, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testIsNotNull()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->isNotNull('pFloat');
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(1000, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->isNotNull('pDocArr');
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(0, $ids);

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Localized');
		$pb = $builder->getPredicateBuilder();
		$predicate = $pb->isNotNull('pDocArr');
		$builder->andPredicates($predicate);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testPublished()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Basic');
		$pb = $builder->getPredicateBuilder();
		$str = 'Model is not publishable: Project_Tests_Basic';
		try
		{

			$builder->andPredicates($pb->published());
			$this->fail('Exception');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals($str, $e->getMessage());
		}

		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Correction');
		$pb = $builder->getPredicateBuilder();
		$builder->andPredicates($pb->published());
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(1, $ids);
		$this->assertContains(3001, $ids);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testPublishedAt()
	{
		$builder = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Project_Tests_Correction');
		$pb = $builder->getPredicateBuilder();
		$at = \DateTime::createFromFormat(\DateTime::ISO8601, '2013-07-04T15:04:08Z');
		$to = \DateTime::createFromFormat(\DateTime::ISO8601, '2013-08-04T15:04:08Z');
		$predicate = $pb->published($at, $to);
		$builder->andPredicates($predicate);
		$this->assertEquals('("_t0L"."publicationstatus" = :_p1 AND ("_t0L"."startpublication" IS NULL OR "_t0L"."startpublication" <= :_p2) AND ("_t0L"."endpublication" IS NULL OR "_t0L"."endpublication" > :_p3))',
			$predicate->toSQL92String());
		$q = $builder->dbQueryBuilder()->query();
		$this->assertEquals($at, $q->getParameterValue('_p2'));
		$this->assertEquals($to, $q->getParameterValue('_p3'));
	}
}
