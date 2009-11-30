<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * @package     TestLink
 * @author      Erik Eloff
 * @copyright   2006-2009, TestLink community
 * @version     CVS: $Id: tlPlatform.class.php,v 1.9 2009/11/30 21:52:19 erikeloff Exp $
 * @link        http://www.teamst.org/index.php
 *
 * @internal Revision:
 *
 *  20091118 - franciscom - getID() - fixed added testproject id in where clause
 *	20091031 - franciscom - getAll(),getAllAsMap(),getLinkedToTestplanAsMap() - added orderBy
 *	20090807 - franciscom - added check on empty name with exception (throwIfEmptyName())
 *                          linkToTestplan(),unlinkFromTestplan() interface changes
 *	20090805 - Eloff    Updated code according to guidelines
 */

/**
 * Class for handling platforms
 * @author Eloff
 **/
class tlPlatform extends tlObjectWithDB
{
	protected $tproject_id;

	const E_NAMENOTALLOWED = -1;
	const E_NAMELENGTH = -2;
	const E_NAMEALREADYEXISTS = -4;
	const E_DBERROR = -8;
	const E_WRONGFORMAT = -16;


	/**
	 * @param $db database object
	 * @param $tproject_id to work on. If null (default) the project in session
	 *                     is used
     * DO NOT USE this kind of code is not accepted have this kind of global coupling
     * for lazy users
	 */
	public function __construct(&$db, $tproject_id = null)
	{
		parent::__construct($db);
		$this->tproject_id = $tproject_id;
	}

	/**
	 * Creates a new platform.
	 * @return tl::OK on success otherwise E_DBERROR;
	 */
	public function create($name, $notes=null)
	{
		$safeName = $this->throwIfEmptyName($name);
		$alreadyExists = $this->getID($name);
		if ($alreadyExists)
		{
			$status = self::E_NAMEALREADYEXISTS;
		}
		else
		{
			$sql = "INSERT INTO {$this->tables['platforms']} " .
				   "(name, testproject_id, notes) " .
				   " VALUES ('" . $this->db->prepare_string($safeName) . 
				   "', $this->tproject_id, '".$this->db->prepare_string($notes)."')";
			$result = $this->db->exec_query($sql);
			$status = $result ? tl::OK : self::E_DBERROR;
		}
		return $status;
	}

	/**
	 * Gets all info of a platform
	 *
	 * @return array with keys id, name and notes
	 */
	public function getByID($id)
	{
		$sql = "SELECT id, name, notes
				FROM {$this->tables['platforms']}
				WHERE id = {$id}";
		return $this->db->fetchFirstRow($sql);
	}
	
	/**
	 * Gets all info of a platform
	 * @return array with keys id, name and notes
     * @TODO remove - francisco
	 */
    public function getPlatform($id)
    {
    	return $this->getByID($id);
    }

	/**
	 * Updates values of a platform in database.
	 * @param $id the id of the platform to update
	 * @param $name the new name to be set
	 * @param $notes new notes to be set
	 *
	 * @return tl::OK on success, otherwise E_DBERROR
	 */
	public function update($id, $name, $notes)
	{
		$safeName = $this->throwIfEmptyName($name);
		$sql = " UPDATE {$this->tables['platforms']} " .
		       " SET name = '" . $this->db->prepare_string($name) . "' " .
		       ", notes =  '". $this->db->prepare_string($notes) . "' " .
			   " WHERE id = {$id}";
		$result =  $this->db->exec_query($sql);
		return $result ? tl::OK : self::E_DBERROR;
	}

	/**
	 * Removes a platform from the database.
	 * @TODO: remove all related data to this platform?
	 *        YES!
	 * @param $id the platform_id to delete
	 *
	 * @return tl::OK on success, otherwise E_DBERROR
	 */
	public function delete($id)
	{
		$sql = "DELETE FROM {$this->tables['platforms']} WHERE id = {$id}";
		$result = $this->db->exec_query($sql);
		
		return $result ? tl::OK : self::E_DBERROR;
	}

	/**
	 * links one or more platforms to a testplan
	 *
	 * @return tl::OK if successfull otherwise E_DBERROR
	 */
	public function linkToTestplan($id, $testplan_id)
	{
		$idSet = (array)$id;
		$result = true;
		foreach ($idSet as $platform_id)
		{
			$sql = "INSERT INTO {$this->tables['testplan_platforms']}
					(testplan_id, platform_id)
					VALUES ($testplan_id, $platform_id)";
			$result = $this->db->exec_query($sql);
			if(!$result)
			{
				break;
			}	
		}
		return $result ? tl::OK : self::E_DBERROR;
	}

