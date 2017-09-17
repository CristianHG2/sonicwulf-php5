<?php

class SonicModel
{
	public $bridge;
	public $columns;

	public $statement = '';

	public $lastFunction = null;
	public $masterFunction = null;

	public $parameters = array();

	public $oldParams = array();

	public $statementOrder = array();
	public $statementWhere = array();
	public $statementLimit = array();

	private static $_instance = array();

	public $selfInstanceName = null;

	public $cacheResult = true;
	public $resultCached = false;
	public $resultCache = null;

	public $custom = array();

	public $table;

	public function initClass($name)
	{
		$this->columns = array();
		$this->statement = '';
		$this->lastFunction = null;
		$this->masterFunction = null;
		$this->parameters = array();
		$this->statementOrder = array();
		$this->statementWhere = array();
		$this->statementLimit = array();
		$this->bridge = GlobalConfig('sql')->getBridge();
		$this->oldParams = array();

		$this->selfInstanceName = $name;

		$this->table = strtolower($name);
		$table = $this->table;

		$describe = $this->bridge->prepare(Text::Bind(GlobalConfig('sql')->get('queryData/descrb'), array("tbl" => $table)));
		$e = $describe->execute();

		if ( $e !== true )
			throw new SonicException('Could not describe table '.$table.' please make sure that it exists', 335);

		foreach ( $describe->fetchAll(PDO::FETCH_COLUMN) as $column )
			$this->columns[] = $column;
	}

	public static function select()
	{
		$cname = get_called_class();

		if ( !isset(self::$_instance[$cname]) )
		{
			self::$_instance[$cname] = new self;
			self::$_instance[$cname]->initClass($cname);
		}

		$self = self::$_instance[$cname];

		//if ( is_null($self->lastFunction) )
		//{
			$explode = func_get_args();

			foreach ( $explode as $i )
			{
				if ( !in_array($i, $self->columns) && $i !== '*' )
					throw new SonicException('No such column "'.$i.'" in table '.Kernel::$lastDb, 331);
				elseif ( $i == '*' )
					$columns = $self->columns;
				else
					$columns[] = $i;
			}

			$self->statement = Text::Bind(GlobalConfig('sql')->get('queryData/select'), array('columns' => implode(', ', $columns)));

			$self->lastFunction = __METHOD__;
			$self->masterFunction = __METHOD__;
			return $self;
		//}
		//else
		//	throw new SonicException('Cannot method chain with method '.__METHOD__, 332);
	}

	public function where($Rconditions)
	{
		//if ( $this->lastFunction === __CLASS__.'::select' || $this->lastFunction === __CLASS__.'::update' || $this->lastFunction === __CLASS__.'::delete' )
		//{
			$conditions = array();
			
			if ( !is_array($Rconditions) && func_num_args() === 2 )
			{
				$args = func_get_args();
				$conditions[$args[0]] = $args[1];
			}
			else
				$conditions = $Rconditions;

			foreach ( $conditions as $key => $i )
			{
				if ( !in_array($key, $this->columns) )
					throw new SonicException('No such column `'.$key.'`', 431);

				$time = "a".strval(count($this->parameters));

				$this->parameters[$time] = $i;

				$clauses[] = $key.' = :'.$time;
			}

			$this->statementWhere = array('where' => 'WHERE '.implode(' AND ', $clauses));
			$this->lastFunction = __METHOD__;

			return self::$_instance[$this->selfInstanceName];
		//}
		//else
		//	throw new SonicException('Cannot method chain from method '.$this->lastFunction.' on method '.__METHOD__, 332);
	}

	public function clearParams()
	{
		$this->parameters = array();
		return $this;
	}

	public function orderby($column, $order)
	{
		if ( $this->lastFunction === __CLASS__.'::select' || $this->lastFunction === __CLASS__.'::where' || $this->lastFunction === __CLASS__.'::update' || $this->lastFunction === __CLASS__.'::delete' )
		{
			//if ( $order !== 'ASC' || $order !== 'DESC' )
			//	throw new SonicException('Invalid order type "'.$order.'"', 333);

			$time = "a".strval(count($this->parameters));

			$this->parameters[$time] = $column;

			$this->statementOrder = array('orderby' => 'ORDER BY :'.$time.' '.$order);
			$this->lastFunction = __METHOD__;

			return self::$_instance[$this->selfInstanceName];
		}
		else
			throw new SonicException('Cannot method chain from method '.$this->lastFunction.' on method '.__METHOD__, 332);
	}

