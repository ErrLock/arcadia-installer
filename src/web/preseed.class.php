<?php
require_once('./db.class.php');

class Preseed
{
	private $db = null;
	
	public function __construct(DB $db)
	{
		$this->db = $db;
	}
	
	public function isset(string $key)
	{
		return $this->db->isset($key);
	}
	
	public function set(
		string $key,
		string $value,
		string $type='string',
		string $target='d-i'
	)
	{
		$this->db->set($key, 'value', $value);
		$this->db->set($key, 'type', $type);
		$this->db->set($key, 'target', $target);
	}
	
	public function __toString()
	{
		$result = "#_preseed_V1\n\n";
		
		foreach($this->db->get_all() as $key => $settings)
		{
			$result .= $settings['target'] ." ". $key ." ". $settings['type'] .
				" ". $settings['value'] ."\n";
		}
		
		return $result;
	}
}
?>
