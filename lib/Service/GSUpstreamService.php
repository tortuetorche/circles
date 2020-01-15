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


namespace OCA\Circles\Service;


use daita\MySmallPhpTools\Exceptions\RequestContentException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\RequestResultNotJsonException;
use daita\MySmallPhpTools\Exceptions\RequestResultSizeException;
use daita\MySmallPhpTools\Exceptions\RequestServerException;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TRequest;
use Exception;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\GSEventsRequest;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\GlobalScale\CircleStatus;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\GlobalScale\GSWrapper;
use OCP\IURLGenerator;


/**
 * Class GSUpstreamService
 *
 * @package OCA\Circles\Service
 */
class GSUpstreamService {


	use TRequest;
	use TArrayTools;


	/** @var string */
	private $userId = '';

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var GSEventsRequest */
	private $gsEventsRequest;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var GlobalScaleService */
	private $globalScaleService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * GSUpstreamService constructor.
	 *
	 * @param $userId
	 * @param IURLGenerator $urlGenerator
	 * @param GSEventsRequest $gsEventsRequest
	 * @param CirclesRequest $circlesRequest
	 * @param GlobalScaleService $globalScaleService
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IURLGenerator $urlGenerator,
		GSEventsRequest $gsEventsRequest,
		CirclesRequest $circlesRequest,
		GlobalScaleService $globalScaleService,
		ConfigService $configService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->urlGenerator = $urlGenerator;
		$this->gsEventsRequest = $gsEventsRequest;
		$this->circlesRequest = $circlesRequest;
		$this->globalScaleService = $globalScaleService;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws Exception
	 */
	public function newEvent(GSEvent $event) {
		try {
			$gs = $this->globalScaleService->getGlobalScaleEvent($event);
//		if (!$this->configService->getGSStatus(ConfigService::GS_ENABLED)) {
//			return true;
//		}

			$this->fillEvent($event);
			if ($this->isLocalEvent($event)) {
				$gs->verify($event, true);
				$gs->manage($event);

				$this->globalScaleService->asyncBroadcast($event);
			} else {
				$gs->verify($event);
				$this->confirmEvent($event);
				$gs->manage($event);
			}
		} catch (Exception $e) {
			$this->miscService->log(
				get_class($e) . ' on new event: ' . $e->getMessage() . ' - ' . json_encode($event), 1
			);
			throw $e;
		}
	}


