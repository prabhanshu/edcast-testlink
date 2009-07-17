<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 * 
 * Management and assignment of keywords
 *
 * @package 	TestLink
 * @copyright 	2007-2009, TestLink community 
 * @version    	CVS: $Id: keyword.class.php,v 1.23 2009/07/17 08:36:45 franciscom Exp $
 * @filesource	http://testlink.cvs.sourceforge.net/viewvc/testlink/testlink/lib/functions/keyword.class.php?view=markup
 * @link 		http://www.teamst.org/index.php
 *
 **/

/** parenthal classes */
require_once('object.class.php');

/** export/import */
require_once('csv.inc.php');
require_once('xml.inc.php');

/**
 * Support for keywords management
 * @package 	TestLink
 */ 
class tlKeyword extends tlDBObject implements iSerialization,iSerializationToXML,iSerializationToCSV
{
	/** @var string the name of the keyword */
	public $name;

	/** @var string the notes for the keyword */
	public $notes;

	/** @var string the testprojectID the keyword belongs to */
	public $testprojectID;

	/** error codes */
	const E_NAMENOTALLOWED = -1;
	const E_NAMELENGTH = -2;
	const E_NAMEALREADYEXISTS = -4;
	const E_DBERROR = -8;
	const E_WRONGFORMAT = -16;
	
	/* 
	 * Brings the object to a clean state
	 * @param interger $options additional initialization options, can be TLOBJ_O_SEARCH_BY_ID
	 */
	protected function _clean($options = self::TLOBJ_O_SEARCH_BY_ID)
	{
		$this->name = null;
		$this->notes = null;
		$this->testprojectID = null;
		if (!($options & self::TLOBJ_O_SEARCH_BY_ID))
		{
			$this->dbID = null;
		}	
	}
	
	/**
	 * Class constructor
	 * 
	 * @param integer $dbID the database identifier of the keywords
	 */
	function __construct($dbID = null)
	{
		parent::__construct($dbID);
	}
	
	/* 
	 * Class destructor
	 */
	function __destruct()
	{
		parent::__destruct();
		$this->_clean();
	}
	
	/**
	 * Initializes the keyword object
	 * 
	 * @param interger $testprojectID the id of the testproject the keywords belongs to
	 * @param string $name the name of the keyword
	 * @param string $notes the notes for the keywords
	 */
	function initialize($testprojectID,$name,$notes)
	{
		$this->name = $name;
		$this->notes = $notes;
		$this->testprojectID = $testprojectID;
	}
	
	//BEGIN interface iDBSerialization
	/* Reads a keyword from the database
	 * 
	 * @param resource $db [ref] the database connection
	 * @param integer $options any combination of TLOBJ_O_ Flags
	 * 
	 * @return integer returns tl::OK on success, tl::ERROR else
	 */
	public function readFromDB(&$db,$options = self::TLOBJ_O_SEARCH_BY_ID)
	{
		$this->_clean($options);
		$query = " SELECT id,keyword,notes,testproject_id FROM {$this->tables['keywords']} ";
		
		$clauses = null;
		if ($options & self::TLOBJ_O_SEARCH_BY_ID)
		{
			$clauses[] = "id = {$this->dbID}";		
		}
		if ($clauses)
		{
			$query .= " WHERE " . implode(" AND ",$clauses);
		}
		$info = $db->fetchFirstRow($query);			 
		if ($info)
		{
			$this->dbID = $info['id'];
			$this->name = $info['keyword'];
			$this->notes = $info['notes'];
			$this->testprojectID = $info['testproject_id'];
		}
		return $info ? tl::OK : tl::ERROR;
	}

	/* 
	 * Writes an keyword into the database
	 * 
	 * @param resource $db [ref] the database connection
	 * 
	 * @return integer returns tl::OK on success, tl::ERROR else
	 */
	public function writeToDB(&$db)
	{
		$result = $this->checkKeyword($db);
		if ($result >= tl::OK)
		{
			$name = $db->prepare_string($this->name);
			$notes = $db->prepare_string($this->notes);

			if ($this->dbID)
			{
				$query = "UPDATE {$this->tables['keywords']} " .
				         " SET keyword = '{$name}',notes = '{$notes}',testproject_id = {$this->testprojectID}" .
						 " WHERE id = {$this->dbID}";
				$result = $db->exec_query($query);
			}
			else
			{
				$query = " INSERT INTO {$this->tables['keywords']} (keyword,testproject_id,notes) " .
						 " VALUES ('" . $name .	"'," . $this->testprojectID . ",'" . $notes . "')";
				
				$result = $db->exec_query($query);
				if ($result)
				{
					$this->dbID = $db->insert_id($this->tables['keywords']);
				}	
			}
			$result = $result ? tl::OK : self::E_DBERROR;
		}
		return $result;
	}

