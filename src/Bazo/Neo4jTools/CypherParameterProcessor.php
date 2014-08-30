<?php

namespace Bazo\Neo4jTools;

class CypherParameterProcessor
{

	/** @var string */
	private $query;

	/** @var array */
	private $parameters = [];


	public function setQuery($string)
	{
		$this->query = $string;
	}


	public function getQuery()
	{
		return $this->query;
	}


	public function setParameter($name, $value)
	{
		if (is_object($value) && method_exists($value, 'getId')) {
			$this->parameters[$name] = $value->getId();
		} else {
			$this->parameters[$name] = $value;
		}
	}


	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}


	public function process()
	{
		$parameters = $this->parameters;
		$string = $this->query;

		$string = str_replace('[:', '[;;', $string);
		$parameters = array_filter($parameters, function ($value) use (& $parameters, & $string) {
			$key = key($parameters);
			next($parameters);

			$string = str_replace(":$key", '{' . $key . '}', $string);
			return true;
		});
		$string = str_replace('[;;', '[:', $string);

		$this->query = $string;
		return $parameters;
	}


}
