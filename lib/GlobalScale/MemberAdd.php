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


use daita\MySmallPhpTools\Model\SimpleDataStore;
use OC\User\NoUserException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Exceptions\EmailAccountInvalidFormatException;
use OCA\Circles\Exceptions\GlobalScaleDSyncException;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberCantJoinCircleException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MembersLimitException;
use OCA\Circles\Model\GlobalScale\GSEvent;


/**
 * Class MemberAdd
 *
 * @package OCA\Circles\GlobalScale
 */
class MemberAdd extends AGlobalScaleEvent {


	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 *
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws EmailAccountInvalidFormatException
	 * @throws GlobalScaleDSyncException
	 * @throws GlobalScaleEventException
	 * @throws MemberAlreadyExistsException
	 * @throws MemberCantJoinCircleException
	 * @throws MembersLimitException
	 * @throws NoUserException
	 * @throws CircleTypeNotValidException
	 * @throws MemberIsNotModeratorException
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
		parent::verify($event, $localCheck, true);

		$eventMember = $event->getMember();
		$this->cleanMember($eventMember);

		$ident = $eventMember->getUserId();
		$this->membersService->verifyIdentBasedOnItsType(
			$ident, $eventMember->getType(), $eventMember->getInstance()
		);

		$circle = $event->getCircle();
		$circle->getHigherViewer()
			   ->hasToBeModerator();

		$member = $this->membersRequest->getFreshNewMember(
			$circle->getUniqueId(), $ident, $eventMember->getType(), $eventMember->getInstance()
		);
		$member->hasToBeInviteAble();

		$this->circlesService->checkThatCircleIsNotFull($circle);

		$this->membersService->addMemberBasedOnItsType($circle, $member);

		$event->setMember($member);

	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws MemberAlreadyExistsException
	 */
	public function manage(GSEvent $event): void {
		$circle = $event->getCircle();
		$member = $event->getMember();
		if ($member->getJoined() === '') {
			$this->membersRequest->createMember($member);
		} else {
			$this->membersRequest->updateMember($member);
		}

		$event->setResult(new SimpleDataStore(['oui' => 'ok']));
		$this->eventsService->onMemberNew($circle, $member);
	}


	/**
	 * @param GSEvent[] $events
	 */
	public function result(array $events): void {
	}

}

