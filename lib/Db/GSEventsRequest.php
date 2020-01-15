<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Db;


use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\GlobalScale\GSWrapper;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharesToken;


/**
 * Class TokensRequest
 *
 * @package OCA\Circles\Db
 */
class GSEventsRequest extends GSEventsRequestBuilder {


	use TStringTools;


	/**
	 * @param GSEvent $event
	 * @param array $instances
	 *
	 * @return GSWrapper
	 */
	public function create(GSevent $event): GSWrapper {
		$wrapper = new GSWrapper();
		$wrapper->setToken($this->uuid());
		$wrapper->setEvent($event);
		$wrapper->setCreation(time());

		$qb = $this->getGSEventsInsertSql();
		$qb->setValue('token', $qb->createNamedParameter($wrapper->getToken()))
		   ->setValue('event', $qb->createNamedParameter(json_encode($wrapper->getEvent())))
		   ->setValue('creation', $qb->createNamedParameter($wrapper->getCreation()));

		$qb->execute();

		return $wrapper;
	}



	/**
	 * @param string $token
	 *
	 * @return GSWrapper
	 * @throws TokenDoesNotExistException
	 * @throws JsonException
	 * @throws ModelException
	 */
	public function getByToken(string $token): GSWrapper {
		$qb = $this->getGSEventsSelectSql();
		$this->limitToToken($qb, $token);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();
		if ($data === false) {
			throw new TokenDoesNotExistException('Unknown share token');
		}

		return $this->parseGSEventsSelectSql($data);
	}


}

