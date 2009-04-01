<?php
//
// Definition of eZSphinx class
//
// Created on: <27-Jul-2009 13:09:57 bf>
//
// COPYRIGHT NOTICE: Copyright (C) 2009 Coral Solutions AS
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

/*!
  \class eZSphinx ezsphinx.php

*/



class eZSphinx
{
    /*!        
     */
    function eZSphinx()
    {
        $this->SphinxINI = eZINI::instance( 'sphinx.ini' );
        $this->SphinxServerHost = $this->SphinxINI->variable( 'eZSphinxSettings', 'ServerHost' );
        $this->SphinxServerPort = (int)$this->SphinxINI->variable( 'eZSphinxSettings', 'Port' );
        $this->SphinxIndexName = $this->SphinxINI->variable( 'eZSphinxSettings', 'DefaultIndexName' );
    }

    /*!
     Adds an object to the search database.
    */
    function addObject( $contentObject, $uri )
    {
        $contentObjectID = $contentObject->attribute( 'id' );
        $currentVersion = $contentObject->currentVersion();

        if ( !$currentVersion )
        {
            $errCurrentVersion = $contentObject->attribute( 'current_version');
            require_once( "lib/ezutils/classes/ezdebug.php" );
            eZDebug::writeError( "Failed to fetch \"current version\" ({$errCurrentVersion})" .
                                 " of content object (ID: {$contentObjectID})", 'eZSphinx' );
            return;
        }

        $indexArray = array();
        $indexArrayOnlyWords = array();

        $wordCount = 0;
        $placement = 0;
        $previousWord = '';
		$textIndex = array();
		$ObjectLanguagesIndex = array();
		
		// Get global object values
        $mainNode = $contentObject->attribute( 'main_node' );
        if ( !$mainNode )
        {
            eZDebug::writeError( 'Unable to fetch main node for object: ' . $contentObject->attribute( 'id' ), 'eZSolr::addObject()' );
            return;
        }
        $pathArray = $mainNode->attribute( 'path_array' );        
        $mainNodeID = $mainNode->attribute( 'node_id' );
        		
        eZContentObject::recursionProtectionStart();
        
        foreach( $currentVersion->translationList( false, false ) as $languageCode )
        {
        
            $textIndex = array();
	        foreach ( $currentVersion->contentObjectAttributes($languageCode) as $attribute )
	        {
                $metaData = array();
	            $textParts = array();
	            $MetaDataParts = array();
	            $classAttribute = $attribute->contentClassAttribute();
	            $text = '';
	            $integerValue = 0;
	            
	                
	                $contentClassAttribute = $attribute->attribute( 'contentclass_attribute' );
	                
	                // Indexing for custom types, in the future i will add more custom attributes :)
	                switch ( $contentClassAttribute->attribute( 'data_type_string' ) )
                    {
                        // Usefull then searching related objects
                        case 'ezobjectrelation' :                   
                        {     
                            if ( $classAttribute->attribute( "is_searchable" ) == 1 ) {               
                                    //Store related object ID in integer column                  
                                    $MetaDataParts[]=$attribute->attribute('data_int');
	                           }
                        } break;
                        
                        //Always index price, it's wrong because of discounts. But where is no discounts perhaps it will be usefull :)
                        case 'ezprice' :                   
                        {                    
                            $MetaDataParts[] = (int)($attribute->attribute('data_float')*100);
                        } break;
            
                        default:
                        {
                            if ( $classAttribute->attribute( "is_searchable" ) == 1 ) { 
                                $metaData = $attribute->metaData();
                	            if ( is_array( $metaData ) )
                                {         
                                    foreach ($metaData as $value)
                                    {
                                        $MetaDataParts[] = implode(' ',$value);                        
                                    }       
                                }
                                else
                                {
                                    $MetaDataParts[] = $metaData;           
                                }
                            }
                        } break;
                    }
	                
                    
                    $text = eZSearchEngine::normalizeText( strip_tags( implode(' ',$MetaDataParts )), true );
                   
                    if ( is_numeric( trim( $text ) ) )
                    {
                        $integerValue = (int) $text;
                        $text = ''; // Avoid inserting integer to search column
                    }
                    else
                    {
                        $integerValue = 0;
                    }   
	            
	            
	            //Each indexed attribute get's it's position.  
	            $textIndex[] = array('text' => trim($text), 'integer' => $integerValue );
	        }
	     
            $ObjectLanguagesIndex[] = array('attributes' => $textIndex,'language_code' => abs(crc32($languageCode)));
        }
    
        eZContentObject::recursionProtectionEnd();

        $db = eZDB::instance();
        $db->begin();        
        $this->indexWords($contentObject, $ObjectLanguagesIndex, $pathArray, $mainNodeID);      
        $db->commit();      
    }


