<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock;

/**
 * @name \Rbs\Stock\StockManager
 */
class StockManager
{
	const INVENTORY_UNIT_PIECE = 0;

	const UNLIMITED_LEVEL = 1000000;

	const THRESHOLD_AVAILABLE = 'AVAILABLE';

	const THRESHOLD_UNAVAILABLE = 'UNAVAILABLE';

	/**
	 * @var \Rbs\Commerce\Std\Context
	 */
	protected $context;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\Collection\CollectionManager
	 */
	protected $collectionManager;

	/**
	 * @param \Rbs\Commerce\Std\Context $context
	 * @return $this
	 */
	public function setContext(\Rbs\Commerce\Std\Context $context)
	{
		$this->context = $context;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Std\Context
	 */
	protected function getContext()
	{
		return $this->context;
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager($transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	protected function getTransactionManager()
	{
		return $this->transactionManager;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return $this
	 */
	public function setDbProvider($dbProvider)
	{
		$this->dbProvider = $dbProvider;
		return $this;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\Collection\CollectionManager $collectionManager
	 * @return $this
	 */
	public function setCollectionManager($collectionManager)
	{
		$this->collectionManager = $collectionManager;
		return $this;
	}

	/**
	 * @return \Change\Collection\CollectionManager
	 */
	protected function getCollectionManager()
	{
		return $this->collectionManager;
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @return \Rbs\Stock\Documents\InventoryEntry|null
	 */
	public function getInventoryEntry($sku, $warehouse = null)
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Stock_InventoryEntry');
		$query->andPredicates(
			$query->eq('sku', $sku),
			$query->eq('warehouse', $warehouse)
		);
		return $query->getFirstDocument();
	}

	/**
	 * @param integer $level
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @throws \Exception
	 * @return \Rbs\Stock\Documents\InventoryEntry
	 */
	public function setInventory($level, $sku, $warehouse = null)
	{
		$entry = $this->getInventoryEntry($sku, $warehouse);
		if ($entry === null)
		{
			/* @var $entry \Rbs\Stock\Documents\InventoryEntry */
			$entry = $this->getDocumentManager()
				->getNewDocumentInstanceByModelName('Rbs_Stock_InventoryEntry');
		}
		$tm = $this->getTransactionManager();
		try
		{
			$tm->begin();
			$entry->setLevel($level);
			$entry->setSku($sku);
			$entry->setWarehouse($warehouse);
			$entry->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $entry;
	}

	/**
	 * @param string $target
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @return array
	 */
	public function getInventoryMovementsByTarget($target, $warehouse = null)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder('stock::getInventoryMovements');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('sku_id'), $fb->column('movement'), $fb->column('warehouse_id'), $fb->column('date'));
			$qb->from($fb->table('rbs_stock_dat_mvt'));
			$logicAnd = $fb->logicAnd($fb->eq($fb->column('target'), $fb->parameter('target')));
			if ($warehouse instanceof \Rbs\Stock\Documents\AbstractWarehouse)
			{
				$logicAnd->addArgument($fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId')));
			}
			$qb->where($logicAnd);
		}
		$query = $qb->query();
		$query->bindParameter('target', $target);
		if ($warehouse instanceof \Rbs\Stock\Documents\AbstractWarehouse)
		{
			$query->bindParameter('warehouseId', $warehouse->getId());
		}
		return $query->getResults($query->getRowsConverter()->addIntCol('sku_id', 'movement', 'warehouse_id')->addDtCol('date'));
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|integer|null $warehouse
	 * @param integer|null $limit
	 * @param integer|null $offset
	 * @param string|null $orderCol
	 * @param string|null $orderSort
	 * @return array
	 */
	public function getInventoryMovementsBySku($sku, $warehouse = null, $limit= null, $offset = null, $orderCol = null, $orderSort = null)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('id'), $fb->column('target'), $fb->column('movement'), $fb->column('warehouse_id'), $fb->column('date'));
		$qb->from($fb->table('rbs_stock_dat_mvt'));
		$logicAnd = $fb->logicAnd(
			$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
		);
		if ($warehouse !== null)
		{
			$logicAnd->addArgument($fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId')));
		}
		$qb->where($logicAnd);

		if ($orderSort !== null && $orderCol !== null)
		{
			if ($orderSort === 'desc')
			{
				$qb->orderDesc($fb->column($orderCol));
			}
			else
			{
				$qb->orderAsc($fb->column($orderCol));
			}
		}

		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		if ($warehouse !== null)
		{
			$warehouseId = $warehouse instanceof \Rbs\Stock\Documents\AbstractWarehouse ? $warehouse->getId() : intval($warehouse);
			$query->bindParameter('warehouseId', $warehouseId);
		}

		if ($limit !== null && $offset !== null)
		{
			$query->setMaxResults($limit);
			$query->setStartIndex($offset);
		}

		return $query->getResults(
			$query->getRowsConverter()->addStrCol('target')->addIntCol('id', 'movement', 'warehouse_id')->addDtCol('date')
		);
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|integer|null $warehouse
	 * @return integer
	 */
	public function countInventoryMovementsBySku($sku, $warehouse = null)
	{
		$key = 'stock::countInventoryMovementsBySku';
		if ($warehouse !== null)
		{
			$key = 'stock::countInventoryMovementsBySkuAndWarehouse';
		}

		$qb = $this->getDbProvider()->getNewQueryBuilder($key);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->func('count', '*'), 'rowCount'));
			$qb->from($fb->table('rbs_stock_dat_mvt'));
			$logicAnd = $fb->logicAnd(
				$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
			);
			if ($warehouse !== null)
			{
				$logicAnd->addArgument($fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId')));
			}
			$qb->where($logicAnd);
		}
		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		if ($warehouse !== null)
		{
			$warehouseId = $warehouse instanceof \Rbs\Stock\Documents\AbstractWarehouse ? $warehouse->getId() : intval($warehouse);
			$query->bindParameter('warehouseId', $warehouseId);
		}

		return $query->getFirstResult($query->getRowsConverter()->addIntCol('rowCount')->singleColumn('rowCount'));
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|integer|null $warehouse
	 * @return integer
	 */
	public function getValueOfMovementsBySku($sku, $warehouse = null)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->alias($fb->sum($fb->column('movement')), 'movement'));
		$qb->from($fb->table('rbs_stock_dat_mvt'));
		$logicAnd = $fb->logicAnd(
			$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
		);
		if ($warehouse !== null)
		{
			$logicAnd->addArgument($fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId')));
		}
		$qb->where($logicAnd);
		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		if ($warehouse !== null)
		{
			$warehouseId = $warehouse instanceof \Rbs\Stock\Documents\AbstractWarehouse ? $warehouse->getId() : intval($warehouse);
			$query->bindParameter('warehouseId', $warehouseId);
		}

		return $query->getFirstResult($query->getRowsConverter()->addIntCol('movement')->singleColumn('movement'));
	}

	/**
	 * Positive = receipt, negative = issue
	 * @param integer $amount
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @param \DateTime|null $date
	 * @param string $target
	 * @throws \Exception
	 * @return integer
	 */
	public function addInventoryMovement($amount, $sku, $warehouse = null, $date = null, $target = null)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('addInventoryIssueReceipt');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('rbs_stock_dat_mvt'),
				$fb->column('sku_id'), $fb->column('movement'), $fb->column('warehouse_id'), $fb->column('date'), $fb->column('target'));
			$qb->addValues(
				$fb->integerParameter('skuId'), $fb->integerParameter('amount'), $fb->integerParameter('warehouseId'),
				$fb->dateTimeParameter('dateValue'), $fb->parameter('target'));
		}
		$warehouseId = ($warehouse instanceof \Rbs\Stock\Documents\AbstractWarehouse ? $warehouse->getId() : 0);
		$dateValue = ($date instanceof \DateTime) ? $date : new \DateTime();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);

