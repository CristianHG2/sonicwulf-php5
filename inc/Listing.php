<?php

class Listing
{
	public $model;
	public $template = null;

	public $customFields = array();
	public $hooks = array();

	public $isCustom = false;

	public $table = '';

	public function __construct($table = null)
	{
		$this->table = $table;
	}

	public function setCMQ($modelData)
	{
		$this->model = $modelData;
		$this->isCustom = true;
	}

	public function setTemplate($template)
	{
		$this->template = $template;
	}

	public function addCustom($name, $value)
	{
		$this->customFields[$name] = $value;
	}

	public function addSqlHook($column, $anonFunction)
	{
		$this->hooks[$column] = $anonFunction;
	}

	public function getFillData()
	{
		$data = array();
		$cast = null;

		if ( !$this->isCustom )
		{
			$index = 0;

			$dataSql = call_user_func_array($this->table.'::select', array('*'))->clearStatement()->run(true);

			//exit;

			foreach ( $dataSql as $i )
			{
				foreach ( $i as $key => $val )
				{
					if ( isset($this->hooks[$key]) )
						$cast[$key] = $this->hooks[$key]($val);
					else
						$cast[$key] = $val;
				}
			
				$data[$index] = $cast;

				$index++;
			}
		}
		else
		{
			$data = array();
			$index = 0;

			if ( $this->model->num(true) < 2 )
				$dataArr = array($this->model->run(true));
			else
				$dataArr = $this->model->run(true);

			foreach ( $dataArr as $i )
			{
				$cast = $i;

				foreach ( $cast as $key => $val )
				{
					if ( isset($this->hooks[$key]) )
						$cast[$key] = $this->hooks[$key]($val);
				}

				$data[$index] = $cast;

				$index++;
			}
		}

		foreach ( $data as $key => $i )
		{
			foreach ( $this->customFields as $key2 => $i2 )
				$data[$key][$key2] = $i2;
		}

		return array_merge($data);
	}

	public function execute()
	{
		if ( is_null($this->template) )
			throw new SonicException('A template must be set before listing execution', 736);

		$q = $this->getFillData();

		foreach ( $q as $i )
			echo Text::Bind($this->template, $i);

		return $q;
	}
}