<?php

class Config
{
	public $file;
	public $config;

	public function init()
	{
		$this->refreshConfig();
	}

	public function getFileConf()
	{
		if ( is_null($get = json_decode(file_get_contents($this->get('file')), true)) )
			throw new SonicException('Could not parse configuration file '.$this->get('file'), 512);
		else
			return $get;		
	}

	public function writeConfig($arr)
	{
		if ( file_put_contents($this->get('file'), json_encode($array)) === false )
			throw new SonicException('Could not write to configuration file '.$this->get('file'), 513);
		else
			return true;
	}

	public function set($property, $value)
	{
		if ( $property == 'file' )
			$this->file = $value;
		else
			$this->config[$property] = $value;
	}

	public function setAndCommit($property, $value)
	{
		$get = $this->getFileConf();
		$get[$property] = $value;

		$this->writeConfig();

		$this->config[$property] = $value;
		return true;
	}

	public function refreshConfig()
	{
		$this->config = $this->getFileConf();
	}

	public function get($property, $refresh = false)
	{
		if ( $refresh )
			$this->refreshConfig();

		$return = null;

		if ( strpos($property, '/') !== false )
		{
			$exp = explode('/', $property);

			$tempArr = null;

			foreach ( $exp as $key => $i )
			{
				if ( is_null($tempArr) )
				{
					if ( isset($this->config[$i]) )
						$tempArr = $this->config[$i];
					else
						$return = false;
				}
				else
				{
					if ( isset($tempArr[$i]) )
						$tempArr = $tempArr[$i];
					else
						$return = false;
				}
			}

			if ( $return !== false )
				$return = $tempArr;
		}
		else if ( isset($this->config[$property]) )
			$return = $this->config[$property];

		return (($property == 'file') ? $this->file : $return);
	}

	public function getBulk()
	{
		$args = func_get_args();
		$arr = array();

		foreach ( $arr as $i )
		{
			if ( !isset($arr[$i]) && SONICWULF_STRICT )
				throw new SonicException('The property '.$i.' does not exist', 516);
			else
				trigger_error('SonicWarn 516: The property '.$i.' does not exist', E_USER_WARNING);

			$arr[$i] = $this->config[$i];
		}

		return $arr;
	}

	public function getAll()
	{
		return $this->config;
	}

	public function commitAll()
	{
		return $this->writeConfig($this->config);
	}

	public function __get($name)
	{
		if ( !is_null($this->get($name)) )
			throw new SonicException('Cannot access property values directly, refer to Config::get', 515);
	}

	public function __set($name, $val)
	{
		if ( !isset($this->$name) )
			throw new SonicException('Cannot change property value directly, refer to Config::set', 514);
	}
}