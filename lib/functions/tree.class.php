<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: tree.class.php,v $
 *
 * @version $Revision: 1.41 $
 * @modified $Date: 2008/02/24 17:54:59 $ by $Author: franciscom $
 * @author Francisco Mancardi
 *
 * 20080105 - franciscom - new method change_child_order()
 * 20071110 - franciscom - solved (auto)bug when refactoring get_path
 * 20071024 - franciscom - DTREE bug
 * 20070620 - franciscom - BUGID 903
 * 20061203 - franciscom - removing error due to undefined var in change_order_bulk()
 * 20061119 - franciscom - change_order_bulk() added abs() to order.
 * 20061008 - franciscom - ORDER BY node_order -> ORDER BY node_order,id
 * 20060729 - franciscom - fixed bug on new_node() after refactoring in version 1.20
 * 20060722 - franciscom - added possibility to create a new node with an specific ID
 * 20060511 - franciscom - changes in call to insert_id() due to problems with Postgres
 * 20060316 - franciscom - bug on get_path
*/

class tree 
{
	// configurable values - pseudoconstants
	var $node_types = array( 1 => 'testproject','testsuite',
	                              'testcase','tcversion','testplan',
	                              'requirement_spec','requirement');
	                              
	var $node_descr_id = array();
	
	var $node_tables = array('testproject' => 'testprojects',
                           'testsuite'   => 'testsuites',
                           'testplan'    => 'testplans',
                           'testcase'    => 'testcases',
                           'tcversion'   => 'tcversions',
                           'requirement_spec' =>'req_specs',
                           'requirement' =>'requirements'  );
 
  
  
	var $ROOT_NODE_TYPE_ID = 1;
	var $ROOT_NODE_PARENT_ID = NULL;
	
	var $db;
  var $obj_table = 'nodes_hierarchy';
  var $node_types_table = 'node_types';
    

  /*
    function: tree
              Class costructor
              
    args : db:reference to db object
    
    returns: new instance of a tree (manager)

  */
	function tree(&$db) 
	{
		$this->db = &$db;
		$this->node_descr_id = array_flip($this->node_types);
	}

  /*
    function: get_available_node_types
              get info from node_types table, regarding node types
              that can be used in a tree. 

    args : -
    
    returns: map
             key: description: single human friendly string describing node type
             value: numeric code used to identify a node type

  */

	function get_available_node_types() 
	{
		$sql = " SELECT * FROM node_types "; 
		$hash_ntypes = $this->db->fetchColumnsIntoMap($sql,"description","id");
		
		return $hash_ntypes;
	}

	/*
    function: new_root_node
              creates a new root node in the hierarchy table.
              root node is tree starting point.

    args : [name]: node name. default=''
    
    returns: node_id of the new node created

  */

	function new_root_node($name = '') 
	{
		$this->new_node(null,$this->ROOT_NODE_TYPE_ID,$name,1);
		return $this->db->insert_id($this->obj_table);
	}

	/*
    function: new_node
              creates a new node in the hierarchy table.
              root node is tree starting point.

    args : parent_id: node id of new node parent
           node_type_id: node type
           [name]: node name. default=''
           [node_order]= order on tree structure. default=0
           [node_id]= id to assign to new node, if you don't want
                      id bein created automatically.
                      default=0 -> id must be created automatically.
    
    returns: node_id of the new node created

  */
	function new_node($parent_id,$node_type_id,$name='',$node_order=0,$node_id=0) 
	{
		$sql = "INSERT INTO {$this->obj_table} " .
		       "(name,node_type_id,node_order";
		
		$values=" VALUES('" . $this->db->prepare_string($name). "'," .
		        " {$node_type_id},{$node_order}";
		if ($node_id)
		{
			$sql .= ",id";
			$values .= ",{$node_id}";
		}
		
		if(is_null($parent_id))
		{
			$sql .= ") {$values} )";
		}
		else
		{
			$sql .= ",parent_id) {$values},{$parent_id})";
    }

		$this->db->exec_query($sql);
		return ($this->db->insert_id($this->obj_table));
 	}

	/*
	get all node hierarchy info from hierarchy table
	returns: node_id of the new node created
	
	
	*/
	/*
    function: get_node_hierachy_info
              returns the row from nodes_hierarchy table that has
              node_id as id.
              
              get all node hierarchy info from hierarchy table

    args : node_id: node id.
    
    returns: array

  */
	function get_node_hierachy_info($node_id) 
	{
		$sql = "SELECT * FROM {$this->obj_table} WHERE id = {$node_id}";
		$result = $this->db->exec_query($sql);
		
		return $this->db->fetch_array($result);
	}