	/**
	 * Check if keyword name is not duplicated
	 * 
	 * @param resource &$db [ref] database connection
	 * 
	 * @return integer returns tl::OK on success, error code else
	 */
	protected function checkKeyword(&$db)
	{
		$this->name = trim($this->name);
		$this->notes = trim($this->notes);
		
		$result = tlKeyword::doesKeywordExist($db,$this->name,$this->testprojectID,$this->dbID);
		if ($result >= tl::OK)
		{
			$result = tlKeyword::checkKeywordName($this->name);
		}	
		return $result;
	}

	/* 
	 * Deletes a keyword from the database, deletes also the keywords from the testcase_keywords, and object_keywords
	 * tables
	 *  
	 * @param resource &$db [ref] database connection
	 *
	 * @return integer returns tl::OK on success, tl:ERROR else
	 */
	public function deleteFromDB(&$db)
	{
		$sql = "DELETE FROM {$this->tables['testcase_keywords']} WHERE keyword_id = " . $this->dbID;
		$result = $db->exec_query($sql);
		if ($result)
		{
			$sql = "DELETE FROM {$this->tables['object_keywords']}  WHERE keyword_id = " . $this->dbID;
			$result = $db->exec_query($sql);
		}
		if ($result)
		{
			$sql = "DELETE FROM {$this->tables['keywords']} WHERE id = " . $this->dbID;
			$result = $db->exec_query($sql);
		}
		return $result ? tl::OK : tl::ERROR;	
	}

	/**
	 * create a keyword by a given id
	 * 
	 * @param resource $db [ref] the database connection
	 * @param integer $id the databse identifier of the keyword
	 * @param integer $detailLevel an optional detaillevel, any combination of TLOBJ_O_GET_DETAIL Flags
	 * 
	 * @return tlKeyword returns the created keyword on success, or null else
	 */
	static public function getByID(&$db,$id,$detailLevel = self::TLOBJ_O_GET_DETAIL_FULL)
	{
		return tlDBObject::createObjectFromDB($db,$id,__CLASS__,tlKeyword::TLOBJ_O_SEARCH_BY_ID,$detailLevel);
	}

	/**
	 * create some keywords by given ids
	 * 
	 * @param resource $db [ref] the database connection
	 * @param array $ids the database identifiers of the keywords
	 * @param integer $detailLevel an optional detaillevel, any combination of TLOBJ_O_GET_DETAIL Flags
	 * 
	 * @return array returns the created keywords (tlKeyword) on success, or null else
	 */
	static public function getByIDs(&$db,$ids,$detailLevel = self::TLOBJ_O_GET_DETAIL_FULL)
	{
		return self::handleNotImplementedMethod(__FUNCTION__);
	}

	/**
	 * currently not implemented
	 * @TODO schlundus, comment if implemented
	 * 
	 * @param resource $db 
	 * @param string $whereClause
	 * @param string $column
	 * @param string $orderBy
	 * @param integer $detailLevel
	 * @return integer returns tl::E_NOT_IMPLEMENTED
	 */
	static public function getAll(&$db,$whereClause = null,$column = null,$orderBy = null,
	                              $detailLevel = self::TLOBJ_O_GET_DETAIL_FULL)
	{
		return self::handleNotImplementedMethod(__FUNCTION__);
	}

	//END interface iDBSerialization
	//@TODO schlundus, remove - for legacy purposes only 
	/*
	 * returns information about the keyword 
	 * 
	 * @return array the keyword information
	 */
	public function getInfo()
	{
		return array("id" => $this->dbID,"keyword" => $this->name,
			         "notes" => $this->notes,"testproject_id" => $this->testprojectID);
	}
	
	/**
	 * Checks a keyword against syntactic rules
	 *
	 * @param string $name the name of the keyword which should be checked
	 *
	 * @return integer returns tl::OK if the check was sucesssful, else errorcode
	 **/
	static public function checkKeywordName($name)
	{
		$result = tl::OK;
		if ($name != "")
		{
			//we shouldnt allow " and , in keywords any longer
			$dummy = null;
			if (preg_match("/(\"|,)/",$name,$dummy))
			{
				$result = self::E_NAMENOTALLOWED;
			}	
		}
		else
		{
			$result = self::E_NAMELENGTH;
        }
		return $result;
	}
	
