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
use OCA\Circles\Model\GlobalScale\GSShare;


/**
 * Class GSSharesRequest
 *
 * @package OCA\Circles\Db
 */
class GSSharesRequest extends GSSharesRequestBuilder {


	use TStringTools;


	/**
	 * @param GSShare $gsShare
	 */
	public function create(GSShare $gsShare): void {
		$hash = $this->token();
		$qb = $this->getGSSharesInsertSql();
		$qb->setValue('circle_unique_id', $qb->createNamedParameter($gsShare->getCircleId()))
		   ->setValue('owner', $qb->createNamedParameter($gsShare->getOwner()))
		   ->setValue('instance', $qb->createNamedParameter($gsShare->getInstance()))
		   ->setValue('token', $qb->createNamedParameter($gsShare->getToken()))
		   ->setValue('parent', $qb->createNamedParameter($gsShare->getParent()))
		   ->setValue('mountpoint', $qb->createNamedParameter($gsShare->getMountPoint()))
		   ->setValue('mountpoint_hash', $qb->createNamedParameter($hash));
		$qb->execute();
	}


	/**
	 * @param string $userId
	 *
	 * @return GSShare[]
	 */
	public function getForUser(string $userId): array {
		$qb = $this->getGSSharesSelectSql();

		$shares = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$shares[] = $this->parseGSSharesSelectSql($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

}

