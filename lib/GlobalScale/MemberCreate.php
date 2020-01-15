<?php declare(strict_types=1);


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


namespace OCA\Circles\GlobalScale;


use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Exceptions\GlobalScaleDSyncException;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Model\GlobalScale\GSEvent;


/**
 * Class MemberCreate
 *
 * @package OCA\Circles\GlobalScale
 */
class MemberCreate extends AGlobalScaleEvent {


	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 *
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws MemberIsNotModeratorException
	 * @throws GlobalScaleDSyncException
	 * @throws GlobalScaleEventException
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
		parent::verify($event, $localCheck, true);

		$circle = $event->getCircle();
//		$eventCircle = $event->getCircle();
//		$viewer = $eventCircle->getViewer();
//		$eventViewer = $viewer->getUserId();
//		$eventInstance = $viewer->getInstance();
//
//		$circle =
//			$this->circlesRequest->getCircle($eventCircle->getUniqueId(), $eventViewer, $eventInstance);
		$circle->getHigherViewer()
			   ->hasToBeModerator();
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws MemberAlreadyExistsException
	 */
	public function manage(GSEvent $event): void {
		$this->miscService->log('new event; ' . json_encode($event));
		if (!$event->hasCircle() || !$event->hasMember()) {
			return;
		}

		$member = $event->getMember();
		$this->miscService->log('new event; ' . json_encode($member));

//		if ($member->getInstance() === '') {
//			$member->setInstance($event->getSource());
//		}

		$this->membersRequest->createMember($member);
		$this->eventsService->onMemberNew($event->getCircle(), $member);
	}

}

