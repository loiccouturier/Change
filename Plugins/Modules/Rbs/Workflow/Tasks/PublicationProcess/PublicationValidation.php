<?php
namespace Rbs\Workflow\Tasks\PublicationProcess;

use Change\Documents\Interfaces\Publishable;
use Change\Workflow\Interfaces\WorkItem;
use Zend\EventManager\Event;

/**
* @name \Rbs\Workflow\Tasks\PublicationProcess\PublicationValidation
*/
class PublicationValidation
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		/* @var $workItem WorkItem */
		$workItem = $event->getParam('workItem');
		$document = $workItem->getWorkflowInstance()->getDocument();
		if ($document instanceof Publishable)
		{
			if ($document->getPublicationStatus() === Publishable::STATUS_VALIDCONTENT)
			{
				$documentServices = $document->getDocumentServices();
				$transactionManager = $documentServices->getApplicationServices()->getTransactionManager();
				try
				{
					$transactionManager->begin();
					$document->updatePublicationStatus(Publishable::STATUS_VALID);
					$transactionManager->commit();
				}
				catch (\Exception $e)
				{
					throw $transactionManager->rollBack($e);
				}
			}
		}
	}
}