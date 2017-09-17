<?php 

class views extends Config
{
	public $gatedRegex = array();

	public function getHeaders($page)
	{
		if ( !isset($this->gatedRegex[$page]) )
		{
			if ( ($ret = $this->runFind($page)) !== false )
				return $ret;
			else
				return $this->turnIndex($this->get('literal/default'));
		}
		else
			return $this->gatedRegex[$page];
	}

	public function turnIndex($array)
	{
		$arr = array();

		foreach ( $array as $i )
			$arr[] = $i;

		return $arr;
	}

	public function runFind($page)
	{
		$literal = $this->get('literal/'.$page);

		if ( $literal == false )
		{
			$props = $this->get('regex');

			if ( !is_null($props) )
			{
				foreach ( $props as $key => $i )
				{
					if ( strpos($page, $key) !== false )
					{
						$this->gatedRegex[$page] = array($i['head'], $i['header'], $i['footer']);
						return array($i['head'], $i['header'], $i['footer']);
					}
				}
			}
		}
		elseif ( $literal !== false )
		{
			return array($literal['head'], $literal['header'], $literal['footer']);
		}

		return false;
	}
}