	public function limit($start, $offset = null)
	{
		if ( $this->lastFunction === __CLASS__.'::select' || $this->lastFunction === __CLASS__.'::where' || $this->lastFunction === __CLASS__.'::update' || $this->lastFunction === __CLASS__.'::delete' || $this->lastFunction === __CLASS__.'::orderby' )
		{
			if ( !is_numeric($start) || ( !is_null($offset) && !is_numeric($offset)) )
				throw new SonicException('Invalid offset or start type "'.$offset.'" or "'.$start.'"', 334);

			if ( !is_null($offset) )
				$this->statementLimit = array('LIMIT' => 'LIMIT '.$start.', '.$offset);
			else
				$this->statementLimit = array('LIMIT' => 'LIMIT '.$start);

			$this->lastFunction = __METHOD__;

			return self::$_instance[$this->selfInstanceName];
		}
		else
			throw new SonicException('Cannot method chain from method '.$this->lastFunction.' on method '.__METHOD__, 332);		
	}

	public static function update($data)
	{
		$cname = get_called_class();

		if ( !isset(self::$_instance[$cname]) )
		{
			self::$_instance[$cname] = new self;
			self::$_instance[$cname]->initClass($cname);
		}

		$self = self::$_instance[$cname];

		if ( $self->lastFunction === __CLASS__.'::select' || $self->lastFunction === __CLASS__.'::where' || $self->lastFunction === __CLASS__.'::update' || $self->lastFunction === __CLASS__.'::delete' || $self->lastFunction === __CLASS__.'::orderby' || is_null($self->lastFunction) )
		{
			$stmt = array();

			foreach ( $data as $key => $i )
			{
				$time = "a".strval(count($self->parameters));

				if ( in_array($key, $self->columns) )
					$self->parameters[$time] = $i;
				else
					throw new SonicException('No such column "'.$key.'"', 331);

				$stmt[] = $key.' = :'.$time;
			}

			$self->statement = Text::Bind(GlobalConfig('sql')->get('queryData/update'), array('setCls' => 'SET '.implode(', ', $stmt)));
			$self->lastFunction = __METHOD__;

			$self->masterFunction = __METHOD__;

			return $self;
		}
		else
			throw new SonicException('Cannot method chain from method '.$self->lastFunction.' on method '.__METHOD__, 332);		
	}

	public static function insert($data)
	{
		$cname = get_called_class();

		if ( !isset(self::$_instance[$cname]) )
		{
			self::$_instance[$cname] = new self;
			self::$_instance[$cname]->initClass($cname);
		}

		$self = self::$_instance[$cname];

		if ( $self->lastFunction === __CLASS__.'::select' || $self->lastFunction === __CLASS__.'::where' || $self->lastFunction === __CLASS__.'::update' || $self->lastFunction === __CLASS__.'::delete' || $self->lastFunction === __CLASS__.'::orderby' || is_null($self->lastFunction) )
		{

			$data2 = array();			

			foreach ( $data as $key => $i )
			{
				$time = "a".strval(count($self->parameters));

				if ( in_array($key, $self->columns) )
					$self->parameters[$time] = $i;
				else
					throw new SonicException('No such column "'.$key.'"', 331);

				$data2[] = ':'.$time;
			}

			$self->statement = Text::Bind(GlobalConfig('sql')->get('queryData/insert'), array('values' => implode(', ', $data2), 'columns' => implode(', ', array_keys($data))));

			$self->lastFunction = __METHOD__;

			$self->masterFunction = __METHOD__;

			return $self;
		}
		else
			throw new SonicException('Cannot method chain from method '.$self->lastFunction.' on method '.__METHOD__, 332);			
	}

	public static function delete()
	{
		$cname = get_called_class();

		if ( !isset(self::$_instance[$cname]) )
		{
			self::$_instance[$cname] = new self;
			self::$_instance[$cname]->initClass($cname);
		}

		$self = self::$_instance[$cname];

		if ($self->lastFunction === __CLASS__.'::select' ||$self->lastFunction === __CLASS__.'::where' ||$self->lastFunction === __CLASS__.'::update' ||$self->lastFunction === __CLASS__.'::delete' ||$self->lastFunction === __CLASS__.'::orderby' || is_null($self->lastFunction) )
		{

			$self->statement = GlobalConfig('sql')->get('queryData/delete');
			$self->lastFunction = __METHOD__;

			$self->masterFunction = __METHOD__;

			return $self;
		}
		else
			throw new SonicException('Cannot method chain from method '.$self->lastFunction.' on method '.__METHOD__, 332);			
	}

