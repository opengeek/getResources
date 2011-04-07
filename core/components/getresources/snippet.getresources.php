<?php

$modx->getService('fire', 'modFire', $modx->getOption('core_path').'components/modfire/');


/**
 * getResources
 *
 * A general purpose Resource listing and summarization snippet for MODX 2.x.
 *
 * @author Jason Coward
 * @copyright Copyright 2010-2011, Jason Coward
 * @version 1.3.1-beta - March 28, 2011
 *
 * TEMPLATES
 *
 * tpl - Name of a chunk serving as a resource template
 * [NOTE: if not provided, properties are dumped to output for each resource]
 *
 * tplOdd - (Opt) Name of a chunk serving as resource template for resources with an odd idx value
 * (see idx property)
 * tplFirst - (Opt) Name of a chunk serving as resource template for the first resource (see first
 * property)
 * tplLast - (Opt) Name of a chunk serving as resource template for the last resource (see last
 * property)
 * tpl_{n} - (Opt) Name of a chunk serving as resource template for the nth resource
 *
 * SELECTION
 *
 * parents - Comma-delimited list of ids serving as parents
 *
 * depth - (Opt) Integer value indicating depth to search for resources from each parent [default=10]
 *
 * tvFilters - (Opt) Delimited-list of TemplateVar values to filter resources by. Supports two
 * delimiters and two value search formats. THe first delimiter || represents a logical OR and the
 * primary grouping mechanism.  Within each group you can provide a comma-delimited list of values.
 * These values can be either tied to a specific TemplateVar by name, e.g. myTV==value, or just the
 * value, indicating you are searching for the value in any TemplateVar tied to the Resource. An
 * example would be &tvFilters=`filter2==one,filter1==bar%||filter1==foo`
 * [NOTE: filtering by values uses a LIKE query and % is considered a wildcard.]
 * [NOTE: this only looks at the raw value set for specific Resource, i. e. there must be a value
 * specifically set for the Resource and it is not evaluated.]
 *
 * where - (Opt) A JSON expression of criteria to build any additional where clauses from. An example would be
 * &where=`{{"alias:LIKE":"foo%", "OR:alias:LIKE":"%bar"},{"OR:pagetitle:=":"foobar", "AND:description:=":"raboof"}}`
 *
 * sortby - (Opt) Field to sort by or a JSON array, e.g. {"publishedon":"ASC","createdon":"DESC"} [default=publishedon]
 * sortbyTV - (opt) A Template Variable name to sort by (if supplied, this precedes the sortby value) [default=]
 * sortbyTVType - (Opt) A data type to CAST a TV Value to in order to sort on it properly [default=string]
 * sortbyAlias - (Opt) Query alias for sortby field [default=]
 * sortbyEscaped - (Opt) Escapes the field name(s) specified in sortby [default=0]
 * sortdir - (Opt) Order which to sort by [default=DESC]
 * sortdirTV - (Opt) Order which to sort by a TV [default=DESC]
 * limit - (Opt) Limits the number of resources returned [default=5]
 * offset - (Opt) An offset of resources returned by the criteria to skip [default=0]
 *
 * OPTIONS
 *
 * includeContent - (Opt) Indicates if the content of each resource should be returned in the
 * results [default=0]
 * includeTVs - (Opt) Indicates if TemplateVar values should be included in the properties available
 * to each resource template [default=0]
 * processTVs - (Opt) Indicates if TemplateVar values should be rendered as they would on the
 * resource being summarized [default=0]
 * tvPrefix - (Opt) The prefix for TemplateVar properties [default=tv.]
 * idx - (Opt) You can define the starting idx of the resources, which is an property that is
 * incremented as each resource is rendered [default=1]
 * first - (Opt) Define the idx which represents the first resource (see tplFirst) [default=1]
 * last - (Opt) Define the idx which represents the last resource (see tplLast) [default=# of
 * resources being summarized + first - 1]
 * outputSeparator - (Opt) An optional string to separate each tpl instance [default="\n"]
 *
 */