    /**
     * @param contentObject
     * @param array of data to be inserted
     * 
     * @return null
    */
    function indexWords( $contentObject, $ObjectLanguagesIndex, $pathArray, $mainNodeID)
    {
        $db = eZDB::instance();
        $insertQuery = array();     
        foreach ($ObjectLanguagesIndex as $ObjectLanuageVersion)
        {
        
            $InsertAttributes = array();
            $InsertAttributes['contentobject_id'] = $contentObject->attribute( 'id' );
            $InsertAttributes['language_code'] = '\''.addslashes($ObjectLanuageVersion['language_code']).'\'';
            
            $InsertAttributesText = array(); 
            $InsertAttributesInteger = array(); 
            $InsertAttributesLanguageCode = array(); 
            
            
            foreach ($ObjectLanuageVersion['attributes'] as $position => $textSearch)
            {
            	$InsertAttributesText['attr_srch_pos'.$position] = '\''.addslashes($textSearch['text']).'\'';
            	$InsertAttributesInteger['attr_srch_int_pos'.$position] = '\''.addslashes($textSearch['integer']).'\'';            	
            }
            
            // Max 14 attributes to index class
            if ((count($InsertAttributesText)) > 14) 
            {
            	$InsertAttributesText = array_slice($InsertAttributesText,0,14);
            	$InsertAttributesInteger = array_slice($InsertAttributesInteger,0,14);
            }
        
            $InsertArr = array_merge($InsertAttributes,$InsertAttributesInteger,$InsertAttributesText);
                    
            $insertQuery[] = "INSERT INTO ezsphinx (".implode(',',array_keys($InsertArr)).") VALUES (".implode(',',$InsertArr).")";
        
        }
                    
        $db->begin();
        
        $IndexID = array();
        //Insert object languages
        foreach ($insertQuery as $query)
        {
            $db->query($query);            
            $query = "SELECT MAX(id) as id FROM ezsphinx";	
    	    $resultArray = $db->arrayQuery( $query,array('column' => 'id') );
    	    $IndexID[]=$resultArray[0];            
        }
        
        //Each language object different relation index
        foreach ($IndexID as $index)
        {
            //Insert path nodes for multi field indexing
            foreach ($pathArray as $nodeID)
            {       
            	$db->query("REPLACE INTO ezsphinx_pathnodes (id,nodepath_id) VALUES ({$index},{$nodeID})");
            }
        }
             	
        $db->commit();
     }

