<?php
require_once('./config.php');
require_once('./db.class.php');

class Preseed
{
	private $default_db = null;
	private $client_db = null;
	
	public function __construct(DB $client_db)
	{
		$default_file = ARCADIA_SYSCONFDIR ."/preseed.ini";
		if(is_file($default_file)) {
			$this->default_db = new DB($default_file);
		}
		
		$this->client_db = $client_db;
	}
	
	public function get_all()
	{
		$result = array();
		if(isset($this->default_db))
		{
			$result = $this->default_db->get_all();
		}
		
		$result = array_merge($result, $this->client_db->get_all());
		
		return $result;
	}
	
	public function isset(string $key)
	{
		if($this->client_db->isset($key))
		{
			return true;
		}
		
		if(isset($this->default_db) && $this->default_db->isset($key))
		{
			return true;
		}
		
		return false;
	}
	
	public function set(
		string $key,
		string $value,
		string $type='string',
		string $target='d-i'
	)
	{
		$this->client_db->set($key, 'value', $value);
		$this->client_db->set($key, 'type', $type);
		$this->client_db->set($key, 'target', $target);
	}
	
	public function __toString()
	{
		$result = "#_preseed_V1\n\n";
		
		foreach($this->get_all() as $key => $settings)
		{
			$result .= $settings['target'] ." ". $key ." ". $settings['type'] .
				" ". $settings['value'] ."\n";
		}
		
		return $result;
	}
}
?>