	/**
	 * @param string $protocol
	 * @param GSEvent $event
	 *
	 * @throws GSStatusException
	 */
	public function broadcastEvent(GSEvent $event, string $protocol = ''): void {
		$this->signEvent($event);

		$path = $this->urlGenerator->linkToRoute('circles.GlobalScale.broadcast');
		$request = new Request($path, Request::TYPE_POST);

		if ($protocol === '') {
			// TODO: test https first, then http
			$protocol = 'http';
		}
		$request->setProtocol($protocol);
		$request->setDataSerialize($event);

		foreach ($this->getInstances() as $instance) {
			$request->setAddress($instance);

			try {
				$this->doRequest($request);
			} catch
			(RequestContentException | RequestNetworkException | RequestResultSizeException | RequestServerException $e) {
				// TODO: queue request
			}
		}

	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws RequestContentException
	 * @throws RequestNetworkException
	 * @throws RequestResultSizeException
	 * @throws RequestServerException
	 * @throws RequestResultNotJsonException
	 * @throws GlobalScaleEventException
	 */
	public function confirmEvent(GSEvent $event): void {
		$this->signEvent($event);

		$circle = $event->getCircle();
		$owner = $circle->getOwner();
		$path = $this->urlGenerator->linkToRoute('circles.GlobalScale.event');

		$request = new Request($path, Request::TYPE_POST);
		$request->setProtocol($_SERVER['REQUEST_SCHEME']);
		$request->setAddressFromUrl($owner->getInstance());
		$request->setDataSerialize($event);

		$result = $this->retrieveJson($request);
		$this->miscService->log('result ' . json_encode($result));
		if ($this->getInt('status', $result) === 0) {
			throw new GlobalScaleEventException($this->get('error', $result));
		}
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws GSStatusException
	 */
	private function fillEvent(GSEvent $event): void {
		if (!$this->configService->getGSStatus(ConfigService::GS_ENABLED)) {
			return;
		}

		$event->setSource($this->configService->getLocalCloudId());
	}

	/**
	 * @param bool $all
	 *
	 * @return array
	 * @throws GSStatusException
	 */
	public function getInstances(bool $all = false): array {
		/** @var string $lookup */
		$lookup = $this->configService->getGSStatus(ConfigService::GS_LOOKUP);

		$request = new Request('/instances', Request::TYPE_GET);
		$request->setAddressFromUrl($lookup);

		try {
			$instances = $this->retrieveJson($request);
		} catch (
		RequestContentException |
		RequestNetworkException |
		RequestResultSizeException |
		RequestServerException |
		RequestResultNotJsonException $e
		) {
			$this->miscService->log('Issue while retrieving instances from lookup: ' . $e->getMessage());

			return [];
		}

		if ($all) {
			return $instances;
		}

		return array_diff($instances, $this->configService->getTrustedDomains());
	}


	/**
	 * @param GSEvent $event
	 */
	private function signEvent(GSevent $event) {
		$event->setKey($this->globalScaleService->getKey());
	}


	/**
	 * @param GSEvent $event
	 *asyncBroadcast
	 *
	 * @return bool
	 */
	private function isLocalEvent(GSEvent $event): bool {
		if ($event->isLocal()) {
			return true;
		}

		$circle = $event->getCircle();
		$owner = $circle->getOwner();
		if ($owner->getInstance() === ''
			|| in_array(
				$owner->getInstance(), $this->configService->getTrustedDomains()
			)) {
			return true;
		}

		return false;
	}


	/**
	 * @param string $token
	 *
	 * @return GSWrapper
	 * @throws JsonException
	 * @throws ModelException
	 * @throws TokenDoesNotExistException
	 */
	public function getEventByToken(string $token): GSWrapper {
		return $this->gsEventsRequest->getByToken($token);
	}


	/**
	 * @param array $circles
	 *
	 * @throws GSStatusException
	 */
	public function syncCircles(array $circles): void {
		$event = new GSEvent(GSEvent::GLOBAL_SYNC, true);
		$event->setSource($this->configService->getLocalCloudId());
		$event->setData(new SimpleDataStore($circles));

		$this->broadcastEvent($event);
	}


	/**
	 * @param Circle $circle
	 *
	 * @return bool
	 * @throws GSStatusException
	 */
	public function confirmCircleStatus(Circle $circle): bool {
		$event = new GSEvent(GSEvent::CIRCLE_STATUS, true);
		$event->setSource($this->configService->getLocalCloudId());
		$event->setCircle($circle);

		$this->signEvent($event);

		$path = $this->urlGenerator->linkToRoute('circles.GlobalScale.status');
		$request = new Request($path, Request::TYPE_POST);

		// TODO: test https first, then http
		$protocol = 'http';
		$request->setProtocol($protocol);
		$request->setDataSerialize($event);

		$requestIssue = false;
		$notFound = false;
		$foundWithNoOwner = false;
		foreach ($this->getInstances() as $instance) {
			$request->setAddress($instance);

			try {
				$result = $this->retrieveJson($request);
				$this->miscService->log('result: ' . json_encode($result));
				if ($this->getInt('status', $result, 0) !== 1) {
					throw new RequestContentException('result status is not good');
				}

				$status = $this->getInt('success.data.status', $result);

				// if error, we assume the circle might still exist.
				if ($status === CircleStatus::STATUS_ERROR) {
					return true;
				}

				if ($status === CircleStatus::STATUS_OK) {
					return true;
				}

				// TODO: check the data.supposedOwner entry.
				if ($status === CircleStatus::STATUS_NOT_OWNER) {
					$foundWithNoOwner = true;
				}

				if ($status === CircleStatus::STATUS_NOT_FOUND) {
					$notFound = true;
				}

			} catch (RequestContentException
			| RequestNetworkException
			| RequestResultNotJsonException
			| RequestResultSizeException
			| RequestServerException $e) {
				$requestIssue = true;
				// TODO: log instances that have network issue, after too many tries (7d), remove this circle.
				continue;
			}
		}

		// if no request issue, we can imagine that the instance that owns the circle is down.
		// We'll wait for more information (cf request exceptions management);
		if ($requestIssue) {
			return true;
		}

		// circle were not found in any other instances, we can easily says that the circle does not exists anymore
		if ($notFound && !$foundWithNoOwner) {
			return false;
		}

		// circle were found everywhere but with no owner on every instance. we need to assign a new owner.
		// This should be done by checking admin rights. if no admin rights, let's assume that circle should be removed.
		if (!$notFound && $foundWithNoOwner) {
			// TODO: assign a new owner and check that when changing owner, we do check that the destination instance is updated FOR SURE!
			 return true;
		}

		// some instances returned notFound, some returned circle with no owner. let's assume the circle is deprecated.
		return false;
	}

}