    /**
     * @param contentObject
     * 
     * 
     * */
    function removeObject( $contentObject )
    {
        $db = eZDB::instance();
        $objectID = $contentObject->attribute( "id" );
        $nodeID = $contentObject->attribute( "main_node_id" );
        $doDelete = false;
        $db->begin();
      
        $cnt = $db->arrayQuery( "SELECT COUNT( * ) AS cnt FROM ezsphinx WHERE contentobject_id='$objectID'" );
        if ( $cnt[0]['cnt'] > 0 )
        {                
            $doDelete = true;
        }
       
        if ( $doDelete )
        {
            // Live deletion of documents
            $cl = new SphinxClient();
	  	    $cl->SetServer( $this->SphinxServerHost, $this->SphinxServerPort );
	  	    	
	  	    $indexToUpdate = $db->arrayQuery( "SELECT id FROM ezsphinx WHERE contentobject_id='$objectID'");
	  	    $DeleteIndex = array();	  	    
	  	    foreach ($indexToUpdate as $docID)
	  	    {
	  	        $DeleteIndex[$docID['id']] = array(1);
	  	    }	  	    
	  	   	/**
	  	   	 * Live index update, but then merging original index is not change.... Even i was set is_deleted flag...
	  	   	 * */  	    
	  	    $cl->UpdateAttributes ( $this->SphinxIndexName , array("is_deleted"), $DeleteIndex );
	  	      	     
            $db->query( "DELETE ezsphinx_pathnodes.* FROM ezsphinx_pathnodes INNER JOIN ezsphinx ON ezsphinx.id = ezsphinx_pathnodes.id WHERE ezsphinx.contentobject_id ='$objectID' " );
            $db->query( "DELETE FROM ezsearch_word WHERE object_count='0'" );
            $db->query( "DELETE FROM ezsphinx WHERE contentobject_id ='$objectID'" );  
         
        }
        $db->commit();
    }

    
    /*!
     Runs a query to the search engine.
    */
    function search( $searchText, $params = array(), $searchTypes = array() )
    {	
    
        $cl = new SphinxClient();
	  	$cl->SetServer( $this->SphinxServerHost, $this->SphinxServerPort );
	 	
	  	// Match mode
	  	$matchModes = array(
	  		'SPH_MATCH_ANY'        => SPH_MATCH_ANY,
	  		'SPH_MATCH_ALL'        => SPH_MATCH_ALL,
	  		'SPH_MATCH_PHRASE'     => SPH_MATCH_PHRASE,
	  		'SPH_MATCH_BOOLEAN'    => SPH_MATCH_BOOLEAN,
	  		'SPH_MATCH_EXTENDED'   => SPH_MATCH_EXTENDED,
	  		'SPH_MATCH_FULLSCAN'   => SPH_MATCH_FULLSCAN,
	  		'SPH_MATCH_EXTENDED2'  => SPH_MATCH_EXTENDED2,
	  	);	  	
	  	$cl->SetMatchMode((isset($params['MatchType']) and key_exists($params['MatchType'],$matchModes)) ? $matchModes[$params['MatchType']] : SPH_MATCH_ANY);
	  	
	 	 
	  	// Perhaps anyone have an idea how to implement this type checking in Sphinx ?
	  	// (ezcontentobject.section_id in (1)) OR (ezcontentobject.contentclass_id in (1, 19, 20, 27, 29, 30, 31, 32, 33, 34, 40, 44, 47, 48, 50, 51, 52, 57, 59, 61) AND ezcontentobject.section_id in (3))
	  	// At this moment it can be implemented directly in sphinx configuration query.
	  	/*$limitation = false;
        if ( isset( $params['Limitation'] ) )
        {
            $limitation = $params['Limitation'];
        }
        $limitationList = eZContentObjectTreeNode::getLimitationList( $limitation );
        $sqlPermissionChecking = eZContentObjectTreeNode::createPermissionCheckingSQL( $limitationList );*/
         
	  	
	  	// Set limit, offset	 	
		$cl->SetLimits((int)$params['SearchOffset'],(int)$params['SearchLimit']);
			  
		// Language filter, eZFind copied and changed a little bit :D
		$ini = eZINI::instance();
        $languages = $ini->variable( 'RegionalSettings', 'SiteLanguageList' );
        $mainLanguage = $languages[0];     
        $cl->SetFilter( 'language_code',array(abs(crc32($mainLanguage))));
             
        // Fetch only not deleted records
		$cl->SetFilter( 'is_deleted',array(0));
		
			
	  	// Build section filter
	  	$searchSectionID = $params['SearchSectionID'];
	  	if ( is_numeric( $searchSectionID ) and  $searchSectionID > 0 )
        {    
            $cl->SetFilter( 'section_id', array( (int)$searchSectionID ) );
        }
        else if ( is_array( $searchSectionID ) )
        {
        	$cl->SetFilter( 'section_id',$searchSectionID);
        }
              
        // Build class filter  
        $searchContentClassID = isset($params['SearchContentClassID']) ? $params['SearchContentClassID'] : 0 ;          
        if ( is_numeric( $searchContentClassID ) and $searchContentClassID > 0 )
        {
        	 $cl->SetFilter( 'contentclass_id', array((int)$searchContentClassID));
        }
        else if ( is_array( $searchContentClassID ) )
        {           
            $cl->SetFilter( 'contentclass_id',$searchContentClassID);
        }
        
        // Build parent node filter
        $searchParentNodeID = isset($params['ParentNodeID']) ? $params['ParentNodeID'] : 0 ;          
        if ( is_numeric( $searchParentNodeID ) and $searchParentNodeID > 0 )
        {
        	 $cl->SetFilter( 'parent_node_id', array((int)$searchParentNodeID));
        }
        else if ( is_array( $searchParentNodeID ) )
        {           
            $cl->SetFilter( 'parent_node_id',$searchParentNodeID);
        }
        
        // Build subtree filter
       $searchSubtreeNodeID = isset($params['SearchSubTreeArray']) ? $params['SearchSubTreeArray'] : 0 ;          
       if ( is_numeric( $searchSubtreeNodeID ) and $searchSubtreeNodeID > 0 )
       {
       	 $cl->SetFilter( 'pathnodes', array((int)$searchSubtreeNodeID));
       }
       else if ( is_array( $searchSubtreeNodeID ) and count( $searchSubtreeNodeID ) )
       {           
          $cl->SetFilter( 'pathnodes',$searchSubtreeNodeID);
       }
       
               
       // Visibility check
       $ignoreVisibility = $params['IgnoreVisibility'] == 'true' ? true : false;
       if (!$ignoreVisibility)
       {
       		$cl->SetFilter( 'is_invisible',array(0));
       }   
        
       // Publish date,timestamp date filter, borrowed from ezsearchengine plugin. :)   
       if ( isset( $params['SearchDate'] ) )
        	$searchDate = $params['SearchDate'];
	   else
		    $searchDate = -1;
		
	   if ( isset( $params['SearchTimestamp'] ) )
		    $searchTimestamp = $params['SearchTimestamp'];
	   else
		    $searchTimestamp = false;
          		     
       
       if ( ( is_numeric( $searchDate ) and  $searchDate > 0 ) or
                 $searchTimestamp )
        {
            $date = new eZDateTime();
            $timestamp = $date->timeStamp();
            $day = $date->attribute('day');
            $month = $date->attribute('month');
            $year = $date->attribute('year');
            $publishedDateStop = false;
            if ( $searchTimestamp )
            {
                if ( is_array( $searchTimestamp ) )
                {
                    $publishedDate = $searchTimestamp[0];
                    $publishedDateStop = $searchTimestamp[1];
                }
                else
                    $publishedDate = $searchTimestamp;
            }
            else
            {
                switch ( $searchDate )
                {
                    case 1:
                    {
                        $adjustment = 24*60*60; //seconds for one day
                        $publishedDate = $timestamp - $adjustment;
                    } break;
                    case 2:
                    {
                        $adjustment = 7*24*60*60; //seconds for one week
                        $publishedDate = $timestamp - $adjustment;
                    } break;
                    case 3:
                    {
                        $adjustment = 31*24*60*60; //seconds for one month
                        $publishedDate = $timestamp - $adjustment;
                    } break;
                    case 4:
                    {
                        $adjustment = 3*31*24*60*60; //seconds for three months
                        $publishedDate = $timestamp - $adjustment;
                    } break;
                    case 5:
                    {
                        $adjustment = 365*24*60*60; //seconds for one year
                        $publishedDate = $timestamp - $adjustment;
                    } break;
                    default:
                    {
                        $publishedDate = $date->timeStamp();
                    }
                }
            }
            
            if ($publishedDateStop)
            {
            	$cl->SetFilterRange('published', $publishedDate, $publishedDateStop); // Range type
            } else {
            	$cl->SetFilterRange('published', 0, $publishedDate, true); // > type
            }
        }
       
        if ( isset( $params['SortBy'] ) )
            $sortArray = $params['SortBy'];
        else
            $sortArray = array();                           
             
        // Build sort params      
       	$sortString = $this->buildSort($sortArray);       	
       	if ($sortString != '')
       	{
       		$cl->SetSortMode(SPH_SORT_EXTENDED, $sortString); // During sorting we set extended sort mode
       	}
        
       
       	
        // Filter , Partly based on ezpersistenobject eZPersistentObject::conditionTextByRow() method   	
		$fitlerRanges = isset($params['Filter']) ? $params['Filter'] : null;
		if ( is_array( $fitlerRanges ) and
             count( $fitlerRanges ) > 0 )
        {
               	
        	foreach ($fitlerRanges as $id => $cond)
        	{        		
        		if ( is_array( $cond ) )
                    {
                        if ( is_array( $cond[0] ) ) // = operator behaviour
                        {
                        	$cl->SetFilter( 'attr_srch_int_pos'.$this->getPositionClassAttribute($id) , (int)$cond[0] );                            
                        }
                        else if ( is_array( $cond[1] ) ) // Betweeen range
                        {                    
                            $range = $cond[1];
                            $cl->SetFilterRange('attr_srch_int_pos'.$this->getPositionClassAttribute($id), (int)$range[0], (int)$range[1], $cond[0] == 'true' );                       	
                        }
                        else
                        {
                          switch ( $cond[0] )
                          {
                              case '>=':                             
                              case '>':                              
                                  {
                                  	  $cl->SetFilterRange( 'attr_srch_int_pos'.$this->getPositionClassAttribute($id) ,0, (int)$cond[1], true );
                                      
                                  } break;
                                  
                             case '<=':                             
                             case '<':                              
                                  {
                                  	  $cl->SetFilterRange( 'attr_srch_int_pos'.$this->getPositionClassAttribute($id),0, (int)$cond[1], false );
                                      
                                  } break;
                                  
                                  
                              default:
                                  {
                                      eZDebug::writeError( "Conditional operator '$cond[0]' is not supported.",'eZSphinx::search()' );
                                  } break;
                          }

                        }
                    } else {
                    	$cl->SetFilter( 'attr_srch_int_pos'.$this->getPositionClassAttribute($id) , array($cond) );                    	
                    }
        	}
        }
		
        // Sphinx field weightning
        if (isset($params['FieldWeight']) and is_array($params['FieldWeight']) and count($params['FieldWeight']) > 0)
        {
        	$tmpFields = array();
        	foreach ($params['FieldWeight'] as $classAttributeID => $weight)
        	{
        		$tmpFields['attr_srch_pos'.$this->getPositionClassAttribute($classAttributeID)] = $weight;
        	}        
        	$cl->SetFieldWeights($tmpFields);
        	unset($tmpFields);
        }
        
                   
        // this will work only if SPH_MATCH_EXTENDED mode is set
        $AppendExtendQuery = '';
        if (isset($params['MatchType']) and key_exists($params['MatchType'],$matchModes) and $matchModes[$params['MatchType']] == SPH_MATCH_EXTENDED)
        {
        	$searchClassAttributeID = isset($params['SearchClassAttributeID']) ? $params['SearchClassAttributeID'] : 0 ;          
	        if ( is_numeric( $searchClassAttributeID ) and $searchClassAttributeID > 0 )
	        {
	        	 $AppendExtendQuery = '@attr_srch_pos'.$this->getPositionClassAttribute((int)$searchClassAttributeID).' ';
	        }
	        else if ( is_array( $searchClassAttributeID ) )
	        {           
	            
	            $SubElements = array();
	            foreach ($searchClassAttributeID as $ClassAttributeID)
	            {
	            	$SubElements[] = 'attr_srch_pos'.$this->getPositionClassAttribute($ClassAttributeID);
	            }
	        	$AppendExtendQuery = '@('.implode(',',$SubElements).') ';	            
	        }
        }
              
        // Transofrm without special characters like i understood. Actualy in sphinx it's not needed. But like indexing converts them to normalized text, it will be changed in futher versions..
        $trans = eZCharTransform::instance();
        $searchText = $trans->transformByGroup( $searchText, 'search' );                
	  	$result = $cl->Query( $AppendExtendQuery.trim($searchText) , isset($params['IndexName']) ? $params['IndexName'] : $this->SphinxIndexName );
	  		
	  	// If nothing found return immediately  	
	  	if ($result['total_found'] == 0)
	  	{	  	
		  	return array( "SearchResult" => array(),
	                      "SearchCount" => 0,
	                      "StopWordArray" => array() );
	  	}                 
	  	
	  	$NodeIDList = array();
	  
	  	$SingleNodeID = null;
	  	
	  	if ($result['total_found'] > 1)
	  	{
		  	// Build nodes ID's
		  	foreach ($result['matches'] as $match)
		  	{ 		
		  		$NodeIDList[$match['attrs']['node_id']] = $match['attrs']['node_id'];
		  	}
	  	} else {
	  			foreach ($result['matches'] as $match)
			  	{	  		
			  		$NodeIDList = $match['attrs']['node_id'];
			  		$SingleNodeID = $match['attrs']['node_id'];
			  	}
	  	}
	  	
	  	
	  	$nodeRowList = array();
  		$tmpNodeRowList = eZContentObjectTreeNode::fetch( $NodeIDList, false, isset($params['AsObjects']) ? $params['AsObjects'] : true );
  		  	
        // Workaround for eZContentObjectTreeNode::fetch behaviour
        if ( count( $tmpNodeRowList ) === 1 )
        {
            $tmpNodeRowList = array( $tmpNodeRowList );   
            unset($NodeIDList);     
            $NodeIDList = array();
            $NodeIDList[$SingleNodeID] = $SingleNodeID;
        }
        
        // If fetched objects, keeps fetched sorting as Sphinx returned it
        if (!isset($params['AsObjects']) || $params['AsObjects'] === true)
        {    
			foreach ($tmpNodeRowList as $node)
			{
				$NodeIDList[$node->attribute('node_id')] = $node;
			}
        } else { // If fetched array
        	foreach ($tmpNodeRowList as $node)
			{
				$NodeIDList[$node['node_id']] = $node;
			}
        }    
    	unset($tmpNodeRowList);
                   	  	
	  	$searchResult = array(
	  		'SearchCount' => $result['total_found'],
	  		'SearchResult' => $NodeIDList,
	  		'SearchTook' => $result['time'],
	  		"StopWordArray" => array() // Perhaps anyone nows how to set this ? :)
	  	);
        
        return $searchResult;      
    }
	
    
	/**
	 * Based on ezsearchengine buildSortSQL method
	 * @param sort array
	 * 
	 * @return sorting string
	 * */
	function buildSort( $sortArray )
    {
        $sortCount = 0;
        $sortList = false;
        if ( isset( $sortArray ) and
             is_array( $sortArray ) and
             count( $sortArray ) > 0 )
        {
            $sortList = $sortArray;
            if ( count( $sortList ) > 1 and
                 !is_array( $sortList[0] ) )
            {
                $sortList = array( $sortList );
            }
        }
        $attributeJoinCount = 0;
        $attributeFromSQL = "";
        $attributeWereSQL = "";
        $sortingFields = '';
        if ( $sortList !== false )
        {          
            foreach ( $sortList as $sortBy )
            {
                if ( is_array( $sortBy ) and count( $sortBy ) > 0 )
                {
                    if ( $sortCount > 0 )
                        $sortingFields .= ', ';
                    $sortField = $sortBy[0];
                    switch ( $sortField )
                    {
                        case 'published':
                        {
                            $sortingFields .= 'published';
                        } break;
                        case 'modified':
                        {
                            $sortingFields .= 'modified';
                        } break;
                        case 'section':
                        {
                            $sortingFields .= 'section_id';
                        } break;
                        case 'relevance':
                        {
                            $sortingFields .= '@relevance';
                        } break;
                        case 'id':
                        {
                            $sortingFields .= '@id';
                        } break;
                        case 'priority':
                        {
                            $sortingFields .= 'priority';
                        } break;                      
                        case 'attribute': //Sorting is enabled only for integer columns
                        {                        	
                            $sortingFields .='attr_srch_int_pos'.$this->getPositionClassAttribute($sortBy[2]);
                        }break;

                        default:
                        {
                            eZDebug::writeWarning( 'Unknown sort field: ' . $sortField, 'eZSphinx::buildSort' );
                            continue;
                        };
                    }
                    $sortOrder = true; // true is ascending
                    if ( isset( $sortBy[1] ) )
                        $sortOrder = $sortBy[1];
                    $sortingFields .= $sortOrder ? " ASC" : " DESC";
                    ++$sortCount;
                }
            }
        }

        return $sortingFields;
    }

