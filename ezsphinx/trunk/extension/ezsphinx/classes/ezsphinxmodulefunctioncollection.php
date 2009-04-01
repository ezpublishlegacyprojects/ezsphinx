<?php
//
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Sphinx
// SOFTWARE RELEASE: 0.1 beta
// COPYRIGHT NOTICE: Copyright (C) 2009 JSC Coral solutions
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file ezsphinxmodulefunctioncollection.php
*/


/**
 * The ezSphinxModuleFunctionCollection contains methods for functions defined
 * in the module ezsphinx.
 */
class ezSphinxModuleFunctionCollection
{
    /**
     * Constructor
     */
    function ezSphinxModuleFunctionCollection()
    {
    }

    
     /**
     * Search function
     *
     * @param string Query string
     * @param int Offset
     * @param int Limit
     * @param array Filter parameters
     * @param array Sort by parameters
     * @param mixed Content class ID or list of content class IDs   
     * @param array list of subtree limitation node IDs
     * @param bool ingnore visibility
     * @param bool as objects
     * @param string indexName can be separated with ";", default used if not set
     * @param int timestamp of publish array(startTimeStamp, endTimeStamp)
     * @param int publishDate of publish 1,2,3,4,5
     * @param string matchType 
     * @param class attribute id or id's array to search text in 
     * @param field weights, class attribute id and weight o array of attributes
     *
     * @return array Search result
     */
    public function search( $query, $offset = 0, $limit = 10, $filters = null, 
                            $sortBy = null, $classID = null, $sectionID = null,
                            $subtreeArray = null, $ignoreVisibility = false,
                            $asObjects = true, $indexName = null, $searchTimestamp = null, $publishDate = null, $mathType = 'SPH_MATCH_ANY', 
                            $classAttributeID = null, $fieldWeight = null, $parent_node_id = null, $limitation = null )
    {
   
        $sphinxSearch = new eZSphinx();
        $params = array( 'SearchOffset' => $offset,
                         'SearchLimit' => $limit,                         
                         'SortBy' => $sortBy,
                         'Filter' => $filters,
                         'SearchContentClassID' => $classID,
                         'SearchSectionID' => $sectionID,
                         'SearchSubTreeArray' => $subtreeArray,
                         'IgnoreVisibility' => $ignoreVisibility,
                         'AsObjects' => $asObjects,                      
                         'IndexName' => $indexName,
                         'SearchDate' => $publishDate,
                         'SearchTimestamp' => $searchTimestamp,
                         'MatchType' => $mathType,
                         'SearchClassAttributeID' => $classAttributeID,
                         'FieldWeight' => $fieldWeight,
                         'ParentNodeID' => $parent_node_id,
                         'Limitation' => $limitation
                         );
        return array( 'result' => $sphinxSearch->search( $query, $params ) );
    }

    
}

?>
