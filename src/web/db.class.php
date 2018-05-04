<?php
class DB
{
	private $file = null;
	private $cache = array();
	
	public function __construct(string $file)
	{
		$this->file = $file;
		
		if(!file_exists($this->file))
		{
			throw new \Error('File not found: '. $this->file);
		}
		
		$this->load();
	}
	
	public function get_all(string $section=null)
	{
		if(isset($section))
		{
			if(!$this->isset($section))
			{
				return null;
			}
			
			return $this->cache[$section];
		}
		return $this->cache;
	}
	
	public function get(string $section, string $key)
	{
		if(!$this->isset($section, $key))
		{
			trigger_error(
				"Key doesn't exists (". $section ."[". $key ."])",
				E_USER_WARNING
			);
			return null;
		}
		
		return $this->cache[$section][$key];
	}
	
	public function isset(string $section, string $key=null)
	{
		if(!isset($this->cache[$section]))
		{
			return false;
		}
		
		if(isset($key) && !isset($this->cache[$section][$key]))
		{
			return false;
		}
		
		return true;
	}
	
	public function set(string $section, string $key, string $value)
	{
		if(!$this->isset($section))
		{
			$this->cache[$section] = array();
		}
		
		$this->cache[$section][$key] = $value;
	}
	
	private function load()
	{
		$this->cache = parse_ini_file($this->file, true);
	}
}
?>