    /**
     * Get position of class attribute in the class
     * 
     * @param class attribute id
     * 
     * @return decremented position of class attribute placement
     * */
    function getPositionClassAttribute($classAttributeID)
    {
    	$sortClassID =  eZContentClassAttribute::fetch($classAttributeID,false,eZContentClass::VERSION_STATUS_DEFINED,array('placement'));
        return $sortClassID['placement']-1;
    }
    
    /*!
     Normalizes the text \a $text so that it is easily parsable
     \param $isMetaData If \c true then it expects the text to be meta data from objects,
                        if not it is the search text and needs special handling.
    */
    function normalizeText( $text, $isMetaData = false )
    {
        //include_once( 'lib/ezi18n/classes/ezchartransform.php' );
        $trans = eZCharTransform::instance();
        $text = $trans->transformByGroup( $text, 'search' );

        // Remove quotes and asterix when not handling search text by end-user
        if ( $isMetaData )
        {
            $text = str_replace( array( "\"", "*" ), array( " ", " " ), $text );
        }

        return $text;
    }

    /**
     * @todo update this function according to implemented functions, any volunteers ? :D
     * 
     * */
    function supportedSearchTypes()
    {
        $searchTypes = array( array( 'type' => 'attribute',
                                     'subtype' => 'fulltext',
                                     'params' => array( 'classattribute_id', 'value' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' => 'patterntext',
                                     'params' => array( 'classattribute_id', 'value' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' => 'integer',
                                     'params' => array( 'classattribute_id', 'value' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' => 'integers',
                                     'params' => array( 'classattribute_id', 'values' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' => 'byidentifier',
                                     'params' => array( 'classattribute_id', 'identifier', 'value' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' => 'byidentifierrange',
                                     'params' => array( 'classattribute_id', 'identifier', 'from', 'to' ) ),
                              array( 'type' => 'attribute',
                                     'subtype' => 'integersbyidentifier',
                                     'params' => array( 'classattribute_id', 'identifier', 'values' ) ),
                              array( 'type' => 'fulltext',
                                     'subtype' => 'text',
                                     'params' => array( 'value' ) ) );
        $generalSearchFilter = array( array( 'type' => 'general',
                                             'subtype' => 'class',
                                             'params' => array( array( 'type' => 'array',
                                                                       'value' => 'value'),
                                                                'operator' ) ),
                                      array( 'type' => 'general',
                                             'subtype' => 'publishdate',
                                             'params'  => array( 'value', 'operator' ) ),
                                      array( 'type' => 'general',
                                             'subtype' => 'subtree',
                                             'params'  => array( array( 'type' => 'array',
                                                                        'value' => 'value'),
                                                                 'operator' ) ) );
        return array( 'types' => $searchTypes,
                      'general_filter' => $generalSearchFilter );
    }


    function fetchTotalObjectCount()
    {
        // Get the total number of objects
        $db = eZDB::instance();
        $objectCount = array();
        $objectCount = $db->arrayQuery( "SELECT COUNT(*) AS count FROM ezcontentobject" );
        $totalObjectCount = $objectCount[0]["count"];
        return $totalObjectCount;
    }

    function constructMethodName( $searchTypeData )
    {
        $type = $searchTypeData['type'];
        $subtype = $searchTypeData['subtype'];
        $methodName = 'search' . $type . $subtype;
        return $methodName;

    }

    function callMethod( $methodName, $parameterArray )
    {
        if ( !method_exists( $this, $methodName ) )
        {
            eZDebug::writeError( $methodName, "Method does not exist in ez search engine" );
            return false;
        }

        if ( $this->UseOldCall )
        {
            return call_user_method_array( $methodName, $this, $parameterArray );
        }
        else
        {
            return $this->$methodName($parameterArray[0]);
        }
    }

    /*!
     Will remove all search words and object/word relations.
    */
    function cleanup()
    {
        $db = eZDB::instance();
        $db->begin();
        $db->query( "DELETE FROM ezsphinx" );
        $db->query( "DELETE FROM ezsphinx_pathnodes" );
         $db->commit();
    }

    var $SphinxINI;
    var $SphinxServerHost;
    var $SphinxServerPort;
    var $SphinxIndexName;
}

?>