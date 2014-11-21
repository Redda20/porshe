<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\MetaDataReader;

abstract class Relation {

	protected $from;
	protected $to;
	protected $name;
	protected $options;

	protected $from_key;
	protected $to_key;
	protected $key_tuple;

	protected $from_primary_key;
	protected $to_primary_key;

	protected $datastore;

	public function __construct(MetaDataReader $from, MetaDataReader $to, $name, $options)
	{
		$this->from = $from;
		$this->to = $to;
		$this->name = $name;

		$this->processOptions($options);
	}

	/**
	 *
	 */
	abstract public function createAssociation(Model $source);

	/**
	 *
	 */
	abstract public function linkIds(Model $source, Model $target);

	/**
	 *
	 */
	abstract public function unlinkIds(Model $source, Model $target);

	/**
	 *
	 */
	abstract protected function deriveKeys();


	/**
	 * TODO figure this out
	 */
	//	abstract public function getInverse();

	/**
	 *
	 */
	public function modifyEagerQuery($query, $from_alias, $to_alias)
	{
		list($from, $to) = $this->getKeys();

		$query->where(
			"{$to_alias}_{$this->to_table}.{$to}",
			"{$from_alias}_{$this->from_table}.{$from}",
			FALSE
		);
	}

	/**
	 *
	 */
	public function modifyLazyQuery($query, $source, $to_alias)
	{
		die('lazy?');
		list($from, $to) = $this->getKeys();

		$query->where("{$to_alias}_{$this->to_table}.{$to}", $source->$from);
	}

	/**
	 *
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 *
	 */
	public function getSourceModel()
	{
		return $this->from->getName();
	}

	/**
	 *
	 */
	public function getTargetModel()
	{
		return $this->to->getName();
	}

	/**
	 *
	 */
	public function setDataStore($datastore)
	{
		$this->datastore = $datastore;
	}

	/**
	 *
	 */
	public function getKeys()
	{
		return $this->key_tuple;
	}

	/**
	 *
	 */
	protected function processOptions($options)
	{
		if (isset($options['from_key']))
		{
			$this->from_key = $options['from_key'];
		}

		if (isset($options['to_key']))
		{
			$this->to_key = $options['to_key'];
		}

		$this->from_primary_key = $options['from_primary_key'];
		$this->to_primary_key = $options['to_primary_key'];

		$this->key_tuple = $this->deriveKeys();
		list($from, $to) = $this->key_tuple;

		$this->from_table = $this->from->getTableForField($from);
		$this->to_table = $this->to->getTableForField($to);
	}
}