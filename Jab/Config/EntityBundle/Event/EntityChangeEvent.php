<?php
namespace Jab\Config\EntityBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Jab\Config\EntityBundle\Tools\Info\EntityInfo;

/**
 * Class EntityChangeEvent
 */
class EntityChangeEvent extends Event{
	/**
	 * @var EntityInfo - the entity that has been changed
	 */
	protected $entityInfo;

	/**
	 * Constructor
	 * @param EntityInfo $entityInfo
	 */
	public function __construct(EntityInfo $entityInfo) {
		$this->entityInfo = $entityInfo;
	}

	/**
	 * @return EntityInfo
	 */
	public function getEntityInfo() {
		return($this->entityInfo);
	}

}