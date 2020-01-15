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


namespace OCA\Circles\Model\GlobalScale;


use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;


/**
 * Class GSEvent
 *
 * @package OCA\Circles\Model\GlobalScale
 */
class GSWrapper implements JsonSerializable {


	use TArrayTools;


	/** @var string */
	private $token = '';

	/** @var GSEvent */
	private $event;

	/** @var int */
	private $creation;


	function __construct() {
	}


	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * @param string $token
	 *
	 * @return GSWrapper
	 */
	public function setToken(string $token): self {
		$this->token = $token;

		return $this;
	}


	/**
	 * @return GSEvent
	 */
	public function getEvent(): GSEvent {
		return $this->event;
	}

	/**
	 * @param GSEvent $event
	 *
	 * @return GSWrapper
	 */
	public function setEvent(GSEvent $event): self {
		$this->event = $event;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasEvent(): bool {
		return ($this->event !== null);
	}


	/**
	 * @return int
	 */
	public function getCreation(): int {
		return $this->creation;
	}

	/**
	 * @param int $creation
	 *
	 * @return GSWrapper
	 */
	public function setCreation(int $creation): self {
		$this->creation = $creation;

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return GSWrapper
	 * @throws JsonException
	 * @throws ModelException
	 */
	public function import(array $data): self {
		$this->setToken($this->get('id', $data));

		$event = new GSEvent();
		$event->importFromJson($this->get('event', $data));

		$this->setEvent($event);

		$this->setCreation($this->getInt('creation', $data));

		return $this;
	}


	/**
	 * @return array
	 */
	function jsonSerialize(): array {
		$arr = [
			'id'       => $this->getToken(),
			'event'    => $this->getEvent(),
			'creation' => $this->getCreation()
		];

		$this->cleanArray($arr);

		return $arr;
	}

}

