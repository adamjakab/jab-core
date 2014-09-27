<?php
namespace Jab\Config\EntityBundle\Annotation;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
/**
 *
 * All Jab manageable entities must declare this annotation by
 * @JAB\Entity() or @JAB\Entity(managedEntity=true,...)
 * where @JAB stands for: 'use Jab\Config\EntityBundle\Annotation as JAB;'
 *
 * @Annotation
 * @Target("CLASS")
 */
class Entity {

	/**
	 * SYSTEM - All application supplied entities (they are not editable by user (fields can be modified if not in $readOnlyFields))
	 * CUSTOM - All entities created by user inside application
	 */
	const _valid_types = '["SYSTEM", "CUSTOM"]';

	/**
	 * You can force an entity not to be managed by Jab by:
	 *  1) removing '@JAB\Entity' notation or
	 *  2) setting '@JAB\Entity(managedEntity=false, ...)'
	 * @var bool
	 */
	private $managedEntity = true;

	/**
	 * @var string
	 */
	private $type = "CUSTOM";

	/**
	 * Fields which cannot be modified usually on SYSTEM type entities
	 * @var array
	 */
	private $readOnlyFields = [];



	public function __construct($options) {
		if (isset($options['value'])) {
			unset($options['value']);
		}

		foreach ($options as $key => $value) {
			if (!property_exists($this, $key)) {
				throw new \InvalidArgumentException(sprintf(__CLASS__ . ' property "%s" does not exist', $key));
			}

			if($key == "type" && !in_array($value, json_decode(self::_valid_types))) {
				throw new \InvalidArgumentException(sprintf(__CLASS__ . ' property "%s" must be one of: (%s).', $key, implode("|", json_decode(self::_valid_types))));
			}


			$this->$key = $value;
		}
	}

	/**
	 * @return boolean
	 */
	public function isManagedEntity() {
		return $this->managedEntity;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return array
	 */
	public function getReadOnlyFields() {
		return $this->readOnlyFields;
	}
}