$output = array();
$outputSeparator = isset($outputSeparator) ? $outputSeparator : "\n";

/* set default properties */
$tpl = !empty($tpl) ? $tpl : '';
$includeContent = !empty($includeContent) ? true : false;
$includeTVs = !empty($includeTVs) ? true : false;
$processTVs = !empty($processTVs) ? true : false;
$tvPrefix = isset($tvPrefix) ? $tvPrefix : 'tv.';
$parents = (!empty($parents) || $parents === '0') ? explode(',', $parents) : array($modx->resource->get('id'));
$depth = isset($depth) ? (integer) $depth : 10;
$children = array();
foreach ($parents as $parent) {
    $pchildren = $modx->getChildIds($parent, $depth);
    if (!empty($pchildren)) $children = array_merge($children, $pchildren);
}
if (!empty($children)) $parents = array_merge($parents, $children);

$tvFilters = !empty($tvFilters) ? explode('||', $tvFilters) : array();

$where = !empty($where) ? $modx->fromJSON($where) : array();
$showUnpublished = !empty($showUnpublished) ? true : false;
$showDeleted = !empty($showDeleted) ? true : false;

$sortby = isset($sortby) ? $sortby : 'publishedon';
$sortbyTV = isset($sortbyTV) ? $sortbyTV : '';
$sortbyAlias = isset($sortbyAlias) ? $sortbyAlias : 'modResource';
$sortbyEscaped = !empty($sortbyEscaped) ? true : false;
$sortdir = isset($sortdir) ? $sortdir : 'DESC';
$sortdirTV = isset($sortdirTV) ? $sortdirTV : 'DESC';
$limit = isset($limit) ? (integer) $limit : 5;
$offset = isset($offset) ? (integer) $offset : 0;
$totalVar = !empty($totalVar) ? $totalVar : 'total';

/* build query */
$contextResourceTbl = $modx->getTableName('modContextResource');

/* multiple context support */
if (!empty($context)) {
    $context = explode(',',$context);
    $contexts = array();
    foreach ($context as $ctx) {
        $contexts[] = $modx->quote($ctx);
    }
    $context = implode(',',$contexts);
    unset($contexts,$ctx);
} else {
    $context = $modx->quote($modx->context->get('key'));
}
$criteria = $modx->newQuery('modResource', array(
    "modResource.parent IN (" . implode(',', $parents) . ")"
    ,"(modResource.context_key IN ({$context}) OR EXISTS(SELECT 1 FROM {$contextResourceTbl} ctx WHERE ctx.resource = modResource.id AND ctx.context_key IN ({$context})))"
));
if (empty($showDeleted)) {
    $criteria->andCondition(array('deleted' => '0'));
}
if (empty($showUnpublished)) {
    $criteria->andCondition(array('published' => '1'));
}
if (empty($showHidden)) {
    $criteria->andCondition(array('hidemenu' => '0'));
}
if (!empty($hideContainers)) {
    $criteria->andCondition(array('isfolder' => '0'));
}
/* include/exclude resources, via &resources=`123,-456` prop */
if (!empty($resources)) {
    $resources = explode(',',$resources);
    $include = array();
    $exclude = array();
    foreach ($resources as $resource) {
        $resource = (int)$resource;
        if ($resource == 0) continue;
        if ($resource < 0) {
            $exclude[] = abs($resource);
        } else {
            $include[] = $resource;
        }
    }
    if (!empty($include)) {
        $criteria->orCondition(array('modResource.id:IN' => $include),null,10);
    }
    if (!empty($exclude)) {
        $criteria->andCondition(array('modResource.id NOT IN ('.implode(',',$exclude).')'));
    }
}

