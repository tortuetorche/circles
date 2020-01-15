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


use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;


/**
 * Class GSEvent
 *
 * @package OCA\Circles\Model\GlobalScale
 */
class GSEvent implements JsonSerializable {


	const GLOBAL_SYNC = 'GlobalScale\GlobalSync';
	const CIRCLE_STATUS = 'GlobalScale\CircleStatus';

	const CIRCLE_CREATE = 'GlobalScale\CircleCreate';
	const CIRCLE_UPDATE = 'GlobalScale\CircleUpdate';
	const CIRCLE_DELETE = 'GlobalScale\CircleDelete';
	const MEMBER_CREATE = 'GlobalScale\MemberCreate'; // used ?
	const MEMBER_ADD = 'GlobalScale\MemberAdd';
	const MEMBER_JOIN = 'GlobalScale\MemberJoin';
	const MEMBER_INVITE = 'GlobalScale\MemberInvite';
	const MEMBER_LEAVE = 'GlobalScale\MemberLeave';
	const MEMBER_LEVEL = 'GlobalScale\MemberLevel';
	const MEMBER_UPDATE = 'GlobalScale\MemberUpdate';
	const MEMBER_REMOVE = 'GlobalScale\MemberRemove';


	use TArrayTools;


	/** @var string */
	private $type = '';

	/** @var string */
	private $source = '';

	/** @var Circle */
	private $circle;

	/** @var Member */
	private $member;

	private $data;

	/** @var string */
	private $key = '';

	/** @var bool */
	private $local = false;


	/**
	 * GSEvent constructor.
	 *
	 * @param string $type
	 * @param bool $local
	 */
	function __construct(string $type = '', bool $local = false) {
		$this->type = $type;
		$this->local = $local;
		$this->data = new SimpleDataStore();
	}


	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @param mixed $type
	 *
	 * @return GSEvent
	 */
	public function setType($type): self {
		$this->type = $type;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSource(): string {
		return $this->source;
	}

	/**
	 * @param string $source
	 *
	 * @return GSEvent
	 */
	public function setSource(string $source): self {
		$this->source = $source;

		if ($this->hasMember() && $this->member->getInstance() === '') {
			$this->member->setInstance($source);
		}

		if ($this->hasCircle()
			&& $this->getCircle()
					->hasViewer()
			&& $this->getCircle()
					->getViewer()
					->getInstance() === '') {
			$this->getCircle()
				 ->getViewer()
				 ->setInstance($source);
		}

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isLocal(): bool {
		return $this->local;
	}

	/**
	 * @param bool $local
	 *
	 * @return GSEvent
	 */
	public function setLocal(bool $local): self {
		$this->local = $local;

		return $this;
	}


	/**
	 * @return Circle
	 */
	public function getCircle(): Circle {
		return $this->circle;
	}

	/**
	 * @param Circle $circle
	 *
	 * @return GSEvent
	 */
	public function setCircle(Circle $circle): self {
		$this->circle = $circle;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasCircle(): bool {
		return ($this->circle !== null);
	}


	/**
	 * @return Member
	 */
	public function getMember(): Member {
		return $this->member;
	}

	/**
	 * @param Member $member
	 *
	 * @return GSEvent
	 */
	public function setMember(Member $member): self {
		$this->member = $member;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasMember(): bool {
		return ($this->member !== null);
	}


	/**
	 * @param SimpleDataStore $data
	 *
	 * @return GSEvent
	 */
	public function setData(SimpleDataStore $data): self {
		$this->data = $data;

		return $this;
	}

	/**
	 * @return SimpleDataStore
	 */
	public function getData(): SimpleDataStore {
		return $this->data;
	}


	/**
	 * @return string
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @param string $key
	 *
	 * @return GSEvent
	 */
	public function setKey(string $key): self {
		$this->key = $key;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isValid(): bool {
		if ($this->getType() === '') {
			return false;
		}

		return true;
	}


	/**
	 * @param string $json
	 *
	 * @return GSEvent
	 * @throws JsonException
	 * @throws ModelException
	 */
	public function importFromJson(string $json): self {
		$data = json_decode($json, true);

		if (!is_array($data)) {
			throw new JsonException('invalid JSON');
		}

		return $this->import($data);
	}


	/**
	 * @param array $data
	 *
	 * @return GSEvent
	 * @throws ModelException
	 */
	public function import(array $data): self {
		$this->setType($this->get('type', $data));
		$this->setKey($this->get('key', $data));
		$this->setSource($this->get('source', $data));
		$this->setData(new SimpleDataStore($this->getArray('data', $data)));

		if (array_key_exists('circle', $data)) {
			$this->setCircle(Circle::fromArray($data['circle']));
		}

		if (array_key_exists('member', $data)) {
			$this->setMember(Member::fromArray($data['member']));
		}

		if (!$this->isValid()) {
			throw new ModelException('invalid GSEvent');
		}

		return $this;
	}


	/**
	 * @return array
	 */
	function jsonSerialize(): array {
		$arr = [
			'type'   => $this->getType(),
			'key'    => $this->getKey(),
			'data'   => $this->getData(),
			'source' => $this->getSource()
		];

		if ($this->hasCircle()) {
			$arr['circle'] = $this->getCircle();
		}
		if ($this->hasMember()) {
			$arr['member'] = $this->getMember();
		}

		$this->cleanArray($arr);

		return $arr;
	}


}

