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


namespace OCA\Circles\Controller;


use daita\MySmallPhpTools\Traits\TAsync;
use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCP\AppFramework\Http\DataResponse;


/**
 * Class GlobalScaleController
 *
 * @package OCA\Circles\Controller
 */
class GlobalScaleController extends BaseController {


	use TStringTools;
	use TAsync;


	/**
	 * Event is generated by any instance of GS and sent to the instance that owns the Circles, that
	 * will broadcast the event to other if ok
	 *
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function event(): DataResponse {
		$data = file_get_contents('php://input');

		try {
			$event = new GSevent();
			$event->importFromJson($data);
			$this->gsDownstreamService->requestedEvent($event);

			return $this->success(['success' => $event]);
		} catch (Exception $e) {
			return $this->fail(['data' => $data, 'error' => $e->getMessage()]);
		}
	}


	/**
	 * Async process and broadcast the event to every instances of GS
	 * This should be initiated by the instance that owns the Circles.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 *
	 * @throws Exception
	 */
	public function asyncBroadcast(string $token) {
		try {
			$wrappers = $this->gsUpstreamService->getEventsByToken($token);
		} catch (Exception $e) {
			$this->miscService->log(
				'exception during async: ' . ['token' => $token, 'error' => $e->getMessage()]
			);
			$this->fail(['token' => $token, 'error' => $e->getMessage()]);
		}

		$this->async();
		foreach ($wrappers as $wrapper) {
			try {
				$this->gsUpstreamService->broadcastWrapper($wrapper, $this->request->getServerProtocol());
			} catch (GSStatusException $e) {
			}
		}

		$this->gsUpstreamService->manageResults($token);

		exit();
	}


	/**
	 * Event is sent by instance that owns the Circles.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $data
	 *
	 * @return DataResponse
	 */
	public function broadcast(): DataResponse {
		$data = file_get_contents('php://input');

		try {
			$event = new GSevent();
			$event->importFromJson($data);

			$this->gsDownstreamService->onNewEvent($event);

			return $this->success(['result' => $event->getResult()]);
		} catch (Exception $e) {
			return $this->fail(['data' => $data, 'error' => $e->getMessage()]);
		}
	}


	/**
	 * Status Event. This is an event to check status of items between instances.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function status(): DataResponse {
		$data = file_get_contents('php://input');

		try {
			$event = new GSevent();
			$event->importFromJson($data);
			$this->gsDownstreamService->statusEvent($event);

			return $this->success(['success' => $event]);
		} catch (Exception $e) {
			return $this->fail(['data' => $data, 'error' => $e->getMessage()]);
		}
	}

}