// Parse TV filters
if (!empty($tvFilters)) {
    $conditions = array();
		foreach ($tvFilters as $fGroup => $tvFilter) {
			
			$filterGroup = count($tvFilters) > 1 ? $fGroup + 1 : 0;
			$filters = explode(',', $tvFilter);
			
			// These are the operators we'll look at. Single characters must be done last, to avoid false positives.
			$operators = array( '==', '!=', '<=', '>=', '<>', '>', '<', '=' );
			
			foreach ($filters as $filter) {
				
				// Find which operator we're working on
				$foundOperator = '=='; // Default
				foreach ($operators as $o) {
					if ( strpos($filter, $o) !== false) {
						$foundOperator = $o;
						break;	
					}
				}
				
				// Split the operator from the values
				$f = explode($foundOperator, $filter);
						
				// And split into TV name and value
				if (count($f) > 2) {// In case the operator was also found in the value, put those bits back together again to reinstate the value
					$tvName = array_shift($f);
					$tvValue = implode($foundOperator, $f);
				} else if (count($f) == 2) { 
					$tvName = $f[0];
					$tvValue = $f[1];
				} else {
					$tvName = '';
					$tvValue = $f[0];
				}
				
				// Put these into an array
				$conditions[$filterGroup][] = array( 'tvName' => $tvName, 'tvValue' => $tvValue, 'operator' => $foundOperator);
    
		}
	}
}
if (!empty($where)) {
    $criteria->where($where);
}

$total = $modx->getCount('modResource', $criteria);
$modx->setPlaceholder($totalVar, $total);

$fields = array_keys($modx->getFields('modResource'));
if (empty($includeContent)) {
    $fields = array_diff($fields, array('content'));
}
$columns = $includeContent ? $modx->getSelectColumns('modResource', 'modResource') : $modx->getSelectColumns('modResource', 'modResource', '', array('content'), true);
$criteria->select($columns);
if (!empty($sortbyTV)) {
    $criteria->leftJoin('modTemplateVar', 'tvDefault', array(
        "tvDefault.name" => $sortbyTV
    ));
    $criteria->leftJoin('modTemplateVarResource', 'tvSort', array(
        "tvSort.contentid = modResource.id",
        "tvSort.tmplvarid = tvDefault.id"
    ));
    if (empty($sortbyTVType)) $sortbyTVType = 'string';
    if ($modx->getOption('dbtype') === 'mysql') {
        switch ($sortbyTVType) {
            case 'integer':
                $criteria->select("CAST(IFNULL(tvSort.value, tvDefault.default_text) AS SIGNED INTEGER) AS sortTV");
                break;
            case 'decimal':
                $criteria->select("CAST(IFNULL(tvSort.value, tvDefault.default_text) AS DECIMAL) AS sortTV");
                break;
            case 'datetime':
                $criteria->select("CAST(IFNULL(tvSort.value, tvDefault.default_text) AS DATETIME) AS sortTV");
                break;
            case 'string':
            default:
                $criteria->select("IFNULL(tvSort.value, tvDefault.default_text) AS sortTV");
                break;
        }
    } elseif ($modx->getOption('dbtype') === 'sqlsrv') {
        switch ($sortbyTVType) {
            case 'integer':
                $criteria->select("CAST(ISNULL(tvSort.value, tvDefault.default_text) AS BIGINT) AS sortTV");
                break;
            case 'decimal':
                $criteria->select("CAST(ISNULL(tvSort.value, tvDefault.default_text) AS DECIMAL) AS sortTV");
                break;
            case 'datetime':
                $criteria->select("CAST(ISNULL(tvSort.value, tvDefault.default_text) AS DATETIME) AS sortTV");
                break;
            case 'string':
            default:
                $criteria->select("ISNULL(tvSort.value, tvDefault.default_text) AS sortTV");
                break;
        }
    }
    $criteria->sortby("sortTV", $sortdirTV);
}
if (!empty($sortby)) {
    if (strpos($sortby, '{') === 0) {
        $sorts = $modx->fromJSON($sortby);
    } else {
        $sorts = array($sortby => $sortdir);
    }
    if (is_array($sorts)) {
        while (list($sort, $dir) = each($sorts)) {
            if ($sortbyEscaped) $sort = $modx->escape($sort);
            if (!empty($sortbyAlias)) $sort = $modx->escape($sortbyAlias) . ".{$sort}";
            $criteria->sortby($sort, $dir);
        }
    }
}
if (!empty($limit)) $criteria->limit($limit, $offset);

