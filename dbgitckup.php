#!/usr/bin/php
<?php

set_time_limit(0);

$sqlgitckup = new DB_GIT_Backup('mysql.ini');
$sqlgitckup->makeDumps();



/**
 * Short description.
 *
 * Detail description
 * @author       Michal Borychowski
 * @version      1.0
 * @copyright    
 * @since        2008-09
 * @access       private/public
 */
class DB_GIT_Backup
{
	
	/**
	 * Short description.
	 *
	 * @param     type	$varname	description
	 * @access    private/public
	 * @return    void desc
	 * @since     2011-09-01
	 */
	function __construct($iniFile)
	{
		define('MAIN_DIR', str_replace ('\\', '/', dirname(__FILE__)));

		$this->readConfig($iniFile);

		$this->setTheNiceCmd();
	} // end func
    


	/**
	 * Short description.
	 *
	 * @param     type	$varname	description
	 * @access    private/public
	 * @return    void desc
	 * @since     2011-09-01
	 */
	function readConfig($iniFile)
	{
		$this->ini = parse_ini_file($iniFile, true);
	} // end func


	
	/**
	 * Short description.
	 *
	 * @param     type	$varname	description
	 * @access    private/public
	 * @return    void desc
	 * @since     2011-09-01
	 */
	function makeDumps()
	{
		foreach ($this->ini as $dump => $info) {
			
			if (is_array($info)) {
				if ($this->ini[$dump]['active'] === '0') {
					continue;
				}


				#Prepare the dump folder
				$this->setDumpFolder($dump);
				if (!$this->createFolder($this->dumpFolder)) {
					//The folder was not created
					//@TODO: error msg
					
					continue;
				}


				#Make the dump
				exec($this->buildMySQLDumpStatement($dump));


				#GIT it!
				$this->GITit($dump);

				
				$now = date("Ymd_His", time());
				echo("<br>DONE! $dump $now"); echo(" &nbsp;&nbsp;&nbsp;<sup>(".__FILE__.','.__LINE__.")  </sup>"); flush();
			}
		} //end foreach
		
		
		
		
		
	    
	} // end func

	/**
	 * Short description.
	 *
	 * @param     type	$varname	description
	 * @access    private/public
	 * @return    void desc
	 * @since     2011-09-01
	 */
	function buildMySQLDumpStatement($dump)
	{
		if (isset($this->ini[$dump]['db'])) {
			$db = $this->ini[$dump]['db'];
		} else {
		    $db = $dump;
		}

		//http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html
		$cmd = "$this->niceCmd ".
			$this->ini['mysqldump'] .
			" --user={$this->ini['user']}" .
			" --password={$this->ini['password']}" .
			" --host={$this->ini['host']}" .
			" --quote-names --default-character-set=utf8 --compress --force --dump-date=FALSE --extended-insert=FALSE" .
			" --result-file={$this->dumpFolder}/$dump.sql".
			" $db" .
			" {$this->ini[$dump]['tableNames']}"
			;

		return $cmd;
	} // end func


	
	/**
	 * Short description.
	 *
	 * @param     type	$varname	description
	 * @access    private/public
	 * @return    void desc
	 * @since     2011-09-03
	 */
	function setDumpFolder($folder)
	{
		//Absolute
		if (substr($this->ini['dumpsFolder'], 0, 1) == '/') {
			$this->dumpFolder = $this->ini['dumpsFolder'].'/'.$folder;
			
			return;
		} else {
			$this->dumpFolder = MAIN_DIR.'/'.$this->ini['dumpsFolder'].'/'.$folder;

			return;
		}
	} // end func


	
	/**
	 * Short description.
	 *
	 * @param     type	$varname	description
	 * @access    private/public
	 * @return    void desc
	 * @since     2011-09-03
	 */
	function createFolder($folder)
	{
		if (!is_dir($folder)) {

			//@TODO: windows compatibility
			exec("mkdir -p $folder");
		}

		//Double check if the folder exists
		return is_dir($folder);
	} // end func


	
	/**
	 * Short description.
	 *
	 * @param     type	$varname	description
	 * @access    private/public
	 * @return    void desc
	 * @since     2011-09-03
	 */
	function GITit($dump)
	{
		chdir($this->dumpFolder);
		
		//Check if repo has already been initialized
		if (!is_dir('.git')) {
			exec('git init');
		}

		exec("$this->niceCmd git add .");
		
		$now = date("Ymd_His", time());
		exec("$this->niceCmd git commit -m '$now'");

		if ($this->ini[$dump]['gc']) {
			exec("$this->niceCmd git gc");
		}
	} // end func 


	
	/**
	 * Short description.
	 *
	 * @param     type	$varname	description
	 * @access    private/public
	 * @return    void desc
	 * @since     2008-09
	 */
	function setTheNiceCmd()
	{
		if ($this->ini['nice']) {
			
			//@TODO: limit for allowable values
			$this->niceCmd = "nice -n {$this->ini['nice']}";
		}
	} // end func

} // end class
