<?php

namespace Bazo\Neo4jTools;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Query\ResultSet;



/**
 * @author Martin Bažík <martin@bazo.sk>
 */
class CypherQueryBuilder
{

	/** @var Client */
	private $client;

	/** @var array */
	private $start = [];

	/** @var array */
	private $match = [];

	/** @var array */
	private $delete = [];

	/** @var array */
	private $return = [];

	/** @var array */
	private $where = [];

	/** @var array */
	private $order = [];

	/** @var int */
	private $skip;

	/** @var int */
	private $limit;

	/** @var CypherParameterProcessor */
	private $processor;


	public function __construct(Client $client)
	{
		$this->client = $client;
		$this->processor = new CypherParameterProcessor;
	}


	/**
	 * @param string $string
	 * @return CypherQueryBuilder
	 */
	public function start($string)
	{
		$this->start = array_merge($this->start, func_get_args());
		return $this;
	}


	/**
	 * @param string $name
	 * @param mixed $nodes
	 * @return CypherQueryBuilder
	 */
	public function startWithNode($name, $nodes)
	{
		if (!is_array($nodes)) {
			$nodes = array($nodes);
		}

		$parts = [];
		foreach ($nodes as $key => $node) {
			$fullKey = $name . '_' . $key;

			$parts[] = ":$fullKey";
			$this->set($fullKey, $node);
		}

		$parts = implode(', ', $parts);
		$this->start("$name = node($parts)");

		return $this;
	}


	/**
	 * @param string $name
	 * @param string $index
	 * @param string $query
	 * @return CypherQueryBuilder
	 */
	public function startWithQuery($name, $index, $query)
	{
		$this->start("$name = node:`$index`('$query')");

		return $this;
	}


	/**
	 * @param string $name
	 * @param string $index
	 * @param string $key
	 * @param mixed $value
	 * @return CypherQueryBuilder
	 */
	public function startWithLookup($name, $index, $key, $value)
	{
		$this->start("$name = node:`$index`($key = :{$name}_{$key})");
		$this->set("{$name}_{$key}", $value);

		return $this;
	}


	/**
	 * @param string $string
	 * @return CypherQueryBuilder
	 */
	public function match($string)
	{
		$this->match = array_merge($this->match, func_get_args());
		return $this;
	}


	/**
	 * @param string $string
	 * @return CypherQueryBuilder
	 */
	public function end($string)
	{
		$this->return = array_merge($this->return, func_get_args());
		return $this;
	}


	/**
	 * @param string $string
	 * @return CypherQueryBuilder
	 */
	public function where($string)
	{
		$this->where = array_merge($this->where, func_get_args());
		return $this;
	}


	/**
	 * @param string $string
	 * @return CypherQueryBuilder
	 */
	public function delete($string)
	{
		$this->delete = array_merge($this->delete, func_get_args());
		return $this;
	}


	/**
	 * @param string $string
	 * @return CypherQueryBuilder
	 */
	public function order($string)
	{
		$this->order = array_merge($this->order, func_get_args());
		return $this;
	}


	/**
	 * @param int $skip
	 * @return CypherQueryBuilder
	 */
	public function skip($skip)
	{
		$this->skip = (int) $skip;
		return $this;
	}


	/**
	 * @param int $limit
	 * @return CypherQueryBuilder
	 */
	public function limit($limit)
	{
		$this->limit = (int) $limit;
		return $this;
	}


	/**
	 * @param string $name
	 * @param mixed $value
	 * @return CypherQueryBuilder
	 */
	public function set($name, $value)
	{
		$this->processor->setParameter($name, $value);

		return $this;
	}


	/**
	 * @return string
	 */
	private function createCypherString()
	{
		$cypher = '';

		if ($this->start) {
			$cypher .= 'START ' . implode(', ', $this->start) . PHP_EOL;
		}

		if (count($this->match)) {
			$cypher .= 'MATCH ' . implode(', ', $this->match) . PHP_EOL;
		}

		if (count($this->where)) {
			$cypher .= 'WHERE (' . implode(') AND (', $this->where) . ')' . PHP_EOL;
		}

		if (count($this->delete)) {
			$cypher .= 'DELETE ' . implode(', ', $this->delete) . PHP_EOL;
		}

		if (count($this->return)) {
			$cypher .= 'RETURN ' . implode(', ', $this->return) . PHP_EOL;
		}

		if (count($this->order)) {
			$cypher .= 'ORDER BY ' . implode(', ', $this->order) . PHP_EOL;
		}

		if ($this->skip) {
			$cypher .= 'SKIP ' . $this->skip . PHP_EOL;
		}

		if ($this->limit) {
			$cypher .= 'LIMIT ' . $this->limit . PHP_EOL;
		}

		return $cypher;
	}


	public function test()
	{
		$cypher = $this->createCypherString();
		$this->processor->setQuery($cypher);
		$parameters = $this->processor->process();
		$mask = '{%s}';

		foreach ($parameters as $parameter => $value) {
			if (is_string($value)) {
				$value = '"' . $value . '"';
			}
			$cypher = str_replace(sprintf($mask, $parameter), $value, $cypher);
		}
		echo $cypher;
	}


	public function __toString()
	{
		return $this->createCypherString();
	}


	/**
	 * @param string $cypher
	 * @return ResultSet
	 */
	public function rawQuery($cypher)
	{
		$this->processor->setQuery($cypher);
		$parameters = $this->processor->process();

		$query = new Query($this->client, $cypher, $parameters);
		return $query->getResultSet();
	}


	/**
	 * @return ResultSet
	 */
	public function execute()
	{
		$cypher = $this->createCypherString();
		return $this->rawQuery($cypher);
	}


}