	/**
	 * Removes one or more platforms from a testplan
	 * @TODO: should this also remove testcases and executions?
	 *
	 * @return tl::OK if successfull otherwise E_DBERROR
	 */
	public function unlinkFromTestplan($id,$testplan_id)
	{
		$idSet = (array)$id;
	    $result = true;
		foreach ($idSet as $platform_id)
		{
			$sql = " DELETE FROM {$this->tables['testplan_platforms']} " .
				   " WHERE testplan_id = {$testplan_id} " .
				   " AND platform_id = {$platform_id} ";
		    
		    $result = $this->db->exec_query($sql);
			if(!$result)
			{
				break;
			}	
		}	   
		return $result ? tl::OK : self::E_DBERROR;
	}

	/**
	 * Gets the id of a platform given by name
	 *
	 * @return integer platform_id
	 */
	public function getID($name)
	{
		$sql = " SELECT id FROM {$this->tables['platforms']} " .
			   " WHERE name = '" . $this->db->prepare_string($name) . "'" . 
			   " AND testproject_id = {$this->tproject_id} ";
		return $this->db->fetchOneValue($sql);
	}

	/**
	 * @options array $options Optional params
	 *                         ['include_linked_count'] => adds the number of
	 *                         testplans this platform is used in
	 *
	 * @return array of all available platforms
	 */
	public function getAll($options = null)
	{
		$default = array(
			'include_linked_count' => false
		);
		$options = array_merge($default, (array)$options);
		if (!$options['include_linked_count'])
		{
			$sql = "SELECT id, name, notes
					FROM {$this->tables['platforms']}";
			if (!is_null($whereClause)) {
				$sql .= $whereClause;
			}
		}
		else
		{
			$sql = "SELECT p.id,p.name,p.notes,
					COUNT(tp.testplan_id) as linked_count
					FROM {$this->tables['platforms']} p
					LEFT JOIN {$this->tables['testplan_platforms']} tp
					ON tp.platform_id = p.id";

			if (!is_null($whereClause)) {
				$sql .= $whereClause;
			}

			$sql .= " GROUP BY p.id";
		}
		$sql .= " ORDER BY name";
		return $this->db->get_recordset($sql);
	}

	/**
	 * @param string $orderBy
	 * @return array Returns all available platforms in the active testproject
	 *               as array($platform_id => $platform_name)
	 */
	public function getAllAsMap($accessKey='id',$output='columns',$orderBy=' ORDER BY name ')
	{
		$sql = "SELECT id, name
				FROM {$this->tables['platforms']}
				WHERE testproject_id = {$this->tproject_id} {$orderBy}";
		if( $output == 'columns' )
		{
			$rs = $this->db->fetchColumnsIntoMap($sql, $accessKey, 'name');
		}
		else
		{
			$rs = $this->db->fetchRowsIntoMap($sql, $accessKey);
		}	
		return $rs;
	}

	/**
	 * Logic to determine if platforms should be visible for a given testplan.
	 * @return bool true if the testplan has more than one linked platforms
	 *              otherwise false.
	 */
	public function platformVisibleForTestplan($testplan_id)
	{
		$sql = "SELECT COUNT(0) as num
				FROM {$this->tables['testplan_platforms']}
				WHERE testplan_id = {$testplan_id}";
		$num_tplans = $this->db->fetchOneValue($sql);
		return ($num_tplans > 1);
	}
	
	/**
	 * @param string $orderBy
	 * @return array Returns all platforms associated to a given testplan
	 */
	public function getLinkedToTestplan($testplanID,$orderBy=' ORDER BY name ')
	{
		$sql = "SELECT P.id, P.name, P.notes
				FROM {$this->tables['platforms']} P
				JOIN {$this->tables['testplan_platforms']} TP
				ON P.id = TP.platform_id
				WHERE  TP.testplan_id = {$testplanID} {$orderBy}";
		return $this->db->get_recordset($sql);
	}


	/**
	 * @param string $orderBy
	 * @return array Returns all platforms associated to a given testplan
	 *	             on the form $platform_id => $platform_name
	 */
	public function getLinkedToTestplanAsMap($testplanID,$orderBy=' ORDER BY name ')
	{
		$sql = "SELECT P.id, P.name
				FROM {$this->tables['platforms']} P
				JOIN {$this->tables['testplan_platforms']} TP
				ON P.id = TP.platform_id
				WHERE  TP.testplan_id = {$testplanID} {$orderBy}";
		return $this->db->fetchColumnsIntoMap($sql, 'id', 'name');
	}


   
	/**
	 * @return 
	 *	       
	 */
	public function throwIfEmptyName($name)
	{
		$safeName = trim($name);
		if (tlStringLen($safeName) == 0)
		{
			$msg = "Class: " . __CLASS__ . " - " . "Method: " . __FUNCTION__ ;
			$msg .= " Empty name ";
			throw new Exception($msg);
	    }
        return $safeName;
    }


}
