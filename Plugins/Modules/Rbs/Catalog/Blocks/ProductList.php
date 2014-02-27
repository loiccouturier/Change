<?php
namespace Rbs\Catalog\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\ProductList
 */
class ProductList extends Block
{
	/**
	 * @var array
	 */
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
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('useCurrentSectionProductList');
		$parameters->addParameterMeta('conditionId');
		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('billingAreaId');
		$parameters->addParameterMeta('zone');
		$parameters->addParameterMeta('contextualUrls', true);
		$parameters->addParameterMeta('itemsPerLine', 3);
		$parameters->addParameterMeta('itemsPerPage', 9);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('showOrdering', true);
		$parameters->addParameterMeta('sortBy', null);

		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');

		$parameters->addParameterMeta('redirectUrl');
		$parameters->setLayoutParameters($event->getBlockLayout());

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('pageNumber', intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->setParameterValueForDetailBlock($parameters, $event);

		if ($parameters->getParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME) == null && $parameters->getParameter('useCurrentSectionProductList') === true)
		{
			/* @var $page \Change\Presentation\Interfaces\Page */
			$page = $event->getParam('page');
			$section = $page->getSection();

			$catalogManager = $commerceServices->getCatalogManager();
			$defaultProductList = $catalogManager->getDefaultProductListBySection($section);
			if ($this->isValidDocument($defaultProductList))
			{
				$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, $defaultProductList->getId());
			}
		}

		if ($parameters->getParameter('showOrdering'))
		{
			$sortBy = $request->getQuery('sortBy');
			if ($sortBy && in_array($sortBy, $this->validSortBy))
			{
				$request->getQuery()->set('sortBy', $sortBy);
				$parameters->setParameterValue('sortBy', $sortBy);
			}
		}

		$webStore = $commerceServices->getContext()->getWebStore();
		if ($webStore)
		{
			$parameters->setParameterValue('webStoreId', $webStore->getId());
			if ($parameters->getParameter('displayPrices') === null)
			{
				$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
				$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
			}

			$billingArea = $commerceServices->getContext()->getBillingArea();
			if ($billingArea)
			{
				$parameters->setParameterValue('billingAreaId', $billingArea->getId());
			}

			$zone = $commerceServices->getContext()->getZone();
			if ($zone)
			{
				$parameters->setParameterValue('zone', $zone);
			}
		}
		else
		{
			$parameters->setParameterValue('webStoreId', 0);
			$parameters->setParameterValue('billingAreaId', 0);
			$parameters->setParameterValue('zone', null);
			$parameters->setParameterValue('displayPrices', false);
			$parameters->setParameterValue('displayPricesWithTax', false);
		}

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
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$productListId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($productListId)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $productList \Rbs\Catalog\Documents\ProductList */
			$productList = $documentManager->getDocumentInstance($productListId);
			if (!($productList instanceof \Rbs\Catalog\Documents\ProductList) ||
				!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
			{
				return null;
			}

			$attributes['productList'] = $productList;

			$conditionId = $parameters->getParameter('conditionId');
			$query = $documentManager->getNewQuery('Rbs_Catalog_Product', $documentManager->getLCID());
			$query->andPredicates($query->published());

			$subQuery = $query->getModelBuilder('Rbs_Catalog_ProductListItem', 'product');
			$subQuery->andPredicates(
				$subQuery->eq('productList', $productListId),
				$subQuery->eq('condition', $conditionId ? $conditionId : 0),
				$subQuery->activated()
			);

			$this->addSort($parameters->getParameter('sortBy'), $productList, $query, $subQuery, $commerceServices->getContext());

			$rows = array();
			$totalCount = $query->getCountDocuments();
			if ($totalCount)
			{
				$itemsPerPage = $parameters->getParameter('itemsPerPage');
				$pageCount = ceil($totalCount / $itemsPerPage);
				$pageNumber = $this->fixPageNumber($parameters->getParameter('pageNumber'), $pageCount);

				$attributes['pageNumber'] = $pageNumber;
				$attributes['totalCount'] = $totalCount;
				$attributes['pageCount'] = $pageCount;

				/* @var $page \Change\Presentation\Interfaces\Page */
				$page = $event->getParam('page');
				$section = $page->getSection();

				$webStoreId = $parameters->getParameter('webStoreId');
				$contextualUrls = $parameters->getParameter('contextualUrls');

				/* @var $product \Rbs\Catalog\Documents\Product */
				foreach ($query->getDocuments(($pageNumber-1)*$itemsPerPage, $itemsPerPage) as $product)
				{
					if ($contextualUrls)
					{
						$url = $event->getUrlManager()->getByDocument($product, $section)->toString();
					}
					else
					{
						$url = $event->getUrlManager()->getCanonicalByDocument($product)->toString();
					}

					$row = array('id' => $product->getId(), 'url' => $url);

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
			return 'product-list.twig';
		}
		return null;
	}

	/**
	 * @param string|null $sortBy
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param \Change\Documents\Query\Query $query
	 * @param \Change\Documents\Query\ChildBuilder $subQuery
	 * @param \Rbs\Commerce\Std\Context $context
	 */
	protected function addSort($sortBy, $productList, $query, $subQuery, $context)
	{
		if ($sortBy == null)
		{
			$subQuery->addOrder('position', true);
			$sortBy = $productList->getProductSortOrder() . '.' . $productList->getProductSortDirection();
		}

		if ($sortBy)
		{
			list($sortName, $sortDir) = explode('.', $sortBy);
			if ($sortName && ($sortDir == 'asc' || $sortDir == 'desc'))
			{
				switch ($sortName) {
					case 'title' :
						$query->addOrder('title', $sortDir == 'asc');
						break;
					case 'dateAdded' :
						$subQuery->addOrder('creationDate', $sortDir == 'asc');
						break;
					case 'price' :
						$ba = $context->getBillingArea();
						$webStore = $context->getWebStore();
						if ($ba && $webStore)
						{
							$priceQuery = $query->getPropertyModelBuilder('sku', 'Rbs_Price_Price', 'sku');
							$priceQuery->andPredicates($priceQuery->activated(), $priceQuery->eq('billingArea', $ba),
								$priceQuery->eq('webStore', $webStore), $priceQuery->eq('targetId', 0)
							);
							$priceQuery->addOrder('value', $sortDir == 'asc');
						}
						break;
				}
			}
		}
	}
}