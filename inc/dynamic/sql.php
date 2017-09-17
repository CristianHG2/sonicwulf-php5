<?php 

class sql extends Config {

	public function getBridge()
	{
		return new PDO($this->get('pdo/driver').':host='.$this->get('db/host').';dbname='.$this->get('db/name').';port='.$this->get('db/port'), $this->get('db/user'), $this->get('db/password'));
	}

} 

