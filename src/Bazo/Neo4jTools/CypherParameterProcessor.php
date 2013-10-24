<?php

namespace Bazo\Neo4jTools;

class CypherParameterProcessor
{

	private $query;
	private $parameters = array();


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