if (!empty($debug)) {
    $criteria->prepare();
    $modx->log(modX::LOG_LEVEL_ERROR, $criteria->toSQL());
}
$collection = $modx->getCollection('modResource', $criteria);



// Now we have a basic set of results, are we retrieving or filtering on TVs?
if (!empty($includeTVs) || !empty($tvFilters)) { 	

	$tv_cache = array();

	// Go through each resource which has been found, and populate it with TV values. Do this once.
	foreach ($collection as $resourceId => $resource) {
		
		
		// Get the TVs for this resource		
		$templateVars =& $resource->getMany('TemplateVars');
		foreach ($templateVars as $tvId => $templateVar) {
			$tvName = $templateVar->get('name');
			$tv_cache[$resourceId][$tvName]['value'] = !empty($processTVs) ? $templateVar->renderOutput($resource->get('id')) : $templateVar->get('value');
			$tv_cache[$resourceId][$tvName]['type'] = $templateVar->get('type');
		}	
		
		
		
		//$modx->fire->log("--------- Testing Resource $resourceId " . $resource->get('pagetitle'));
		
		// Are we including this resource?
		 if (!empty($conditions)) {
			 
			$keep_group = false; 
			 
			foreach ($conditions as $cGroup => $c) {
				
				$keep = false;
				//$modx->fire->log("--------- Testing Condition group $cGroup");
				
				foreach ($c as $thisCriteria) {	
								
					
					// If it's a wildcard, keep and check the next criteria
					if ($thisCriteria['tvValue'] == '%') {
						$keep = true;
						//$modx->fire->log("Its a wildcard - keep is true, skipping to next criteria");
						continue;	
					}
					
					$tvValue = $tv_cache[$resourceId][$thisCriteria["tvName"]]['value'];
					
					// if it's a date, convert both to timestamps
					if ($tv_cache[$resourceId][$thisCriteria["tvName"]]['type'] == 'date') {
						$thisCriteria['tvValue'] = (strtotime($thisCriteria['tvValue'])) ? strtotime($thisCriteria['tvValue']) : $thisCriteria['tvValue'];
						$tvValue = (strtotime($tvValue)) ? strtotime($tvValue) : $tvValue;
					}
					
					
					//$modx->fire->log("-- Testing criteria are:");
					//$modx->fire->log( $thisCriteria);
					//$modx->fire->log("Processed TV value is $tvValue"); 
					
					
					switch ($thisCriteria['operator']) {
						
						case '==':
						case '=':
							// If there is no specific TV name, search all TVs
							if ($thisCriteria["tvName"] == '' && array_search($thisCriteria['tvValue'], $tv_cache[$resourceId]) ) {
								$keep = true;
							// If there is a specific TV name, check that one
							} else if ($tvValue == $thisCriteria['tvValue']) {
								$keep = true;
							// Else remove this resource		
							} else {
								$keep = false;
								break 2;
							}
						break;
						
						case '!=':
						case '<>':
							// If there is no specific TV name, search all TVs
							if ($thisCriteria["tvName"] == '' && !array_search($thisCriteria['tvValue'], $tv_cache[$resourceId]) ) {
								$keep = true;
							// If there is a specific TV name, check that one
							} else if ($tvValue != $thisCriteria['tvValue']) {
								$keep = true;
							// Else remove this resource		
							} else {
								$keep = false;
								break 2;
							}
						break;	
						
						
						case '<=':							
							// If there is a specific TV name, check that one
							if ($tvValue <= $thisCriteria['tvValue']) {
								$keep = true;
							// Else remove this resource		
							} else {
								$keep = false;
								break 2;
							}
						break;
						
						
						case '>=':							
							// If there is a specific TV name, check that one
							if ($tvValue >= $thisCriteria['tvValue']) {
								$keep = true;
							// Else remove this resource		
							} else {
								$keep = false;
								break 2;
							}
						break;
						
						
						case '<':							
							// If there is a specific TV name, check that one
							if ($tvValue < $thisCriteria['tvValue']) {
								$keep = true;
							// Else remove this resource		
							} else {
								$keep = false;
								break 2;
							}
						break;
						
						
						case '>':							
							// If there is a specific TV name, check that one
							if ($tvValue > $thisCriteria['tvValue']) {
								$modx->fire->log("> match - keep is true");
								$keep = true;
							// Else remove this resource		
							} else {
								$modx->fire->log("NO > match - keep is false");
								$keep = false;
								break 2;
							}
						break;
						
					}
					
				}
				
				// If this group has proven to be true, since groups are OR, we don't need to evaluate any further
				if ($keep) {
					$modx->fire->log("End of this group tests - keep is still true");
					$keep_group = true;
					break;	
				} else {
					$modx->fire->log("End of this group tests - keep is false");
				}
				
			}
			
			// If we're not keeping, remove from the collections array
			if (!$keep_group) {
				$modx->fire->log("End of this all tests - keep_group is NOT true, deleting this resource");
				unset($collection[$resourceId]);	
			} else {
				$modx->fire->log("End of this all tests - keep_group is true, keeping this resource");	
			}
			
		}
		
	}
}



