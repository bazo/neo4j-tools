<?php

namespace Bazo\Neo4jTools;

use Everyman\Neo4j\Query\ResultSet;



/**
 * @author Martin Bažík <martin@bazo.sk>
 */
class Neo4jRowIterator implements \Iterator
{

	/** @var ResultSet */
	protected $resultSet;


	function __construct(ResultSet $resultSet)
	{
		$this->resultSet = $resultSet;
	}


	public function current()
	{
		$current = $this->resultSet->current();
		return $current->current();
	}


	public function key()
	{
		return $this->resultSet->key();
	}


	public function next()
	{
		$this->resultSet->next();
	}


	public function rewind()
	{
		$this->resultSet->rewind();
	}


	public function valid()
	{
		return $this->resultSet->valid();
	}


}