  /*
    function: get_subtree_list()
              get a string representing a list, where elements are separated
              by comma, with all nodes in tree starting on node_id.
              node is can be considered as root of subtree.
              
    args : node_id: root of subtree
    
    returns: list (string with nodes_id, using ',' as list separator).

  */
	function get_subtree_list($node_id)
	{
    $nodes=array();
  	$this->_get_subtree_list($node_id,$nodes);
  	$node_list=implode(',',$nodes);
    return($node_list);
  }
  
  
  /*
    function: _get_subtree_list()
              private function (name start with _), that using recursion
              get an array with all nodes in tree starting on node_id.
              node is can be considered as root of subtree.


    args : node_id: root of subtree
    
    returns: array with nodes_id

  */
	function _get_subtree_list($node_id,&$node_list)
	{
		$sql = "SELECT * from {$this->obj_table} WHERE parent_id = {$node_id}";
		$result = $this->db->exec_query($sql);
		
		if (!$result || !$this->db->num_rows($result))
			return;
		
		while($row = $this->db->fetch_array($result))
		{
			$node_list[] = $row['id'];
			$this->_get_subtree_list($row['id'],$node_list);	
		}
	}



  /*
    function: delete_subtree
              delete all element on tree structure that forms a subtree
              that has as root or starting point node_id.

    args : node_id: root of subtree
    
    returns: array with nodes_id

  */
	function delete_subtree($node_id)
	{
		$children = $this->get_subtree_list($node_id);
		$id2del = $node_id;
		if(strlen($children))
		{
			$id2del .= ",{$children}";	
		}
		$sql = "DELETE FROM {$this->obj_table} WHERE id IN ({$id2del})";
	
		$result = $this->db->exec_query($sql);
	}


