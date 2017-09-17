<?php 

class gates extends Config
{
	public function getGates($view)
	{
		$r = array();

		foreach ( $this->getAll() as $key => $i )
		{
			$data = strpos($view, $i);

			if ( $data !== false )
				$r[] = $key;
		}

		if ( count($r) < 1 )
			return false;
		else
			return $r;
	}

	public function manualGate($gate)
	{
			$g = $gate;
			$name = $g.'Anon';

			include GATE_DIR.$g.'.php';
			return ${$name}();		
	}

	public function runGate($gate)
	{
		$name = $gate.'Anon';

		include GATE_DIR.$gate.'.php';

		return ${$name}();

		return false;
	}
} 

