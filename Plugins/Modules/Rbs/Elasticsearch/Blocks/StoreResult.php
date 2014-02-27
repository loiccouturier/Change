<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Elasticsearch\Blocks\StoreResult
 */
class StoreResult extends Block
{

	protected $validSortBy = ['title.asc', 'price.asc', 'price.desc', 'price.desc', 'dateAdded.desc'];

	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('facetFilters', null);
		$parameters->addParameterMeta('storeIndex');
		$parameters->addParameterMeta('contextualUrls', true);
		$parameters->addParameterMeta('webStoreId');

		$parameters->addParameterMeta('itemsPerLine', 3);
		$parameters->addParameterMeta('itemsPerPage', 9);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('showOrdering', true);
		$parameters->addParameterMeta('sortBy', null);

		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');

		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('redirectUrl');

		$parameters->setNoCache();

		$parameters->setLayoutParameters($event->getBlockLayout());

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('pageNumber',
			intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));

		if ($parameters->getParameter('showOrdering'))
		{
			$sortBy = $request->getQuery('sortBy');
			if ($sortBy && in_array($sortBy, $this->validSortBy))
			{
				$parameters->setParameterValue('sortBy', $sortBy);
			}
		}

		$queryFilters = $request->getQuery('facetFilters', null);
		$facetFilters = array();
		if (is_array($queryFilters))
		{
			foreach ($queryFilters as $fieldName => $rawValue)
			{
				if (is_string($fieldName) && $rawValue)
				{
					$facetFilters[$fieldName] = $rawValue;
				}
			}
		}
		$parameters->setParameterValue('facetFilters', $facetFilters);

		if (!$parameters->getParameter('redirectUrl'))
		{
			$urlManager = $event->getUrlManager();
			$oldValue = $urlManager->getAbsoluteUrl();
			$urlManager->setAbsoluteUrl(true);
			$uri = $urlManager->getByFunction('Rbs_Commerce_Cart');
			if ($uri)
			{
				$parameters->setParameterValue('redirectUrl', $uri->normalize()->toString());
			}
			$urlManager->setAbsoluteUrl($oldValue);
		}

		if ($parameters->getParameter('displayPrices') === null)
		{
			$parameters->setParameterValue('displayPrices', false);
			$parameters->setParameterValue('displayPricesWithTax', false);

			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$storeIndex = $documentManager->getDocumentInstance($parameters->getParameter('storeIndex'));
			if ($storeIndex instanceof \Rbs\Elasticsearch\Documents\StoreIndex)
			{
				$webStore = $storeIndex->getStore();
				if ($webStore)
				{
					$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
					$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
				}
			}
		}

		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Rbs\Catalog\Documents\ProductList && $document->activated())
		{
			return true;
		}
		return false;
	}

	/**
	 * @param Event $event
	 * @return \Rbs\Generic\GenericServices | null
	 */
	protected function getGenericServices($event)
	{
		$genericServices = $event->getServices('genericServices');
		if (!($genericServices instanceof \Rbs\Generic\GenericServices))
		{
			return null;
		}
		return $genericServices;
	}

	/**
	 * @param Event $event
	 * @return \Rbs\Commerce\CommerceServices
	 */
	protected function getCommerceServices($event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			return null;
		}
		return $commerceServices;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();

		$commerceServices = $this->getCommerceServices($event);
		$genericServices = $this->getGenericServices($event);
		if ($commerceServices == null || $genericServices == null)
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': commerceServices or genericServices not defined');
			return null;
		}

		$parameters = $event->getBlockParameters();
		$productListId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$productList = null;
		if ($productListId !== null)
		{
			/** @var $productList \Rbs\Catalog\Documents\ProductList|null */
			$productList = $documentManager->getDocumentInstance($productListId);
			if (!($productList instanceof \Rbs\Catalog\Documents\ProductList) || !$productList->activated())
			{
				$applicationServices->getLogging()->warn(__METHOD__ . ': invalid product list');
				return null;
			}
		}

		$facetFilters = $parameters->getParameter('facetFilters');
		$storeIndex = $documentManager->getDocumentInstance($parameters->getParameter('storeIndex'));
		if (!($storeIndex instanceof \Rbs\Elasticsearch\Documents\StoreIndex))
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': invalid store index');
			return null;
		}

		$parameters->setParameterValue('webStoreId', $storeIndex->getStoreId());

		$client = $genericServices->getIndexManager()->getClient($storeIndex->getClientName());
		if (!$client)
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': invalid client ' . $storeIndex->getClientName());
			return null;
		}

		$index = $client->getIndex($storeIndex->getName());
		if (!$index->exists())
		{
			$applicationServices->getLogging()->warn(__METHOD__ . ': index not exist ' . $storeIndex->getName());
			return null;
		}

		$searchQuery = new \Rbs\Elasticsearch\Index\SearchQuery($storeIndex);
		$searchQuery->setFacetManager($genericServices->getFacetManager());
		$searchQuery->setI18nManager($applicationServices->getI18nManager());
		$searchQuery->setCollectionManager($applicationServices->getCollectionManager());

		$attributes['pageNumber'] = $pageNumber = intval($parameters->getParameter('pageNumber'));
		$size = $parameters->getParameter('itemsPerPage');
		$from = ($pageNumber - 1) * $size;
		$query = $searchQuery->getListSearchQuery($productList, $facetFilters, $from, $size, []);
		$sortArgs = $this->getSortArgs($parameters->getParameter('sortBy'), $productList, $commerceServices->getContext());
		if ($sortArgs)
		{
			$query->setSort($sortArgs);
		}

		$searchResult = $index->getType($storeIndex->getDefaultTypeName())->search($query);
		$attributes['totalCount'] = $totalCount = $searchResult->getTotalHits();

		$rows = array();
		if ($totalCount)
		{
			/* @var $page \Change\Presentation\Interfaces\Page */
			$page = $event->getParam('page');
			$section = $page->getSection();

			$itemsPerPage = $parameters->getParameter('itemsPerPage');
			$pageCount = ceil($totalCount / $itemsPerPage);
			$pageNumber = $this->fixPageNumber($parameters->getParameter('pageNumber'), $pageCount);

			$attributes['pageNumber'] = $pageNumber;
			$attributes['totalCount'] = $totalCount;
			$attributes['pageCount'] = $pageCount;

			$webStoreId = $storeIndex->getStoreId();
			$contextualUrls = $parameters->getParameter('contextualUrls');

			/* @var $result \Elastica\Result */
			foreach ($searchResult->getResults() as $result)
			{
				$product = $documentManager->getDocumentInstance($result->getId());
				if (!($product instanceof \Rbs\Catalog\Documents\Product) || !$product->published())
				{
					continue;
				}
				if ($contextualUrls)
				{
					$url = $event->getUrlManager()->getByDocument($product, $section)->toString();
				}
				else
				{
					$url = $event->getUrlManager()->getCanonicalByDocument($product)->toString();
				}

				$row = array('id' => $product->getId(), 'url' => $url);
				$visual = $product->getFirstVisual();
				$row['visual'] = $visual ? $visual->getPath() : null;

				$productPresentation = $product->getPresentation($commerceServices, $webStoreId, $event->getUrlManager());
				if ($productPresentation)
				{
					$productPresentation->evaluate();
					$row['productPresentation'] = $productPresentation;
				}
				$rows[] = (new \Rbs\Catalog\Product\ProductItem($row))->setDocumentManager($documentManager);
			}
		}
		$attributes['rows'] = $rows;

		$attributes['itemsPerLine'] = $parameters->getParameter('itemsPerLine');
		return 'store-result.twig';
	}

	/**
	 * @param string $sortBy
	 * @param \Rbs\Catalog\Documents\ProductList|null $productList
	 * @param \Rbs\Commerce\Std\Context $context
	 * @return array | null
	 */
	protected function getSortArgs($sortBy, $productList, $context)
	{
		$sort = [];
		if ($productList && $sortBy == null)
		{
			$sort['position'] = ['order' => 'asc', 'nested_path' => 'listItems', 'nested_filter' => ['term'=>['listId' => $productList->getId()]]];
			$sortBy = $productList->getProductSortOrder() . '.' . $productList->getProductSortDirection();
		}

		if ($sortBy)
		{
			list($sortName, $sortDir) = explode('.', $sortBy);
			if ($sortName && ($sortDir == 'asc' || $sortDir == 'desc'))
			{
				switch ($sortName) {
					case 'title' :
						$sort['title.untouched'] = ['order' => $sortDir];
						break;
					case 'dateAdded' :
						if ($productList)
						{
							$sort['creationDate'] = ['order' => $sortDir, 'nested_path' => 'listItems', 'nested_filter' => ['term'=>['listId' => $productList->getId()]]];
						}
						else
						{
							$sort['creationDate'] = ['order' => $sortDir];
						}
						break;
					case 'price' :
						$ba = $context->getBillingArea();
						if ($ba)
						{
							$baId = $ba->getId();
							$zone = $context->getZone();
							$now = (new \DateTime())->format(\DateTime::ISO8601);
							$sortKey = $zone ? 'valueWithTax' : 'value';
							$bool = new \Elastica\Filter\Bool();
							$bool->addMust(new \Elastica\Filter\Term(['billingAreaId' => $baId]));
							$bool->addMust(new \Elastica\Filter\Term(['zone' => $zone ? $zone : '']));
							$bool->addMust(new \Elastica\Filter\Range('startActivation', array('lte' => $now)));
							$bool->addMust(new \Elastica\Filter\Range('endActivation', array('gt' => $now)));
							$sort[$sortKey] = ['order' => $sortDir, 'nested_path' => 'prices', 'nested_filter' => $bool->toArray()];
						}
						break;
				}
			}
		}

		return  (count($sort)) ? $sort : null;
	}
}