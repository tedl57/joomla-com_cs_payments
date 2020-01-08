<?php
defined('_JEXEC') or die; // No direct access

abstract class Cs_paymentsPayprocAction
{
	/////////////////////////////////////////
	// properties
	protected $id;
	protected $username;
	protected $action;

	/////////////////////////////////////////
	// abstract methods child classes MUST PROVIDE
	abstract public function executeAction();	// this does the real work
	static abstract public function getAuthLevel();	// eg, core.edit.state
	abstract public function getTitle();		// tooltip-like description - returns false to not be shown

	/////////////////////////////////////////
	// public methods
	public function __construct($id,$username,$action)
	{
		$this->action = strtolower(str_replace(get_class(),"",get_class($this)));
		$this->id = $id;
		$this->username = $username;
		$this->action = $action;
	}
	public function doConfirm() { return false; }	// child class can override
	public function isBuiltin() { return false; }	// child class can override
	public function getId() { return $this->id; }
	public function getUsername() { return $this->username; }
	public function getAction() { return $this->action; }
	public function getActionNameUpper() { return ucwords( str_replace('_',' ', $this->getAction() )); }
	
	/////////////////////////////////////////
	// static methods
	static public function getScriptDirectory() { return realpath(dirname(__FILE__)); }
	static public function getBaseClassName() { return get_class(); }
	static public function getChildClassName($action) { return self::getBaseClassName().ucwords($action); }
	static public function getBuiltins() { return array('process','show_data'); }	// add new builtin here
	static public function isActionBuiltin($action) { return in_array($action, self::getBuiltins() ); }
	static public function loadActionClass($dir=null, $action=null, $bAuthorize=true)
	{
		if ($dir === null)
			$dir = self::getScriptDirectory();
		$bBuiltin = self::isActionBuiltin($action);
		$classfile = "$dir" . ($bBuiltin ? "/builtins/" : "/plugins/" ) . "$action.php";
		@include_once $classfile;
		$classname = self::getBaseClassName().ucwords($action);
		if ( ! class_exists( $classname ) )
			return null;

		// only load actions user has permission to do; also protect against URL spoofing
		if ( $bAuthorize )
		{
			$loggeduser = JFactory::getUser();
			
			if ( ! $loggeduser->authorise($classname::getAuthLevel(), 'com_cs_payments') )
				return null;
		}
		return $action;
	}
	static public function loadActions( $dir = null, $bAuthorize = true, $bBuiltinsFirst = true )
	{
		$actions = array();	// will return (optionally) authorized builtin and plugin action object names
		
		if ( $dir === null )
			$dir = self::getScriptDirectory();

		// load builtins, then plugins
		$builtins = self::getBuiltins();
		foreach($builtins as $action)
			if ( ($action = self::loadActionClass($dir, $action, $bAuthorize)) !== null )
				$actions[$action] = true;

		// get list of all (child class) php files in plugins directory
		$direntries = glob("$dir/plugins/*.php");
	
		if ( $direntries === NULL )
			return $actions;

		sort( $direntries );

		foreach( $direntries as $ent )
			if ( ($action = self::loadActionClass($dir, pathinfo($ent,PATHINFO_FILENAME), $bAuthorize)) !== null )
				$actions[$action] = true;

		if ( ! $bBuiltinsFirst )
			ksort( $actions );

		return $actions;
	}
}
?>
