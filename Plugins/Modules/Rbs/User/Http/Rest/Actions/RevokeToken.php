<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Rest\Actions;

use Change\Http\Rest\V1\ArrayResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\User\Http\Rest\Actions\RevokeToken
 */
class RevokeToken
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		if ($event->getRequest()->getPost('token'))
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$token = $event->getRequest()->getPost('token');
				$qb = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();

				$qb->update($fb->table($qb->getSqlMapping()->getOAuthTable()))
					->assign($fb->column('validity_date'), $fb->dateTimeParameter('validity_date'))
					->where($fb->eq($fb->column('token'), $fb->parameter('token')));
				$uq = $qb->updateQuery();

				$now = new \DateTime();
				$uq->bindParameter('validity_date', $now);
				$uq->bindParameter('token', $token);

				$uq->execute();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}

		$result = new ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$event->setResult($result);
	}
}