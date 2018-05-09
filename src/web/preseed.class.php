<?php
class Preseed
{
	private $db = null;
	private $uuid = null;
	private $s_create_d = null;
	private $s_create_u = null;
	private $s_update_u = null;
	private $s_get_all = null;
	private $s_check_d = null;
	private $s_check_u = null;
	
	public function __construct(\PDO $db, string $uuid=null)
	{
		$this->db = $db;
		$this->uuid = $uuid;
		
		$preseed = $this->db->query(
			'SELECT `package`, `key`, `type`, `default` FROM `preseed` LIMIT 1;'
		);
		$preseed_uuid = $this->db->query(
			'SELECT `uuid`, `package`, `key`, `value` '.
			'FROM `preseed_uuid` LIMIT 1;'
		);
		if($preseed === false || $preseed_uuid === false)
		{
			$this->init_db();
		}
		
		$this->s_check_d = $this->db->prepare(
			'SELECT COUNT(*) FROM `preseed` '.
			'WHERE `package`=? AND `key`=?;'
		);
		$this->s_create_d = $this->db->prepare(
			'INSERT INTO `preseed` (`package`, `key`, `type`, `default`) '.
			'VALUES (?, ?, ?, ?);'
		);
		
		$this->s_check_u = $this->db->prepare(
			'SELECT COUNT(*) FROM `preseed_uuid` '.
			'WHERE `uuid`=? AND `package`=? AND `key`=?;'
		);
		$this->s_create_u = $this->db->prepare(
			'INSERT '.
			'INTO `preseed_uuid` (`uuid`, `package`, `key`, `value`) '.
			'VALUES (?, ?, ?, ?);'
		);
		$this->s_update_u = $this->db->prepare(
			'UPDATE `preseed_uuid` '.
			'SET `value`=? '.
			'WHERE `uuid`=? AND `package`=? AND `key`=?;'
		);

		$this->s_get_all = $this->db->prepare(
			'SELECT p.`package`, p.`key`, p.`type`, '.
			'COALESCE(u.`value`, p.`default`) AS `value` '.
			'FROM `preseed` AS p '.
			'LEFT JOIN `preseed_uuid` AS u '.
			'USING (`package`,`key`) '.
			'WHERE u.`value` NOT NULL OR p.`default` NOT NULL;'
		);
	}
	
	private function init_db()
	{
		$driver = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
		switch($driver)
		{
			default:
				$result = $this->db->query(
					'CREATE TABLE `preseed` (
						`package` VARCHAR(32) DEFAULT `d-i`,
						`key` VARCHAR(128) NOT NULL,
						`type` VARCHAR(16) DEFAULT `string`,
						`default` VARCHAR(128),
						CONSTRAINT PK_preseed PRIMARY KEY (`package`,`key`)
					);'
				);
				if($result === false)
				{
					throw new \Error(
						"Failed to create table 'preseed'".
						print_r($this->db->errorInfo(), true)
					);
				}
				$result = $this->db->query(
					'CREATE TABLE `preseed_uuid` (
						`uuid` CHAR(36) NOT NULL,
						`package` VARCHAR(32) DEFAULT `d-i`,
						`key` VARCHAR(128) NOT NULL,
						`value` VARCHAR(128) NOT NULL,
						CONSTRAINT PK_preseed_uuid '.
						'PRIMARY KEY (`uuid`,`package`,`key`),
						CONSTRAINT FK_preseed FOREIGN KEY (`package`,`key`)
						REFERENCES `preseed` (`package`,`key`)
					);'
				);
				if($result === false)
				{
					throw new \Error(
						"Failed to create table 'preseed_uuid'".
						print_r($this->db->errorInfo(), true)
					);
				}
				break;
		}
	}
	
	public function get_all()
	{
		$result = array();
		$this->s_get_all->execute();
		
		return $this->s_get_all->fetchAll();
	}
	
	private function isset_d(string $package, string $key)
	{
		$this->s_check_d->execute(array($package, $key));
		return ($this->s_check_d->fetchColumn() > 0);
	}
	
	private function isset_u(string $package, string $key)
	{
		if(!isset($this->uuid))
		{
			trigger_error("uuid not set", E_USER_WARNING);
			return false;
		}
		
		$this->s_check_u->execute(array($this->uuid, $package, $key));
		return ($this->s_check_u->fetchColumn() > 0);
	}
	
	public function set(string $key, string $value, string $package='d-i')
	{
		if(!isset($this->uuid))
		{
			trigger_error("uuid not set", E_USER_WARNING);
			return;
		}
		
		if(!$this->isset_d($package, $key))
		{
			throw new \Error("Unknown key: ". $key);
		}
		
		if($this->isset_u($package, $key))
		{
			return $this->s_update_u->execute(
				array($value, $this->uuid, $package, $key)
			);
		}
		
		return $this->s_create_u->execute(
			array($this->uuid, $package, $key, $value)
		);
	}
	
	public function create(
		string $key,
		string $default=null,
		string $type='string',
		string $package='d-i'
	)
	{
		if($this->isset_d($package, $key))
		{
			throw new \Error('default already exists');
		}
		
		return $this->s_create_d->execute(
			array($package, $key, $type, $default)
		);
	}
	
	public function __toString()
	{
		$preseed = "#_preseed_V1\n\n";
		
		foreach($this->get_all() as $settings)
		{
			$preseed .= $settings['package'] ." ". $settings['key'] ." ".
				$settings['type'] ." ". $settings['value'] ."\n";
		}
		
		return $preseed;
	}
}
?>
