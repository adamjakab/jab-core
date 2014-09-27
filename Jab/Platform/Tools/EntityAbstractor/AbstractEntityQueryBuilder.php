<?php
namespace Jab\Platform\Tools\EntityAbstractor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class AbstractEntityQueryBuilder extends QueryBuilder {

	/**
	 * @var KernelInterface
	 */
	private $kernel;

	/** @var string */
	protected $entity_name;

	/** @var string */
	protected $entity_alias;

	/** @var array */
	protected $fields = [];

	/** @var string */
	protected $order_field = NULL;

	/** @var string */
	protected $order_type = "asc";

	/** @var string */
	protected $where = NULL;

	/** @var array */
	protected $joins = [];

	/** @var boolean */
	protected $has_action = true;

	/** @var array */
	protected $fixed_data = [];

	/** @var \closure */
	protected $renderer = NULL;

	/** @var boolean */
	protected $search = false;

	/** @var int */
	private $recordsOffset = 0;

	/** @var int  */
	private $recordsMax = 0;

	/**
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em) {
		parent::__construct($em);
	}

	/**
	 * convert object to array
	 * @param object $object
	 * @return array
	 */
	protected function _toArray($object) {
		$reflectionClass = new \ReflectionClass(get_class($object));
		$array = array();
		foreach ($reflectionClass->getProperties() as $property) {
			$property->setAccessible(true);
			$array[$property->getName()] = $property->getValue($object);
			$property->setAccessible(false);
		}
		return $array;
	}

	/**
	 * add join
	 *
	 * @param string $join_field
	 * @param string $alias
	 * @param string $type
	 * @param string $cond
	 *
	 * @return AbstractEntityQueryBuilder
	 */
	public function addJoin($join_field, $alias, $type, $cond = '') {
		if ($cond != '') {
			$cond = " with {$cond} ";
		}
		if ($type == "inner") {
			$this->innerJoin($join_field, $alias, null, $cond);
		} else {
			$this->leftJoin($join_field, $alias, null, $cond);
		}
		$this->joins[] = array($join_field, $alias, $type, $cond);
		return $this;
	}

	/**
	 * get total records
	 *
	 * @return integer
	 */
	public function getTotalRecords() {
		$qb = clone $this;
		$qb->resetDQLPart('orderBy');
		$gb = $qb->getDQLPart('groupBy');
		//if (empty($gb) || !in_array($this->fields['_identifier_'], $gb)) {
		if (empty($gb) || !in_array("id", $gb)) {
			//$qb->select(" count({$this->fields['_identifier_']}) ");
			$qb->select(" count(".$this->entity_alias.") ");
			return $qb->getQuery()->getSingleScalarResult();
		} else {
			$qb->resetDQLPart('groupBy');
			//$qb->select(" count(distinct {$this->fields['_identifier_']}) ");
			$qb->select(" count(distinct ".$this->entity_alias.") ");//-----------------------hmmmmmmmm
			return $qb->getQuery()->getSingleScalarResult();
		}
	}

	/**
	 * get data
	 * @return array
	 */
	public function getData() {
		$hydration_mode = Query::HYDRATE_OBJECT;
		$qb = clone $this;

		//always select entity alias and all joins
		$select = [$this->entity_alias];
		foreach ($this->joins as $join) {
			$select[] = $join[1];
		}
		$qb->setFields($select);

		//
		// the query
		$query = $qb->getQuery();
		// apply limiting
		if ($this->recordsMax > 0) {
			$query->setMaxResults($this->recordsMax)->setFirstResult($this->recordsOffset);
		}
		$objects = $query->getResult($hydration_mode);
		return($objects);
	}




	/**
	 * get entity name
	 *
	 * @return string
	 */
	public function getEntityName() {
		return $this->entity_name;
	}

	/**
	 * get entity alias
	 *
	 * @return string
	 */
	public function getEntityAlias() {
		return $this->entity_alias;
	}

	/**
	 * get fields
	 *
	 * @return array
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * get order field
	 *
	 * @return string
	 */
	public function getOrderField() {
		return $this->order_field;
	}

	/**
	 * get order type
	 *
	 * @return string
	 */
	public function getOrderType() {
		return $this->order_type;
	}

	/**
	 * set entity
	 *
	 * @param string $entity_name
	 * @param string $entity_alias
	 *
	 * @return AbstractEntityQueryBuilder
	 */
	public function setEntity($entity_name, $entity_alias) {
		$this->entity_name = $entity_name;
		$this->entity_alias = $entity_alias;
		$this->from($entity_name, $entity_alias);
		return $this;
	}

	/**
	 * will be used only privately by getData
	 *
	 * @param array $fields
	 *
	 * @return AbstractEntityQueryBuilder
	 */
	private function setFields(array $fields) {
		$this->fields = $fields;
		$selectFields = [];
		$prefix = $this->entity_alias . '.';
		foreach($this->fields as $fld) {
			if($fld != $this->entity_alias) {
				$fld = (substr($fld,0,strlen($prefix))!=$prefix ? $prefix.$fld : $fld);
			}
			$selectFields[] = $fld;
		}
		$this->select(implode(', ', $selectFields));
		return $this;
	}

	/**
	 * set order
	 *
	 * @param string $order_field
	 * @param string $order_type
	 *
	 * @return AbstractEntityQueryBuilder
	 */
	public function setOrder($order_field, $order_type) {
		$this->order_field = $order_field;
		$this->order_type = $order_type;
		$this->orderBy($order_field, $order_type);
		return $this;
	}

	/**
	 * set fixed data
	 *
	 * @param string $data
	 *
	 * @return AbstractEntityQueryBuilder
	 */
	public function setFixedData($data) {
		$this->fixed_data = $data;
		return $this;
	}

	/**
	 * set query where
	 * @param string $where
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $type
	 * @return $this
	 */
	public function setWhere($where, $key, $value, $type = null) {
		$this->where($where);
		//$this->setParameters($params);
		$this->setParameter($key, $value, $type);
		return $this;
	}

	/**
	 * set query group
	 *
	 * @param string $group
	 *
	 * @return $this
	 */
	public function setGroupBy($group) {
		$this->groupBy($group);
		return $this;
	}

	/**
	 * set search
	 *
	 * @param bool $search
	 *
	 * @return AbstractEntityQueryBuilder
	 */
	public function setSearch($search) {
		$this->search = $search;
		return $this;
	}

	/**
	 * @param int $recordsOffset
	 */
	public function setRecordsOffset($recordsOffset) {
		$this->recordsOffset = $recordsOffset;
	}

	/**
	 * @return int
	 */
	public function getRecordsOffset() {
		return $this->recordsOffset;
	}

	/**
	 * @param int $recordsMax
	 */
	public function setRecordsMax($recordsMax) {
		$this->recordsMax = $recordsMax;
	}

	/**
	 * @return int
	 */
	public function getRecordsMax() {
		return $this->recordsMax;
	}



}
