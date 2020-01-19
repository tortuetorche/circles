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


namespace OCA\Circles\Cron;


use OC\BackgroundJob\TimedJob;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\GSUpstreamService;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\QueryException;


/**
 * Class GlobalSync
 *
 * @package OCA\Cicles\Cron
 */
class GlobalSync extends TimedJob {


	/** @var MembersRequest */
	private $membersRequest;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var GSUpstreamService */
	private $gsUpstreamService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * Cache constructor.
	 */
	public function __construct() {
		$this->setInterval(1);
	}


	/**
	 * @param mixed $argument
	 *
	 * @throws QueryException
	 */
	protected function run($argument) {
		$app = new Application();
		$c = $app->getContainer();

		$this->circlesRequest = $c->query(CirclesRequest::class);
		$this->membersRequest = $c->query(MembersRequest::class);
		$this->gsUpstreamService = $c->query(GSUpstreamService::class);
		$this->configService = $c->query(ConfigService::class);
		$this->miscService = $c->query(MiscService::class);

		try {
			if (!$this->configService->getGSStatus(ConfigService::GS_ENABLED)) {
				return;
			}
		} catch (GSStatusException $e) {
			return;
		}

		$this->syncCircles();
		$this->removeDeprecatedCircles();

		$this->syncEvents();
		$this->removeDeprecatedEvents();
	}


	private function syncCircles() {
		$circles = $this->circlesRequest->forceGetCircles();
		$sync = [];
		foreach ($circles as $circle) {
			if ($circle->getOwner()
					   ->getInstance() !== ''
				|| $circle->getType() === Circle::CIRCLES_PERSONAL) {
				continue;
			}

			$members = $this->membersRequest->forceGetMembers($circle->getUniqueId());
			$circle->setMembers($members);

			$sync[] = $circle;
		}

		try {
			$this->gsUpstreamService->syncCircles($sync);
		} catch (GSStatusException $e) {
		}
	}


	/**
	 *
	 */
	private function removeDeprecatedCircles(): void {
		$knownCircles = $this->circlesRequest->forceGetCircles();

		foreach ($knownCircles as $knownItem) {
			if ($knownItem->getOwner()
						  ->getInstance() === '') {
				continue;
			}

			try {
				$this->checkCircle($knownItem);
			} catch (GSStatusException $e) {
			}
		}
	}


	/**
	 * @param Circle $circle
	 *
	 * @throws GSStatusException
	 */
	private function checkCircle(Circle $circle): void {
		$status = $this->gsUpstreamService->confirmCircleStatus($circle);

		if (!$status) {
			$this->circlesRequest->destroyCircle($circle->getUniqueId());
			$this->membersRequest->removeAllFromCircle($circle->getUniqueId());
		}
	}


	/**
	 *
	 */
	private function syncEvents(): void {

	}

	private function removeDeprecatedEvents(): void {
		$this->gsUpstreamService->deprecatedEvents();
	}

}