  /*
    function: get_path
              get list of nodes to traverse when you want to move 
              from node A (node at level N) to node B (node at level M),
              where MUST BE ALLWAYS M < N, and remembering that level for root node is the minimun.
              This means path on tree backwards (to the upper levels).
              An array is used to represent list.
              Last array element contains data regarding Node A, first element (element with index 0) 
              is data regarding child of node B.
              What data is returned depends on value of optional argument 'format'.
              
              Attention:
              1 - destination node (node B) will be NOT INCLUDED in result.
              2 - This is refactoring of original get_path method.

    args : node_id: start of path
           [to_node_id]: destination node. default null -> path to tree root.
           [format]: default 'full' 
                     defines type of elements of result array.
                     
                     format='full'
                     Element is a map with following keys:
                     id
                     parent_id
                     node_type_id
                     node_order
                     node_table
                     name
                     
                     Example
                     Is tree is :
                                
                              null 
                                \
                               id=1   <--- Tree Root
                                 |
                                 + ------+
                               /   \      \
                            id=9   id=2   id=8
                                    \
                                     id=3
                                      \
                                       id=4     
                    
                    
                    get_path(4), returns:
                          
                    (
                     [0] => Array([id] => 2
                                  [parent_id] => 1
                                  [node_type_id] => 2
                                  [node_order] => 1
                                  [node_table] => testsuites
                                  [name] => TS1)
        
                     [1] => Array([id] => 3
                                  [parent_id] => 2
                                  [node_type_id] => 2
                                  [node_order] => 1
                                  [node_table] => testsuites
                                  [name] => TS2)
        
                     [2] => Array([id] => 4
                                  [parent_id] => 3
                                  [node_type_id] => 3
                                  [node_order] => 0
                                  [node_table] => testcases
                                  [name] => TC1)
                    )
                  
                    
                    
                    format='simple'
                    every element is a number containing parent id
                    For the above example result will be:
                    (
                     [0] => 1
                     [1] => 2
                     [2] => 3
                    )

    returns: array

  */
	function get_path($node_id,$to_node_id = null,$format = 'full') 
	{
		$the_path = array();
		$this->_get_path($node_id,$the_path,$to_node_id,$format); 
		
		if( !is_null($the_path) && count($the_path) > 0 )
		{
		  $the_path=array_reverse($the_path);  
		}
		return $the_path;
	}


/*
  function: _get_path
            This is refactoring of original get_path method.
            Attention:
            returns node in inverse order, that was done for original get_path

  args : node_id: start of path
         node_list: passed by reference, to build the result.
         [to_node_id]: destination node. default null -> path to tree root.
         [format]: default 'full' 
  
  returns: array
*/
function _get_path($node_id,&$node_list,$to_node_id=null,$format='full') 
{
	
// look up the parent of this node
 $sql = " SELECT * from {$this->obj_table} 
          WHERE id = {$node_id} ";
 
 $result = $this->db->exec_query($sql);
 
 if( $this->db->num_rows($result) == 0 )
 {
    $node_list=null;
    return; 	
 }
  
 while ( $row = $this->db->fetch_array($result) )
 {
   
   // only continue if this $node isn't the root node
   // (that's the node with no parent)
   if ($row['parent_id'] != '' && $row['id'] != $to_node_id) 
   {
      // Getting data from the node specific table
      $node_table = $this->node_tables[$this->node_types[$row['node_type_id']]];
      
   		// the last part of the path to $node, is the name
   		// of the parent of $node
   		if( $format == "full" )
   		{
      		$node_list[] = array('id'        => $row['id'],
          		                 'parent_id' => $row['parent_id'],
              		             'node_type_id' => $row['node_type_id'],
                  		         'node_order' => $row['node_order'],
                      		     'node_table' => $node_table,
                          		 'name' => $row['name'] );
      }
      else
      {
      		$node_list[$row['parent_id']] = $row['parent_id'];
      }
			
      // we should add the path to the parent of this node to the path
      $this->_get_path($row['parent_id'],$node_list,$to_node_id,$format);
   }
 }
}




/*
  function: change_parent
            change node parent, using this method you implement move operation.

  args : node_id: node that needs to changed
         parent_id: new parent
  
  returns: 1 -> operation OK
*/
function change_parent($node_id, $parent_id) 
{
  $sql = "UPDATE nodes_hierarchy SET parent_id = {$parent_id} WHERE id = {$node_id}";
  $result = $this->db->exec_query($sql);
 
  return $result ? 1 : 0;
}
 
 
// 20061008 - franciscom - added ID in order by clause
// 
/*
  function: get_children
            get nodes that have id as parent node.
            Children can be filtering according to node type.
            
  args : id: node 
         [exclude_node_types]: map 
                               key: verbose description of node type to exclude.
                                    see get_available_node_types.
                               value: anything is ok
  
  returns: array of maps that contain children nodes.
           map structure:
           id 
           name
           parent_id
           node_type_id
           node_order
           node_table
          
           
*/

function get_children($id,$exclude_node_types=null) 
{
  $sql = " SELECT * from {$this->obj_table}
          WHERE parent_id = {$id} ORDER BY node_order,id";

  $node_list=array();  
  $result = $this->db->exec_query($sql);
 
  if( $this->db->num_rows($result) == 0 )
  {
    return(null); 	
  }

  while ( $row = $this->db->fetch_array($result) )
  {
    // ----------------------------------------------------------------------------
    $node_table = $this->node_tables[$this->node_types[$row['node_type_id']]];

    if( !isset($exclude_node_types[$this->node_types[$row['node_type_id']]]))
    {
      $node_list[] = array('id'        => $row['id'],
                           'parent_id' => $row['parent_id'],
                           'node_type_id' => $row['node_type_id'],
                           'node_order' => $row['node_order'],
                           'node_table' => $node_table,
                           'name' => $row['name']);
  	}
  }
  return ($node_list);
}

 
/*
  function: change_order_bulk
            change order for all nodes is present in nodes array.
            Order of node in tree, is set to position node has in nodes array.

  args :
         nodes: array where value is node_id. Node order = node position on array
   
  returns: -

*/
function change_order_bulk($nodes) 
{
	foreach($nodes as $order => $node_id)
	{
		$order = abs(intval($order));
		$node_id = intval($node_id);
	  $sql = "UPDATE {$this->obj_table} SET node_order = {$order}
	      	    WHERE id = {$node_id}";
	  $result = $this->db->exec_query($sql);
	}
}


/*
  function: change_child_order
            will change order of children of parent id, to position
            choosen node on top or bottom of children.             

  args:
        parent_id: node used as root of a tree.
        node_id: node which we want to reposition
        $top_bottom: possible values 'top', 'bottom'
        [exclude_node_types]: map 
                              key: verbose description of node type to exclude.
                                   see get_available_node_types.
                              value: anything is ok

   
  returns: -

*/
function change_child_order($parent_id,$node_id,$top_bottom,$exclude_node_types=null)
{
    $node_type_filter='';
    if( !is_null($exclude_node_types) )
    {
       $types=implode("','",array_keys($exclude_node_types));  
       $node_type_filter=" AND NT.description NOT IN ('{$types}') ";
    }
    
    $sql = " SELECT NH.id, NH.node_order, NH.name " .
           " FROM {$this->obj_table} NH, {$this->node_types_table} NT " .
           " WHERE NH.node_type_id=NT.id " .
           " AND NH.parent_id = {$parent_id} AND NH.id <> {$node_id} " . 
           $node_type_filter .
           " ORDER BY NH.node_order,NH.id";
    $children=$this->db->get_recordset($sql);
    
    switch ($top_bottom)
    {
        case 'top':
        $no[]=$node_id;
        if( !is_null($children) )
        {
            foreach($children as $key => $value)
            {
              $no[]=$value['id'];     
            }
        }
        break;
          
        case 'bottom':  
        $new_order=$this->getBottomOrder($parent_id)+1;
        $no[$new_order]=$node_id;
        break;
    }
    $this->change_order_bulk($no);    
} 

/*
  function: getBottomOrder
            given a node id to be used as parent, returns  the max(node_order) from the children nodes.
            We consider this bottom order.

  args: parentID: 
  
  returns: order

*/
function getBottomOrder($parentID)
{
    $sql="SELECT MAX(node_order) AS TOP_ORDER" .
         " FROM {$this->obj_table} " . 
         " WHERE parent_id={$parentID} " .
         " GROUP BY parent_id";
    $rs=$this->db->get_recordset($sql);
    
    return $rs[0]['TOP_ORDER'];     
}




/*
  function: get_subtree
            Giving a node_id, get the nodes that forma s subtree that 
            has node_id as root or starting point.

            Is possible to exclude:
            branches that has as staring node, node of certain types.
            children of some node types.
            full branches.
            

  args :
        [exclude_node_types]: map/hash. 
                              default: null -> no exclusion filter will be applied.
                              Branches starting with nodes of type detailed, will not be
                              visited => no information will be returned.
                              key: verbose description of node type to exclude.
                                   (see get_available_node_types).
                              value: can be any value, because is not used,anyway is suggested 
                                     to use 'exclude_me' as value.
                              
                              Example:
                              array('testplan' => 'exclude_me')
                              Node of type tesplan, will be excluded. 
                             
                             
        
        [exclude_children_of]: map/hash
                              default: null -> no exclusion filter will be applied.
                              When traversing tree if the type of a node child, of node under analisys,
                              is contained in this map, traversing of branch starting with this child node
                              will not be done.
                              key: verbose description of node type to exclude.
                                   (see get_available_node_types).
                              value: can be any value, because is not used,anyway is suggested 
                                     to use 'exclude_my_children' as value.
                              
                              Example:        
                              array('testcase' => 'exclude_my_children')                               
                              Children of testcase nodes, (tcversion nodes) will be EXCLUDED.         
        
        [exclude_branches]: map/hash. 
                            default: null -> no exclusion filter will be applied.
                            key: node id.
                            value: anything is ok.
                            
                            When traversing tree branches that have these node is, will
                            not be visited => no information will be retrieved.
        
        
        [and_not_in_clause]: sql filter to include in sql sentence used to retrieve nodes.
                             default: null -> no action taken.
                              
        [bRecursive]: changes structure of returned structure.
                      default: false -> a flat array will be generated
                               true  -> a map with recursive structure will be generated.
                      
                      false returns array, every element is a map with following keys:
                      
                      id
                      parent_id
                      node_type_id
                      node_order
                      node_table
                      name
                      
                      
                      true returns a map, with only one element
                      key: childNodes.
                      value: array, that represents a tree branch.
                             Array elements are maps with following keys:
                      
                             id
                             parent_id
                             node_type_id
                             node_order
                             node_table
                             name
                             childNodes -> (array)
                      
          
  returns: array or map

*/
function get_subtree($node_id,$exclude_node_types=null,
                              $exclude_children_of=null,
                              $exclude_branches=null, $and_not_in_clause='',$bRecursive = false)
{
 		$the_subtree=array();
 		
 		// Generate NOT IN CLAUSE to exclude some node types
 		$not_in_clause='';
 	  if( !is_null($exclude_node_types) )
  	{
  			$exclude=array();
    		foreach($exclude_node_types as $the_key => $elem)
    		{
      			$exclude[]= $this->node_descr_id[$the_key];
    		}
    		$not_in_clause = " AND node_type_id NOT IN (" . implode(",",$exclude) . ")";
  	}
    
	if ($bRecursive)
	    $this->_get_subtree_rec($node_id,$the_subtree,$not_in_clause,
	                                          $exclude_children_of,
	                                          $exclude_branches);
	else
	    $this->_get_subtree($node_id,$the_subtree,$not_in_clause,
	                                          $exclude_children_of,
	                                          $exclude_branches);


  return ($the_subtree);
}


// 20061008 - franciscom - added ID in order by clause
// 
// 20060312 - franciscom
// Changed and improved following some Andreas Morsing advice.
//
// I would like this method will be have PRIVate scope, but seems not possible in PHP4
// that's why I've prefixed with _
//
function _get_subtree($node_id,&$node_list,$and_not_in_clause='',
                                           $exclude_children_of=null,
                                           $exclude_branches=null)
{
   
    //if( is_null($exclude_branches) )
    //  echo "NO EXCLUTION REQUIRED <br>";

  	$sql = " SELECT * from nodes_hierarchy
    	       WHERE parent_id = {$node_id}  {$and_not_in_clause} ORDER BY node_order,id";
 
    $result = $this->db->exec_query($sql);
  
    if( $this->db->num_rows($result) == 0 )
    {
  	   return; 	
    }
  
    while ( $row = $this->db->fetch_array($result) )
    {
      if( !isset($exclude_branches[$row['id']]) )
      {  
          
        	$node_table = $this->node_tables[$this->node_types[$row['node_type_id']]];
          $node_list[] = array('id'        => $row['id'],
                               'parent_id' => $row['parent_id'],
                               'node_type_id' => $row['node_type_id'],
                               'node_order' => $row['node_order'],
                               'node_table' => $node_table,
                               'name' => $row['name']);
          
          // Basically we use this because:
          // 1. Sometimes we don't want the children if the parent is a testcase,
          //    due to the version management
          //
          // 2. Sometime we want to exclude all descendants (branch) of a node.
          //
          // [franciscom]: 
          // I think ( but I have no figures to backup my thoughts) doing this check and 
          // avoiding the function call is better that passing a condition that will result
          // in a null result set.
          //
          //
          if( !isset($exclude_children_of[$this->node_types[$row['node_type_id']]]) && 
              !isset($exclude_branches[$row['id']])
            )
          {
        	  $this->_get_subtree($row['id'],$node_list,
        	                                 $and_not_in_clause,
        	                                 $exclude_children_of,
        	                                 $exclude_branches);	
         	  
        	}
    	}
  	}
} // function end
 
 
// 20061008 - franciscom - added ID in order by clause
// 
function _get_subtree_rec($node_id,&$pnode,$and_not_in_clause = '',
                                           $exclude_children_of = null,
                                           $exclude_branches = null)
{
  	$sql = " SELECT * from {$this->obj_table} WHERE parent_id = {$node_id} {$and_not_in_clause}" .
		       " ORDER BY node_order,id";
 
    $result = $this->db->exec_query($sql);
    while($row = $this->db->fetch_array($result))
    {
  		$rowID = $row['id'];
  		$nodeTypeID = $row['node_type_id'];
  		$nodeType = $this->node_types[$nodeTypeID];
  		
  		if(!isset($exclude_branches[$rowID]))
  		{  
  			$node_table = $this->node_tables[$nodeType];
  			$node =  array('id' => $rowID,
                       'parent_id' => $row['parent_id'],
                       'node_type_id' => $nodeTypeID,
                       'node_order' => $row['node_order'],
                       'node_table' => $node_table,
                       'name' => $row['name'],
  							       'childNodes' => null);
            
        // Basically we use this because:
        // 1. Sometimes we don't want the children if the parent is a testcase,
        //    due to the version management
        //
        // 2. Sometime we want to exclude all descendants (branch) of a node.
        //
        // [franciscom]: 
        // I think ( but I have no figures to backup my thoughts) doing this check and 
        // avoiding the function call is better that passing a condition that will result
        // in a null result set.
        //
        //
        if(!isset($exclude_children_of[$nodeType]) && 
           !isset($exclude_branches[$rowID]) )
  			{
  				$this->_get_subtree_rec($rowID,$node,
                                  $and_not_in_clause,
                                  $exclude_children_of,
                                  $exclude_branches);	
        }
  			
  			$pnode['childNodes'][] = $node;
  			
  		} // if(!isset($exclude_branches[$rowID]))
  	} //while
}
 
}// end class
?>
