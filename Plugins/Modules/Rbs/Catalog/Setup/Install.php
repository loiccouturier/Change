<?php
namespace Rbs\Catalog\Setup;

/**
 * @name \Rbs\Generic\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$schema = new Schema($applicationServices->getDbProvider()->getSchemaManager());
		$schema->generate();
		$applicationServices->getDbProvider()->closeConnection();

		$applicationServices->getThemeManager()->installPluginTemplates($plugin);

		$this->installGenericAttributes($applicationServices);

		//Add CrossSelling Type collection
		$cm = $applicationServices->getCollectionManager();
		if ($cm->getCollection('Rbs_Catalog_Collection_CrossSellingType') === null)
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();

				$i18n = $applicationServices->getI18nManager();

				/* @var $collection \Rbs\Collection\Documents\Collection */
				$collection = $applicationServices->getDocumentManager()
					->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$collection->setLabel('Cross Selling Types');
				$collection->setCode('Rbs_Catalog_Collection_CrossSellingType');

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('ACCESSORIES');
				$item->setLabel($i18n->trans('m.rbs.catalog.setup.attr-cross-selling-accessories', array('ucf')));
				$item->getCurrentLocalization()->setTitle($i18n->trans('m.rbs.catalog.setup.attr-cross-selling-accessories',
					array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('SIMILAR');
				$item->setLabel($i18n->trans('m.rbs.catalog.setup.attr-cross-selling-similar', array('ucf')));
				$item->getCurrentLocalization()->setTitle($i18n->trans('m.rbs.catalog.setup.attr-cross-selling-similar',
					array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('HIGHERRANGE');
				$item->setLabel($i18n->trans('m.rbs.catalog.setup.attr-cross-selling-higher-range', array('ucf')));
				$item->getCurrentLocalization()->setTitle($i18n->trans('m.rbs.catalog.setup.attr-cross-selling-higher-range',
					array('ucf')));
				$item->save();
				$collection->getItems()->add($item);
				$collection->setLocked(true);
				$collection->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function installGenericAttributes($applicationServices)
	{
		$i18nManager = $applicationServices->getI18nManager();
		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$attributes = array();
			$attributesData = \Zend\Json\Json::decode(file_get_contents(__DIR__ . '/Assets/attributes.json'),
				\Zend\Json\Json::TYPE_ARRAY);
			foreach ($attributesData as $key => $attributeData)
			{
				/** @var $attribute \Rbs\Catalog\Documents\Attribute */
				$label = $i18nManager->trans($attributeData['title']);
				$attributeData['title'] = $label;
				$query = new \Change\Documents\Query\Query('Rbs_Catalog_Attribute', $applicationServices->getDocumentManager(), $applicationServices->getModelManager());
				$query->andPredicates($query->eq('label', $label));
				$attribute = $query->getFirstDocument();
				if ($attribute === null)
				{
					$attribute = $applicationServices->getDocumentManager()
						->getNewDocumentInstanceByModelName('Rbs_Catalog_Attribute');
					$attribute->setLabel($label);
				}
				foreach ($attributeData as $propertyName => $value)
				{
					switch ($propertyName)
					{
						case 'attributes':
							foreach ($value as $attrKey)
							{
								$attribute->getAttributes()->add($attributes[$attrKey]);
							}
							break;
						default:
							$attribute->getDocumentModel()->setPropertyValue($attribute, $propertyName, $value);
							break;
					}
				}
				$attribute->save();
				$attributes[$key] = $attribute;
			}
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}