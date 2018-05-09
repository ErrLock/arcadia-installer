<?php
class Config
{
	private $db = null;
	private $s_get_all = null;
	private $s_check = null;
	private $s_get = null;
	private $s_create = null;
	private $s_update = null;
	
	public function __construct(\PDO $db)
	{
		$this->db = $db;
		
		$result = $this->db->query(
			'SELECT `key`, `value` FROM `config` LIMIT 1;'
		);
		if($result === false)
		{
			$this->init_db();
		}
		
		$this->s_check = $this->db->prepare(
			'SELECT COUNT(*) from `config` WHERE `key`=?;'
		);
		$this->s_get_all = $this->db->prepare(
			'SELECT `key`, `value` FROM `config`;'
		);
		$this->s_get = $this->db->prepare(
			'SELECT `value` FROM `config` WHERE `key`=?;'
		);
		$this->s_create = $this->db->prepare(
			'INSERT INTO `config` (`key`, `value`) VALUES (?, ?);'
		);
		$this->s_update = $this->db->prepare(
			'UPDATE `config` SET `value`=? WHERE `key`=?;'
		);
	}
	
	private function init_db()
	{
		$driver = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
		switch($driver)
		{
			default:
				$result = $this->db->query(
					'CREATE TABLE `config` (
						`key` VARCHAR(32) NOT NULL PRIMARY KEY,
						`value` VARCHAR(64) NOT NULL
					);'
				);
				if($result === false)
				{
					throw new \Error(
						"Failed to create table 'config'".
						print_r($this->db->errorInfo(), true)
					);
				}
				break;
		}
	}
	
	public function get_all()
	{
		$this->s_get_all->execute();
		return $this->s_get_all->fetchAll();
	}
	
	public function get(string $key)
	{
		if(!$this->isset($key))
		{
			throw new \Error("Key not found: ". $key);
		}
		$this->s_get->execute(array($key));
		$result = $this->s_get->fetch();
		return $result['value'];
	}
	
	public function isset(string $key)
	{
		$this->s_check->execute(array($key));
		return ($this->s_check->fetchColumn() > 0);
	}
	
	public function set(string $key, string $value)
	{
		if($this->isset($key))
		{
			return $this->s_update->execute(array($value, $key));
		}
		
		return $this->s_create->execute(array($key, $value));
	}
}
?>
