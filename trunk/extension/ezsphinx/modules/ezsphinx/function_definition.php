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

/*! \file function_definition.php
*/

$FunctionList = array();

$FunctionList['search'] = array( 'name' => 'search',
                                 'operation_types' => 'read',
                                 'call_method' => array( 'class' => 'ezSphinxModuleFunctionCollection',
                                                         'include_file' => 'extension/ezsphinx/classes/ezsphinxmodulefunctioncollection.php',
                                                         'method' => 'search' ),
                                 'parameter_type' => 'standard',
                                 'parameters' => array( array( 'name' => 'query', 				// Implemented
                                                               'type' => 'string',
                                                               'required' => true,
                                                               'default' => '' ),
                                                        array( 'name' => 'offset', 				// Implemented
                                                               'type' => 'integer',
                                                               'required' => false,
                                                               'default' => 0 ),
                                                        array( 'name' => 'limit', 				// Implemented
                                                               'type' => 'integer',
                                                               'required' => false,
                                                               'default' => 10 ),                                                        
                                                        array( 'name' => 'filter', 				// Implemented
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ),
                                                        array( 'name' => 'sort_by', 			// Implemented
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ),
                                                        array( 'name' => 'class_id',			// Implemented
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ),
                                                        array( 'name' => 'section_id',			// Implemented
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ),
                                                        array( 'name' => 'subtree_array', 		// Implemented
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ),
                                                        array( 'name' => 'ignore_visibility', 	// Implemented
                                                               'type' => 'bool',
                                                               'required' => false,
                                                               'default' => false ),                                                       
                                                        array( 'name' => 'as_objects', 			// Implemented
                                                               'type' => 'boolean',
                                                               'required' => false,
                                                               'default' => true ),
                                                        array( 'name' => 'index_name',			// Implemented
                                                               'type' => 'string',
                                                               'required' => false,
                                                               'default' => null ),
                                                        array( 'name' => 'publish_timestamp', 	// Implemented
                                                               'type' => 'mixed',
                                                               'required' => false,
                                                               'default' => false ),
                                                        array( 'name' => 'publish_date',	  	// Implemented
                                                               'type' => 'integer',
                                                               'required' => false,
                                                               'default' => false ),
                                                        array( 'name' => 'match_type',	  		// Implemented
                                                               'type' => 'string',
                                                               'required' => false,
                                                               'default' => 'SPH_MATCH_ANY' ),
                                                        array( 'name' => 'class_attribute_id',	// Implemented
                                                               'type' => 'string',
                                                               'required' => false,
                                                               'default' => false ),
                                                        array( 'name' => 'field_weight',	  	// Implemented
                                                               'type' => 'string',
                                                               'required' => false,
                                                               'default' => false ),
                                                        array( 'name' => 'parent_node_id',	  	// Implemented
                                                               'type' => 'string',
                                                               'required' => false,
                                                               'default' => false ),
                                                        array( 'name' => 'limitation',	  		// NOT Implemented, Will not be implemented untill i found solution how to implemente permission checking
                                                               'type' => 'array',
                                                               'required' => false,
                                                               'default' => null ) ) );

?>