$idx = !empty($idx) ? intval($idx) : 1;
$first = empty($first) && $first !== '0' ? 1 : intval($first);
$last = empty($last) ? (count($collection) + $idx - 1) : intval($last);

/* include parseTpl */
include_once $modx->getOption('getresources.core_path',null,$modx->getOption('core_path').'components/getresources/').'include.parsetpl.php';

foreach ($collection as $resourceId => $resource) {
    $tvs = array();
    if (!empty($includeTVs)) {
        $templateVars =& $resource->getMany('TemplateVars');
        foreach ($tv_cache[$resourceId] as $tvId => $templateVal) {
            $tvs[$tvPrefix . $tvId] = $templateVal;
        }
    }
    $odd = ($idx & 1);
    $properties = array_merge(
        $scriptProperties
        ,array(
            'idx' => $idx
            ,'first' => $first
            ,'last' => $last
        )
        ,$includeContent ? $resource->toArray() : $resource->get($fields)
        ,$tvs
    );
    $resourceTpl = '';
    $tplidx = 'tpl_' . $idx;
    if (!empty($$tplidx)) $resourceTpl = parseTpl($$tplidx, $properties);
    switch ($idx) {
        case $first:
            if (!empty($tplFirst)) $resourceTpl = parseTpl($tplFirst, $properties);
            break;
        case $last:
            if (!empty($tplLast)) $resourceTpl = parseTpl($tplLast, $properties);
            break;
    }
    if ($odd && empty($resourceTpl) && !empty($tplOdd)) $resourceTpl = parseTpl($tplOdd, $properties);
    if (!empty($tpl) && empty($resourceTpl)) $resourceTpl = parseTpl($tpl, $properties);
    if (empty($resourceTpl)) {
        $chunk = $modx->newObject('modChunk');
        $chunk->setCacheable(false);
        $output[]= $chunk->process(array(), '<pre>' . print_r($properties, true) .'</pre>');
    } else {
        $output[]= $resourceTpl;
    }
    $idx++;
}

/* output */
$toSeparatePlaceholders = $modx->getOption('toSeparatePlaceholders',$scriptProperties,false);
if (!empty($toSeparatePlaceholders)) {
    $modx->setPlaceholders($output,$toSeparatePlaceholders);
    return '';
}

$output = implode($outputSeparator, $output);
$toPlaceholder = $modx->getOption('toPlaceholder',$scriptProperties,false);
if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder,$output);
    return '';
}
return $output;