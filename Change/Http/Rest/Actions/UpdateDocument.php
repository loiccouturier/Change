<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\PropertyConverter;

/**
 * @name \Change\Http\Rest\Actions\UpdateDocument
 */
class UpdateDocument
{

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function getDocument($event)
	{
		$modelName = $event->getParam('modelName');
		$model = ($modelName) ? $event->getDocumentServices()->getModelManager()->getModelByName($modelName) : null;
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}

		$documentId = intval($event->getParam('documentId'));
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId, $model);
		if (!$document)
		{
			throw new \RuntimeException('Invalid Parameter: documentId', 71000);
		}
		return $document;
	}


	/**
	 * Use Event Params: documentId, modelName
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$document = $this->getDocument($event);
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$event->setParam('LCID', $document->getRefLCID());
			$updateLocalizedDocument = new UpdateLocalizedDocument();
			$updateLocalizedDocument->execute($event);
			return;
		}

		$properties = $event->getRequest()->getPost()->toArray();
		$this->update($event, $document, $properties);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $properties
	 * @throws \Exception
	 */
	protected function update($event, $document, $properties)
	{
		$urlManager = $event->getUrlManager();
		foreach ($document->getDocumentModel()->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if (array_key_exists($name, $properties))
			{
				try
				{
					$c = new PropertyConverter($document, $property, $urlManager);
					$c->setPropertyValue($properties[$name]);
				}
				catch (\Exception $e)
				{
					$errorResult = new \Change\Http\Rest\Result\ErrorResult('INVALID-VALUE-TYPE', 'Invalid property value type', HttpResponse::STATUS_CODE_409);
					$errorResult->setData(array('name' => $name, 'value' => $properties[$name], 'type' => $property->getType()));
					$errorResult->addDataValue('document-type', $property->getDocumentType());
					$event->setResult($errorResult);
					return;
				}
			}
		}

		try
		{
			$document->update();
		}
		catch (\Exception $e)
		{
			$code = $e->getCode();
			if ($code && $code >= 52000 && $code < 53000)
			{
				$i18nManager = $event->getApplicationServices()->getI18nManager();
				$errorResult = new \Change\Http\Rest\Result\ErrorResult('VALIDATION-ERROR', 'Document properties validation error', HttpResponse::STATUS_CODE_409);
				if (count($errors = $document->getPropertiesErrors()) > 0)
				{
					$pe = array();
					foreach ($errors as $propertyName => $errorsMsg)
					{
						foreach ($errorsMsg as $errorMsg)
						{
							$pe[$propertyName][] = $i18nManager->trans($errorMsg);
						}
					}
					$errorResult->addDataValue('properties-errors', $pe);
				}
				$event->setResult($errorResult);
				return;
			}
			throw $e;
		}

		$getDocument = new GetDocument();
		$getDocument->execute($event);
		$result = $event->getResult();
		if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		}
	}
}