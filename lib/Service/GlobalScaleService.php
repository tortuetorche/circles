<?php declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019
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
use daita\MySmallPhpTools\Exceptions\RequestResultSizeException;
use daita\MySmallPhpTools\Exceptions\RequestServerException;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Traits\TRequest;
use OC;
use OCA\Circles\Db\GSEventsRequest;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Exceptions\GSKeyException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\GlobalScale\AGlobalScaleEvent;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCP\AppFramework\QueryException;
use OCP\IURLGenerator;


/**
 * Class GlobalScaleService
 *
 * @package OCA\Circles\Service
 */
class GlobalScaleService {


	use TRequest;


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var GSEventsRequest */
	private $gsEventsRequest;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * GlobalScaleService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param GSEventsRequest $gsEventsRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		GSEventsRequest $gsEventsRequest,
		ConfigService $configService,
		MiscService $miscService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->gsEventsRequest = $gsEventsRequest;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws GSStatusException
	 */
	public function asyncBroadcast(GSEvent $event): void {
		if (!$this->configService->getGSStatus(ConfigService::GS_ENABLED)) {
			return;
		}

		$wrapper = $this->gsEventsRequest->create($event);
		$path = $this->urlGenerator->linkToRoute(
			'circles.GlobalScale.asyncBroadcast', ['token' => $wrapper->getToken()]
		);

		$request = new Request($path, Request::TYPE_PUT);
		$request->setAddressFromUrl($this->urlGenerator->getBaseUrl());
		$request->setDataSerialize($event);

		try {
			$this->doRequest($request);
		} catch (RequestContentException | RequestNetworkException | RequestResultSizeException | RequestServerException $e) {
		}
	}


	/**
	 * @param GSEvent $event
	 *
	 * @return AGlobalScaleEvent
	 * @throws GlobalScaleEventException
	 */
	public function getGlobalScaleEvent(GSEvent $event): AGlobalScaleEvent {
		$class = '\OCA\Circles\\' . $event->getType();
		try {
			$gs = OC::$server->query($class);
			if (!$gs instanceof AGlobalScaleEvent) {
				throw new GlobalScaleEventException($class . ' not an AGlobalScaleEvent');
			}

			return $gs;
		} catch (QueryException $e) {
			throw new GlobalScaleEventException('AGlobalScaleEvent ' . $class . ' not found');
		}
	}


	/**
	 * @return string
	 */
	public function getKey(): string {
		// TODO: sign event with real and temp key.
		return 'abcd';
	}


	/**
	 * @param string $key
	 *
	 * @throws GSKeyException
	 */
	public function checkKey(string $key) {
		if ($key !== $this->getKey()) {
			throw new GSKeyException('invalid key');
		}
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws GSKeyException
	 */
	public function checkEvent(GSEvent $event): void {
		$this->checkKey($event->getKey());
	}

}