		$is = $qb->insertQuery();
		$is->bindParameter('skuId', $skuId)
			->bindParameter('amount', $amount)
			->bindParameter('warehouseId', $warehouseId)
			->bindParameter('dateValue', $dateValue)
			->bindParameter('target', $target);
		$is->execute();
		return $is->getDbProvider()->getLastInsertId('rbs_catalog_dat_attribute');
	}

	/**
	 * @param integer $movementId
	 */
	public function deleteInventoryMovementById($movementId)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('stock::deleteInventoryMovementById');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->table('rbs_stock_dat_mvt'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('movementId')));
		}
		$statement = $qb->deleteQuery();
		$statement->bindParameter('movementId', $movementId);
		$statement->execute();
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param integer|\Rbs\Store\Documents\WebStore $store
	 * @return integer
	 */
	public function getInventoryLevel(\Rbs\Stock\Documents\Sku $sku, $store = null)
	{
		if ($sku->getUnlimitedInventory())
		{
			return static::UNLIMITED_LEVEL;
		}

		$query = $this->getDocumentManager()->getNewQuery('Rbs_Stock_InventoryEntry');
		$query->andPredicates($query->eq('sku', $sku), $query->eq('warehouse', 0));
		$dbQueryBuilder = $query->dbQueryBuilder();
		$fb = $dbQueryBuilder->getFragmentBuilder();

		$docTable = $query->getTableAliasName();
		$mvtTable = $fb->table('rbs_stock_dat_mvt');

		$dbQueryBuilder->leftJoin($mvtTable, $fb->logicAnd(
			$fb->eq($fb->getDocumentColumn('sku', $docTable), $fb->column('sku_id', $mvtTable)),
			$fb->eq($fb->getDocumentColumn('warehouse', $docTable), $fb->column('warehouse_id', $mvtTable))
		));
		$sum = $fb->alias($fb->sum($fb->column('movement', $mvtTable)), 'movement');
		$level = $fb->alias($fb->getDocumentColumn('level', $docTable), 'level');

		$dbQueryBuilder->addColumn($level);
		$dbQueryBuilder->addColumn($sum);

		$result = $dbQueryBuilder->query()->getFirstResult();
		$level = intval($result['level']);
		$movement = intval($result['movement']);

		if ($store === null)
		{
			$store = $this->getContext()->getWebStore();
		}

		if ($store)
		{
			$skuId = $sku->getId();
			$storeId = ($store instanceof \Change\Documents\AbstractDocument) ? $store->getId() : intval($store);
			return $level + $movement - $this->getReservedQuantity($skuId, $storeId);
		}
		return $level + $movement;
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku[] $skus
	 * @param integer|\Rbs\Store\Documents\WebStore|null $store
	 * @return integer
	 */
	public function getInventoryLevelForManySku($skus, $store = null)
	{
		$skusId = array();
		foreach ($skus as $sku)
		{
			if ($sku->getUnlimitedInventory())
			{
				return static::UNLIMITED_LEVEL;
			}
			$skusId[] = $sku->getId();
		}

		$query = $this->getDocumentManager()->getNewQuery('Rbs_Stock_InventoryEntry');
		$query->andPredicates($query->in('sku', $skusId), $query->eq('warehouse', 0));
		$dbQueryBuilder = $query->dbQueryBuilder();
		$fb = $dbQueryBuilder->getFragmentBuilder();

		$docTable = $query->getTableAliasName();
		$mvtTable = $fb->table('rbs_stock_dat_mvt');

		$dbQueryBuilder->leftJoin($mvtTable, $fb->logicAnd(
			$fb->eq($fb->getDocumentColumn('sku', $docTable), $fb->column('sku_id', $mvtTable)),
			$fb->eq($fb->getDocumentColumn('warehouse', $docTable), $fb->column('warehouse_id', $mvtTable))
		));
		$sum = $fb->alias($fb->sum($fb->column('movement', $mvtTable)), 'movement');
		$level = $fb->alias($fb->getDocumentColumn('level', $docTable), 'level');
		$dbQueryBuilder->addColumn($level);
		$dbQueryBuilder->addColumn($sum);
		$result = $dbQueryBuilder->query()->getFirstResult();
		$level = intval($result['level']);
		$movement = intval($result['movement']);
		if ($store === null)
		{
			$store = $this->getContext()->getWebStore();
		}

		if ($store)
		{
			$storeId = ($store instanceof \Change\Documents\AbstractDocument) ? $store->getId() : intval($store);
			return $level + $movement - $this->getReservedQuantity($skusId, $storeId);
		}
		return $level + $movement;
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param integer|\Rbs\Store\Documents\WebStore $store
	 * @param integer $level
	 * @return string
	 */
	public function getInventoryThreshold(\Rbs\Stock\Documents\Sku $sku, $store = null, $level = null)
	{
		if ($level === null)
		{
			$level = $this->getInventoryLevel($sku, $store);
		}
		$thresholds = $sku->getThresholds();
		if (!is_array($thresholds) || !count($thresholds))
		{
			$thresholds = $sku->getDefaultThresholds();
		}

		foreach ($thresholds as $threshold)
		{
			if ($level <= $threshold['l'])
			{
				return $threshold['c'];
			}
		}
		return $level > 0 ? static::THRESHOLD_AVAILABLE : static::THRESHOLD_UNAVAILABLE;
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku[] $skus
	 * @param integer|\Rbs\Store\Documents\WebStore $store
	 * @param integer $level
	 * @return string
	 */
	public function getInventoryThresholdForManySku($skus, $store = null, $level = null)
	{
		if ($level === null)
		{
			$level = $this->getInventoryLevelForManySku($skus, $store);
		}

		return $level > 0 ? static::THRESHOLD_AVAILABLE : static::THRESHOLD_UNAVAILABLE;
	}


	/**
	 * @param string $threshold
	 * @return string|null
	 */
	public function getInventoryThresholdTitle($threshold)
	{
		if ($threshold)
		{
			$cm = $this->getCollectionManager();
			$collection = $cm->getCollection('Rbs_Stock_Collection_Threshold');
			if ($collection)
			{
				$item = $collection->getItemByValue($threshold);
				if ($item)
				{
					return $item->getTitle();
				}
			}
		}
		return null;
	}

	/**
	 * Return not reserved quantity
	 * @param string $targetIdentifier
	 * @param \Rbs\Stock\Interfaces\Reservation[] $reservations
	 * @throws \Exception
	 * @return \Rbs\Stock\Interfaces\Reservation[]
	 */
	public function setReservations($targetIdentifier, array $reservations)
	{
		$date = new \DateTime();
		$transactionManager = $this->getTransactionManager();
		$result = array();
		try
		{
			$transactionManager->begin();

			/* @var $currentReservations \Rbs\Stock\Std\Reservation[] */
			$currentReservations = $this->getReservationsByTarget($targetIdentifier);

			foreach ($reservations as $reservation)
			{
				$sku = $this->getSkuByCode($reservation->getCodeSku());
				if (!$sku)
				{
					$result[] = $reservation;
					continue;
				}

				$currentReservation = array_reduce($currentReservations,
					function ($result, \Rbs\Stock\Std\Reservation $res) use ($reservation)
					{
						return $res->isSame($reservation) ? $res : $result;
					});

				if (!$sku->getUnlimitedInventory() && !$sku->getAllowBackorders())
				{
					$level = $this->getInventoryLevel($sku, $reservation->getWebStoreId());
					if ($currentReservation instanceof \Rbs\Stock\Std\Reservation)
					{
						$level += $currentReservation->getQuantity();
					}
					if ($level < $reservation->getQuantity())
					{
						$result[] = $reservation;
						if ($reservation instanceof \Rbs\Commerce\Cart\CartReservation)
						{
							$reservation->setQuantityNotReserved($reservation->getQuantity() - $level);
						}
						continue;
					}
				}

				if ($currentReservation instanceof \Rbs\Stock\Std\Reservation)
				{
					$currentReservation->setQuantity($reservation->getQuantity());
					$this->updateReservation($currentReservation, $date);
				}
				else
				{
					$currentReservation = (new \Rbs\Stock\Std\Reservation())->fromReservation($reservation);
					$currentReservation->setSkuId($sku->getId());
					$this->insertReservation($targetIdentifier, $currentReservation, $date);
				}
			}

			foreach ($currentReservations as $currentReservation)
			{
				$res = array_reduce($reservations, function (\Rbs\Stock\Std\Reservation $result = null, $res)
				{
					return ($result && $result->isSame($res)) ? null : $result;
				}, $currentReservation);

				if ($res instanceof \Rbs\Stock\Std\Reservation)
				{
					$this->deleteReservationById($res->getId());
				}
			}
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
		return $result;
	}

	/**
	 * @param integer | integer[] $skuId
	 * @param integer $storeId
	 * @return integer
	 */
	protected function getReservedQuantity($skuId, $storeId)
	{

		if (is_array($skuId))
		{
			if (count($skuId) > 1)
			{
				return $this->getReservedQuantityByArray($skuId, $storeId);
			}
			$skuId = $skuId[0];
		}

		$qb = $this->getDbProvider()->getNewQueryBuilder('stock::getReservedQuantity');

		if (!$qb->isCached())
		{

			$fb = $qb->getFragmentBuilder();
			$resTable = $fb->table('rbs_stock_dat_res');
			$qb->select($fb->alias($fb->sum($fb->column('reservation')), 'quantity'));
			$qb->from($resTable);

			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId')),
					$fb->eq($fb->column('store_id'), $fb->integerParameter('storeId'))
				)
			);
		}
		$query = $qb->query();
		$query->bindParameter('skuId', $skuId);
		$query->bindParameter('storeId', $storeId);
		return intval($query->getFirstResult($query->getRowsConverter()->addIntCol('quantity')));
	}

	/**
	 * @param integer[] $skuIds
	 * @param integer $storeId
	 * @return integer
	 */
	protected function getReservedQuantityByArray($skuIds, $storeId)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder('stock::getReservedQuantity');

		$fb = $qb->getFragmentBuilder();
		$resTable = $fb->table('rbs_stock_dat_res');
		$qb->select($fb->alias($fb->sum($fb->column('reservation')), 'quantity'));
		$qb->from($resTable);

		$qb->where(
			$fb->logicAnd(
				$fb->in($fb->column('sku_id'), $skuIds),
				$fb->eq($fb->column('store_id'), $fb->integerParameter('storeId'))
			)
		);

		$query = $qb->query();
		$query->bindParameter('storeId', $storeId);
		return intval($query->getFirstResult($query->getRowsConverter()->addIntCol('quantity')));
	}



	/**
	 * @param string $targetIdentifier
	 * @param \Rbs\Stock\Std\Reservation $reservation
	 * @param \DateTime $date
	 */
	protected function insertReservation($targetIdentifier, \Rbs\Stock\Std\Reservation $reservation, \DateTime $date)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('stock::insertReservation');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('rbs_stock_dat_res'),
				$fb->column('sku_id'), $fb->column('reservation'),
				$fb->column('store_id'), $fb->column('target'), $fb->column('date'));
			$qb->addValues($fb->integerParameter('skuId'), $fb->integerParameter('reservation'),
				$fb->integerParameter('storeId'), $fb->parameter('target'), $fb->dateTimeParameter('date')
			);
		}
		$statement = $qb->insertQuery();
		$statement->bindParameter('skuId', $reservation->getSkuId());
		$statement->bindParameter('reservation', $reservation->getQuantity());
		$statement->bindParameter('storeId', $reservation->getWebStoreId());
		$statement->bindParameter('target', $targetIdentifier);
		$statement->bindParameter('date', $date);
		$statement->execute();
		$reservation->setId($statement->getDbProvider()->getLastInsertId('rbs_stock_dat_res'));
	}

	/**
	 * @param \Rbs\Stock\Std\Reservation $reservation
	 * @param \DateTime $date
	 */
	protected function updateReservation(\Rbs\Stock\Std\Reservation $reservation, \DateTime $date)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('stock::updateReservation');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_stock_dat_res'));
			$qb->assign($fb->column('reservation'), $fb->integerParameter('reservation'));
			$qb->assign($fb->column('date'), $fb->dateTimeParameter('date'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('reservationId')));
		}
		$statement = $qb->updateQuery();
		$statement->bindParameter('reservation', $reservation->getQuantity());
		$statement->bindParameter('date', $date);
		$statement->bindParameter('reservationId', $reservation->getId());
		$statement->execute();
	}

	/**
	 * @param integer $reservationId
	 */
	protected function deleteReservationById($reservationId)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('stock::deleteReservationById');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->table('rbs_stock_dat_res'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('reservationId')));
		}
		$statement = $qb->deleteQuery();
		$statement->bindParameter('reservationId', $reservationId);
		$statement->execute();
	}

	/**
	 * @param string $targetIdentifier
	 * @return \Rbs\Stock\Interfaces\Reservation[]
	 */
	public function getReservationsByTarget($targetIdentifier)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder('stock::getReservations');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$skuTable = $fb->getDocumentTable('Rbs_Stock_Sku');
			$resTable = $fb->table('rbs_stock_dat_res');
			$qb->select($fb->column('id', $resTable), $fb->column('sku_id', $resTable),
				$fb->alias($fb->getDocumentColumn('code', $skuTable), 'sku_code'),
				$fb->column('reservation', $resTable), $fb->column('store_id', $resTable));
			$qb->from($resTable)
				->innerJoin($skuTable, $fb->eq($fb->column('sku_id', $resTable), $fb->getDocumentColumn('id', $skuTable)));
			$qb->where($fb->eq($fb->column('target', $resTable), $fb->parameter('targetIdentifier')));
		}
		$query = $qb->query();
		$query->bindParameter('targetIdentifier', $targetIdentifier);
		$rows = $query->getResults($query->getRowsConverter()->addIntCol('id', 'store_id', 'sku_id')
			->addNumCol('reservation')->addStrCol('sku_code'));
		if (count($rows))
		{
			return array_map(function (array $row)
			{
				return (new \Rbs\Stock\Std\Reservation($row['id']))
					->setCodeSku($row['sku_code'])
					->setQuantity($row['reservation'])
					->setSkuId($row['sku_id'])
					->setWebStoreId($row['store_id']);
			}, $rows);
		}
		return array();
	}

	/**
	 * Remove not confirmed reservation for target identifier
	 * @api
	 * @param string $targetIdentifier
	 * @throws \Exception
	 */
	public function cleanupReservations($targetIdentifier)
	{
		$transactionManager = $this->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$qb = $this->getDbProvider()->getNewStatementBuilder('stock::cleanupReservations');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->delete($fb->table('rbs_stock_dat_res'));
				$qb->where(
					$fb->logicAnd(
						$fb->eq($fb->column('target'), $fb->parameter('targetIdentifier')),
						$fb->eq($fb->column('confirmed'), $fb->booleanParameter('confirmed'))
					)
				);
			}
			$statement = $qb->deleteQuery();
			$statement->bindParameter('targetIdentifier', $targetIdentifier);
			$statement->bindParameter('confirmed', false);
			$statement->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * Remove all reservation for target identifier
	 * @api
	 * @param string $targetIdentifier
	 * @throws \Exception
	 */
	public function unsetReservations($targetIdentifier)
	{
		$transactionManager = $this->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$qb = $this->getDbProvider()->getNewStatementBuilder('stock::unsetReservations');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->delete($fb->table('rbs_stock_dat_res'));
				$qb->where($fb->eq($fb->column('target'), $fb->parameter('targetIdentifier')));
			}
			$statement = $qb->deleteQuery();
			$statement->bindParameter('targetIdentifier', $targetIdentifier);
			$statement->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @api
	 * @param string $targetIdentifier
	 * @param string $confirmedTargetIdentifier
	 * @throws \Exception
	 */
	public function confirmReservations($targetIdentifier, $confirmedTargetIdentifier = null)
	{
		$transactionManager = $this->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$qb = $this->getDbProvider()->getNewStatementBuilder('stock::confirmReservations');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->update($fb->table('rbs_stock_dat_res'));
				$qb->assign($fb->column('confirmed'), $fb->booleanParameter('confirmed'));
				$qb->assign($fb->column('target'), $fb->parameter('confirmedTargetIdentifier'));
				$qb->where($fb->eq($fb->column('target'), $fb->parameter('targetIdentifier')));
			}
			$statement = $qb->updateQuery();
			$statement->bindParameter('confirmed', true);
			$statement->bindParameter('confirmedTargetIdentifier', $confirmedTargetIdentifier ? $confirmedTargetIdentifier : $targetIdentifier);
			$statement->bindParameter('targetIdentifier', $targetIdentifier);
			$statement->execute();
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param $targetIdentifier
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param $quantity
	 * @throws \Exception
	 */
	public function decrementReservation($targetIdentifier, $sku, $quantity)
	{
		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : $sku;
		$transactionManager = $this->getTransactionManager();
		try
		{
			$transactionManager->begin();

			//get the current reservation
			$qb = $this->getDbProvider()->getNewQueryBuilder('stock::decrementReservationSelect');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->column('id'), $fb->column('sku_id'), $fb->column('reservation'));
				$qb->from($fb->table('rbs_stock_dat_res'));
				$qb->where($fb->logicAnd(
						$fb->eq($fb->column('target'), $fb->parameter('targetIdentifier')),
						$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId')))
				);
			}
			$query = $qb->query();
			$query->bindParameter('targetIdentifier', $targetIdentifier);
			$query->bindParameter('skuId', $skuId);


			$row = $query->getFirstResult($query->getRowsConverter()->addIntCol('id', 'sku_id')
				->addNumCol('reservation'));

			//if we found a reservation, we'll decrement his quantity
			if ($row)
			{
				$newReservation = $row['reservation'] - $quantity;
				//we cannot have a reservation under 0.
				if ($newReservation < 0)
				{
					$newReservation = 0;
				}

				$qb = $this->getDbProvider()->getNewStatementBuilder('stock::decrementReservationUpdate');
				if (!$qb->isCached())
				{
					$fb = $qb->getFragmentBuilder();
					$qb->update($fb->table('rbs_stock_dat_res'));
					$qb->assign($fb->column('reservation'), $fb->integerParameter('reservation'));
					$qb->assign($fb->column('date'), $fb->dateTimeParameter('date'));
					$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('reservationId')));
				}
				$statement = $qb->updateQuery();
				$statement->bindParameter('reservation', $newReservation);
				$statement->bindParameter('date', new \DateTime());
				$statement->bindParameter('reservationId', $row['id']);
				$statement->execute();
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $store
	 * @param integer|null $limit
	 * @param integer|null $offset
	 * @param string|null $orderCol
	 * @param string|null $orderSort
	 * @return array
	 */
	public function getReservationsBySku($sku, $store = null, $limit= null, $offset = null, $orderCol = null, $orderSort = null)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('reservation'), $fb->column('store_id'), $fb->column('date'), $fb->column('target'),
			$fb->column('confirmed')); //
		$qb->from($fb->table('rbs_stock_dat_res'));
		$logicAnd = $fb->logicAnd(
			$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
		);
		if ($store !== null)
		{
			$logicAnd->addArgument($fb->eq($fb->column('store_id'), $fb->integerParameter('storeId')));
		}
		$qb->where($logicAnd);

		if ($orderSort !== null && $orderCol !== null)
		{
			if ($orderSort === 'desc')
			{
				$qb->orderDesc($fb->column($orderCol));
			}
			else
			{
				$qb->orderAsc($fb->column($orderCol));
			}
		}

		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		if ($store !== null)
		{
			$storeId = $store instanceof \Rbs\Store\Documents\WebStore ? $store->getId() : intval($store);
			$query->bindParameter('warehouseId', $storeId);
		}

		if ($limit !== null && $offset !== null)
		{
			$query->setMaxResults($limit);
			$query->setStartIndex($offset);
		}

		return $queryResult = $query->getResults();
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $store
	 * @return integer
	 */
	public function countReservationsBySku($sku, $store = null)
	{
		$key = 'stock::countReservationsBySku';
		if ($store !== null)
		{
			$key = 'stock::countReservationsBySkuAndStore';
		}

		$qb = $this->getDbProvider()->getNewQueryBuilder($key);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->func('count', '*'), 'rowCount'));
			$qb->from($fb->table('rbs_stock_dat_res'));
			$logicAnd = $fb->logicAnd(
				$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
			);
			if ($store !== null)
			{
				$logicAnd->addArgument($fb->eq($fb->column('store_id'), $fb->integerParameter('storeId')));
			}
			$qb->where($logicAnd);
		}
		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		if ($store !== null)
		{
			$storeId = $store instanceof \Rbs\Store\Documents\WebStore ? $store->getId() : intval($store);
			$query->bindParameter('warehouseId', $storeId);
		}

		$count = $query->getFirstResult($query->getRowsConverter()->addIntCol('rowCount')->singleColumn('rowCount'));

		return $count;
	}

	protected $skuIds = array();

	/**
	 * @param string $code
	 * @return \Rbs\Stock\Documents\Sku|null
	 */
	public function getSkuByCode($code)
	{
		if (!is_string($code))
		{
			return null;
		}
		if (array_key_exists($code, $this->skuIds))
		{
			$skuId = $this->skuIds[$code];
			if (is_int($skuId))
			{
				return $this->getDocumentManager()->getDocumentInstance($skuId);
			}
			return null;
		}

		$query = $this->getDocumentManager()->getNewQuery('Rbs_Stock_Sku');
		$query->andPredicates($query->eq('code', $code));
		$sku = $query->getFirstDocument();
		if ($sku)
		{
			$this->skuIds[$code] =  $sku->getId();
		}
		return $sku;
	}

	/**
	 * @param string $targetIdentifier
	 * @return integer|null
	 */
	public function getTargetIdFromTargetIdentifier($targetIdentifier)
	{
		$split = explode(':', $targetIdentifier);

		if (count($split) == 2 && is_numeric($split[1]))
		{
			return $split[1];
		}

		return null;
	}
}