	/**
	 * checks if a keyword for a certain testproject already exists in the database
	 * 
	 * @param resource $db [ref] the database connection
	 * @param string $name the name of the keyword
	 * @param integer $tprojectID the testprojectID 
	 * @param integer $kwID an additional keyword id which is excluded in the search 
	 * @return integer return tl::OK if the keyword is found, else tlKeyword::E_NAMEALREADYEXISTS 
	 */
	static public function doesKeywordExist(&$db,$name,$tprojectID,$kwID = null)
	{
		$result = tl::OK;
		$tables = tlObjectWithDB::getDBTables("keywords");
		
		$name = $db->prepare_string(strtoupper($name));
		$query = " SELECT id FROM {$tables['keywords']} " .
				 " WHERE UPPER(keyword) ='" . $name.
			     "' AND testproject_id = " . $tprojectID ;
		
		if ($kwID)
		{
			$query .= " AND id <> " .$kwID;
		}
		if ($db->fetchFirstRow($query))
		{
			$result = self::E_NAMEALREADYEXISTS;
		}
		return $result;
	}

	//BEGIN interface iSerializationToXML
	
	/**
	 * gets the format descriptor for XML
	 *
	 * @return string returns the XML Format description for Keyword/Export
	 */
	public function getFormatDescriptionForXML()
	{
		return "<keywords><keyword name=\"name\">Notes</keyword></keywords>";
	}

	/* 
	 * Writes the keyword to XML representation
	 *
	 * @param string $xml [ref] the generated XML Code will be appended here
	 * @param boolean $noHeader set this to true if no XML Header should be generated
	 */
	public function writeToXML(&$xml,$noHeader = false)
	{
		//@TODO schlundus, maybe written with SimpleXML ?
		$keywords = array($this->getInfo());
		$keywordElemTpl = '<keyword name="{{NAME}}"><notes><![CDATA['."\n||NOTES||\n]]>" . 
		                  '</notes></keyword>'."\n";
		$keywordInfo = array ("{{NAME}}" => "keyword","||NOTES||" => "notes");
		$xml .= exportDataToXML($keywords,"{{XMLCODE}}",$keywordElemTpl,$keywordInfo,$noHeader);
	}

	/* 
	 * Reads a keyword from a given XML representation
	 * @param string $xml the XML representation of a keyword
	 * 
	 * @return returns tl::OK on success, errorcode else
	 */
	public function readFromXML($xml)
	{
		$keyword = simplexml_load_string($xml);
		return $this->readFromSimpleXML($keyword);
	}

	/* 
	 * Reads a keyword from a simpleXML Object
	 * 
	 * @param object $keyword the SimpleXML Object which hold the keyword information
	 * 
	 * @return returns tl::OK on success, errorcode else
	 */
	public function readFromSimpleXML($keyword)
	{
		$this->name = NULL;
		$this->notes = NULL;
		
		if (!$keyword || $keyword->getName() != 'keyword')
		{
			return self::E_WRONGFORMAT;
		}
			
		$attributes = $keyword->attributes();
		if (!isset($attributes['name']))
		{
			return self::E_WRONGFORMAT;
		}
			
		$this->name = (string)$attributes['name'];
		if ($keyword->notes)
		{
			$this->notes = (string)$keyword->notes[0];
		}	
		return tl::OK;
	}
	//END interface iSerializationToXML
	
	//BEGIN interface iSerializationToCSV
	/* 
	 * gets the Format description for the CSV Import/Export Format
	 * 
	 * @return string the CSV Format 
	 */
	public function getFormatDescriptionForCSV()
	{
		return "keyword;notes";
	}

	/* Writes a keyword to CSV
	 * 
	 * @param string $csv the CSV representation of the keyword will be appended here
	 * @param string $delimiter an optional delimited for the CSV format
	 */
	public function writeToCSV(&$csv,$delimiter = ';')
	{
		$keyword = array($this->getInfo());
		$sKeys = array(	"keyword","notes" );
		$csv .= exportDataToCSV($keyword,$sKeys,$sKeys);
	}

	/* reads a keyword from a CSV string
	 * @param string $csv the csv string for the keyword
	 * @param string $delimiter an optional delimited for the CSV format
	 * 
	 * @return integer returns tl::OK on success, tl::ERROR else
	 */
	public function readFromCSV($csv,$delimiter = ';')
	{
		$data = explode($delimiter,$csv);
	 					
		$this->name = isset($data[0]) ? $data[0] : null;
		$this->notes = isset($data[1]) ? $data[1] : null;
		
		return sizeof($data) ? tl::OK : tl::ERROR;
	}
	//END interface iSerializationToCSV
}
?>