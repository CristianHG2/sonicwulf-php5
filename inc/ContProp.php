<?php

class ContProp
{
	public $contPropname;

	static $contProps;

	public function __construct($name)
	{
		$this->contPropname = $name;
	}

	static function RegisterCP($object)
	{
		if ( $object instanceof ContProp )
			self::$contProps[$object->contPropname] = $object;
		else
			throw new SonicException('Argument provided for ContProp::RegisterCP is not an instance of ContProp', 624);
	}

	static function cpv($name, $val)
	{
		if ( isset(self::$contProps[$name]) )
			return self::$contProps[$name]->get($val);
		else
			return false;
	}

	public function set($name, $val)
	{
		if ( $name == 'contPropname' )
			throw new SonicException('Cannot set the ContProp::contPropname property', 627);

		$this->$name = $val;
	}

	public function get($name)
	{
		if ( isset($this->$name) )
			return $this->$name;
		else
			return false;
	}
}