	public function clearStatement()
	{
		$this->statementLimit = array();
		$this->statementWhere = array();
		$this->statementOrder = array();

		return $this;
	}

	public function num($isArray = false, $class = 'SMChild')
	{
		$arr = array_merge(array('tbl' => $this->table), $this->statementLimit, $this->statementWhere, $this->statementOrder);

		if ( $this->masterFunction == __CLASS__.'::select' )
		{
			if ( $this->lastFunction == 'SonicModel::num' || is_null($this->parameters) )
				$this->parameters = $this->oldParams;

			$prepare = $this->bridge->prepare(trim(Text::Bind($this->statement, $arr, '{$1}', false, true)));
			$prepare->execute($this->parameters);

			$this->oldParams = $this->parameters;
			$this->parameters = null;

			$rc = $prepare->rowCount();

			if ( $rc < 1 )
				return $rc;

			$this->lastFunction = __METHOD__;

			if ( $this->cacheResult )
			{
				$this->resultCached = true;

				if ( !$isArray )
				{
					$data = $prepare->fetchAll(PDO::FETCH_CLASS, $class, array($this->selfInstanceName, $this->columns));

					if ( count($data) > 1 )
						$this->resultCache = $data;
					else
						$this->resultCache = $data[0];
				}
				else
				{
					$data = $prepare->fetchAll(PDO::FETCH_ASSOC);

					if ( count($data) > 1 )
						$this->resultCache = $data;
					else
						$this->resultCache = $data[0];
				}					
			}

			return $rc;
		}	
	}

	public static function lastError()
	{
		$cname = get_called_class();

		if ( !isset(self::$_instance[$cname]) )
		{
			self::$_instance[$cname] = new self;
			self::$_instance[$cname]->initClass($cname);
		}

		$self = self::$_instance[$cname];

		return $self->bridge->errorInfo();
	}

	public static function lastId()
	{
		$cname = get_called_class();

		if ( !isset(self::$_instance[$cname]) )
		{
			self::$_instance[$cname] = new self;
			self::$_instance[$cname]->initClass($cname);
		}

		$self = self::$_instance[$cname];

		return $self->bridge->lastInsertId();
	}

	public function noNumCache()
	{
		$this->cacheResult = false;
		return self::$_instance[$this->selfInstanceName];
	}

	public function addCustomParam($name, $value)
	{
		$this->custom[$name] = $value;
		return $this;
	}

	public function run($isArray = false, $class = 'SMChild')
	{
		$arr = array_merge(array('tbl' => $this->table), $this->statementLimit, $this->statementWhere, $this->statementOrder, $this->custom);

		if ( $this->masterFunction == __CLASS__.'::select' )
		{
			if ( $this->cacheResult && $this->resultCached )
			{
				$this->resultCached = false;
				return $this->resultCache;
			}

			if ( $this->lastFunction == 'SonicModel::num' || is_null($this->parameters) )
				$this->parameters = $this->oldParams;
				
			$this->lastFunction = null;

			$prepare = $this->bridge->prepare(trim(Text::Bind($this->statement, $arr, '{$1}', false, true)));
			
			$prepare->execute($this->parameters);

			$this->parameters = null;

			if ( !$isArray )
			{
				$data = $prepare->fetchAll(PDO::FETCH_CLASS, $class, array($this->selfInstanceName, $this->columns));

				var_dump($data);

				if ( count($data) > 1 )
					return $data;
				else
					return $data[0];
			}
			else
			{
				$data = $prepare->fetchAll(PDO::FETCH_ASSOC);

				if ( count($data) > 1 )
					return $data;
				else
					return $data[0];
			}
		}
		elseif ( $this->masterFunction == __CLASS__.'::update' )
		{
			$prepare = $this->bridge->prepare(trim(Text::Bind($this->statement, $arr, '{$1}', false, true)));

			$this->lastFunction = null;

			$qReturn = $prepare->execute($this->parameters);

			$this->parameters = null;	

			return $qReturn;	
		}
		elseif ( $this->masterFunction == __CLASS__.'::insert' )
		{
			$prepare = $this->bridge->prepare(trim(Text::Bind($this->statement, $arr, '{$1}', false, true)));

			$this->lastFunction = null;

			$qReturn = $prepare->execute($this->parameters);

			$this->parameters = null;

			return $qReturn;
		}
	}
}

class SMChild
{
	public $table;

	public function __construct($table, $columns)
	{
		$this->table = $table;
		$this->init();
	}

	public function update($data)
	{
		return call_user_func_array($this->table.'::update', array($data));
	}

	public function init() {}
}