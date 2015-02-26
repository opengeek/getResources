<?php
/**
 * @name getResources
 *
 * @description A general purpose Resource listing and summarization snippet for MODX 2.x.
 *
 * @author Jason Coward
 * @copyright Copyright 2010-2015, Jason Coward
 *
 * TEMPLATES
 *
 * @param string $tpl Name of a chunk serving as a resource template. NOTE: if not provided, properties are dumped to output for each resource.
 * @param string $tplOdd Name of a chunk serving as resource template for resources with an odd idx value (see idx property).
 * @param string $tplFirst Name of a chunk serving as resource template for the first resource (see first property).
 * @param string $tplLast Name of a chunk serving as resource template for the last resource (see last property).
 * tpl_{n} - (Opt) Name of a chunk serving as resource template for the nth resource
 * @param string $tplCondition A condition to compare against the conditionalTpls property to map Resources to different tpls based on custom conditional logic. Must be a resource field; does not work with Template Variables.
 * @param string $conditionalTpls A JSON map of conditional operands and tpls to compare against the tplCondition property using the specified tplOperator. Use when the field defined by tplCondition matches the value. [NOTE: tplOdd, tplFirst, tplLast, and tpl_{n} will take precedence over any defined conditionalTpls]
 * @param list $tplOperator An optional operator to use for the tplCondition when comparing against the conditionalTpls operands. Default is == (equals). [default="=="] [options={"==":"is equal to","!=":"is not equal to","<":"less than","<=":"less than or equal to",">":"greater than",">=":"greater than or equal to","empty":"is empty","!empty":"is not empty","null":"is null","inarray":"is in array","between":"is between"}]
 * @param string $tplWrapper Name of a chunk serving as wrapper template for the Snippet output. This does not work with toSeparatePlaceholders.
 *
 * SELECTION
 *
 * @param string $parents Optional. Comma-delimited list of ids serving as parents.
 * @param string $context A comma-delimited list of context keys for limiting results. Default is empty, i.e. do not limit results by context.
 * @param integer $depth Integer value indicating depth to search for resources from each parent. Defaults to 10. [default=10]
 * @param string $tvFilters Delimited-list of TemplateVar values to filter resources by. Supports two delimiters and two value search formats. THe first delimiter || represents a logical OR and the primary grouping mechanism.  Within each group you can provide a comma-delimited list of values. These values can be either tied to a specific TemplateVar by name, e.g. myTV==value, or just the value, indicating you are searching for the value in any TemplateVar tied to the Resource. An example would be &tvFilters=`filter2==one,filter1==bar%||filter1==foo`. <br />NOTE: filtering by values uses a LIKE query and % is considered a wildcard. <br />ANOTHER NOTE: This only looks at the raw value set for specific Resource, i. e. there must be a value specifically set for the Resource and it is not evaluated.
 * @param boolean $showHidden Indicates if Resources that are hidden from menus should be shown. Defaults to false. [default=false]
 * @param boolean $showUnpublished Indicates if Resources that are unpublished should be shown. Defaults to false. [default=false]
 * @param boolean $showDeleted Indicates if Resources that are deleted should be shown. Defaults to false. [default=false]
 * @param string $tvFiltersAndDelimiter The delimiter to use to separate logical AND expressions in tvFilters. Customize when you want to match a literal comma in the tvFilters. E.g. &tvFiltersAndDelimiter=`&&` &tvFilters=`filter1==foo,bar&&filter2==baz` [default=","]
 *
 * @param string $tvFiltersOrDelimiter The delimiter to use to separate logical OR expressions in tvFilters, in case you want to match a literal '||' in the tvFilters. E.g. &tvFiltersOrDelimiter=`|OR|` &tvFilters=`filter1==foo||bar|OR|filter2==baz` [default="||"]
 *
 * @param string $where A JSON expression of criteria to build any additional where clauses from, e.g. &where=`{{"alias:LIKE":"foo%", "OR:alias:LIKE":"%bar"},{"OR:pagetitle:=":"foobar", "AND:description:=":"raboof"}}`
 *
 * @param string $sortby A field name to sort by or JSON object of field names and sortdir for each field, e.g. {"publishedon":"ASC","createdon":"DESC"}. Defaults to publishedon. [default=publishedon]
 * @param string $sortbyTV Name of a Template Variable to sort by. Defaults to empty string.
 * @param list $sortbyTVType An optional type to indicate how to sort on the Template Variable value. [default=string] [options=["string","integer","decimal","datetime"]]
 * @param string $sortbyAlias Query alias for sortby field. Defaults to an empty string.
 * @param string $sortbyEscaped Determines if the field name specified in sortby should be escaped. Defaults to 0. [default=0]
 * @param string $sortdir Order which to sort by. Defaults to DESC. [default=DESC] [options=["ASC","DESC"]]
 * @param string $sortdirTV Order which to sort a Template Variable by. Defaults to DESC. [default=DESC] [options=["ASC","DESC"]]
 * @param integer $limit Limits the number of resources returned. Defaults to 5. [default=5]
 * @param integer $offset An offset of resources returned by the criteria to skip. [default=0]
 * @param string dbCacheFlag Determines how result sets are cached if cache_db is enabled in MODX. 0|false = do not cache result set; 1 = cache result set according to cache settings, any other integer value = number of seconds to cache result set [default=0]
 *
 * OPTIONS
 *
 * @param boolean $includeContentIndicates if the content of each resource should be returned in the results. Defaults to false. [default=false]
 * @param combon-boolean $includeTVs Indicates if TemplateVar values should be included in the properties available to each resource template. Defaults to false. [default=false]
 * @param string $includeTVList Limits included TVs to those specified as a comma-delimited list of TV names. Defaults to empty.
 * @param boolean $prepareTVs Indicates if TemplateVar values that are not processed fully should be prepared before being returned. Defaults to true. [default=true]
 * @param string prepareTVList Limits prepared TVs to those specified as a comma-delimited list of TV names; note only includedTVs will be available for preparing if specified. Defaults to empty.
 * @param boolean $processTVs Indicates if TemplateVar values should be rendered as they would on the resource being summarized. Defaults to false. [default=false]
 * @param string $processTVList Limits processed TVs to those specified as a comma-delimited list of TV names; note only includedTVs will be available for processing if specified. Defaults to empty.
 * @param string $tvPrefix The prefix for TemplateVar properties. Defaults to: tv. [default=tv.]
 * @param string $idx You can define the starting idx of the resources, which is an property that is incremented as each resource is rendered. [default=1]
 * @param string $first Define the idx which represents the first resource (see tplFirst). Defaults to 1. [default=1]
 * @param string $last Define the idx which represents the last resource (see tplLast). Defaults to the number of resources being summarized + first - 1
 * @param string resources A comma-separated list of resource IDs to exclude or include. IDs with a - in front mean to exclude. Ex: 123,-456 means to include Resource 123, but always exclude Resource 456.
 * outputSeparator - (Opt) An optional string to separate each tpl instance [default="\n"]
 * @param boolean $wrapIfEmpty - Indicates if empty output should be wrapped by the tplWrapper, if specified. Defaults to false. [default=0]
 * @param string $toPlaceholder If set, will assign the result to this placeholder instead of outputting it directly.
 * @param string $toSeparatePlaceholders If set, will assign EACH result to a separate placeholder named by this param suffixed with a sequential number (starting from 0).
 * @param boolean $debug If true, will send the SQL query to the MODX log. Defaults to false. [default=false]
 *
 */
$output = array();
$outputSeparator = isset($outputSeparator) ? $outputSeparator : "\n";

/* set default properties */
$tpl = !empty($tpl) ? $tpl : '';
$includeContent = !empty($includeContent) ? true : false;
$includeTVs = !empty($includeTVs) ? true : false;
$includeTVList = !empty($includeTVList) ? explode(',', $includeTVList) : array();
$processTVs = !empty($processTVs) ? true : false;
$processTVList = !empty($processTVList) ? explode(',', $processTVList) : array();
$prepareTVs = !empty($prepareTVs) ? true : false;
$prepareTVList = !empty($prepareTVList) ? explode(',', $prepareTVList) : array();
$tvPrefix = isset($tvPrefix) ? $tvPrefix : 'tv.';
$parents = (!empty($parents) || $parents === '0') ? explode(',', $parents) : array($modx->resource->get('id'));
array_walk($parents, 'trim');
$parents = array_unique($parents);
$depth = isset($depth) ? (integer) $depth : 10;

$tvFiltersOrDelimiter = isset($tvFiltersOrDelimiter) ? $tvFiltersOrDelimiter : '||';
$tvFiltersAndDelimiter = isset($tvFiltersAndDelimiter) ? $tvFiltersAndDelimiter : ',';
$tvFilters = !empty($tvFilters) ? explode($tvFiltersOrDelimiter, $tvFilters) : array();

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

$dbCacheFlag = !isset($dbCacheFlag) ? false : $dbCacheFlag;
if (is_string($dbCacheFlag) || is_numeric($dbCacheFlag)) {
    if ($dbCacheFlag == '0') {
        $dbCacheFlag = false;
    } elseif ($dbCacheFlag == '1') {
        $dbCacheFlag = true;
    } else {
        $dbCacheFlag = (integer) $dbCacheFlag;
    }
}

/* multiple context support */
$contextArray = array();
$contextSpecified = false;
if (!empty($context)) {
    $contextArray = explode(',',$context);
    array_walk($contextArray, 'trim');
    $contexts = array();
    foreach ($contextArray as $ctx) {
        $contexts[] = $modx->quote($ctx);
    }
    $context = implode(',',$contexts);
    $contextSpecified = true;
    unset($contexts,$ctx);
} else {
    $context = $modx->quote($modx->context->get('key'));
}

$pcMap = array();
$pcQuery = $modx->newQuery('modResource', array('id:IN' => $parents), $dbCacheFlag);
$pcQuery->select(array('id', 'context_key'));
if ($pcQuery->prepare() && $pcQuery->stmt->execute()) {
    foreach ($pcQuery->stmt->fetchAll(PDO::FETCH_ASSOC) as $pcRow) {
        $pcMap[(integer) $pcRow['id']] = $pcRow['context_key'];
    }
}

$children = array();
$parentArray = array();
foreach ($parents as $parent) {
    $parent = (integer) $parent;
    if ($parent === 0) {
        $pchildren = array();
        if ($contextSpecified) {
            foreach ($contextArray as $pCtx) {
                if (!in_array($pCtx, $contextArray)) {
                    continue;
                }
                $options = $pCtx !== $modx->context->get('key') ? array('context' => $pCtx) : array();
                $pcchildren = $modx->getChildIds($parent, $depth, $options);
                if (!empty($pcchildren)) $pchildren = array_merge($pchildren, $pcchildren);
            }
        } else {
            $cQuery = $modx->newQuery('modContext', array('key:!=' => 'mgr'));
            $cQuery->select(array('key'));
            if ($cQuery->prepare() && $cQuery->stmt->execute()) {
                foreach ($cQuery->stmt->fetchAll(PDO::FETCH_COLUMN) as $pCtx) {
                    $options = $pCtx !== $modx->context->get('key') ? array('context' => $pCtx) : array();
                    $pcchildren = $modx->getChildIds($parent, $depth, $options);
                    if (!empty($pcchildren)) $pchildren = array_merge($pchildren, $pcchildren);
                }
            }
        }
        $parentArray[] = $parent;
    } else {
        $pContext = array_key_exists($parent, $pcMap) ? $pcMap[$parent] : false;
        if ($debug) $modx->log(modX::LOG_LEVEL_ERROR, "context for {$parent} is {$pContext}");
        if ($pContext && $contextSpecified && !in_array($pContext, $contextArray, true)) {
            $parent = next($parents);
            continue;
        }
        $parentArray[] = $parent;
        $options = !empty($pContext) && $pContext !== $modx->context->get('key') ? array('context' => $pContext) : array();
        $pchildren = $modx->getChildIds($parent, $depth, $options);
    }
    if (!empty($pchildren)) $children = array_merge($children, $pchildren);
    $parent = next($parents);
}
$parents = array_merge($parentArray, $children);

/* build query */
$criteria = array("modResource.parent IN (" . implode(',', $parents) . ")");
if ($contextSpecified) {
    $contextResourceTbl = $modx->getTableName('modContextResource');
    $criteria[] = "(modResource.context_key IN ({$context}) OR EXISTS(SELECT 1 FROM {$contextResourceTbl} ctx WHERE ctx.resource = modResource.id AND ctx.context_key IN ({$context})))";
}
if (empty($showDeleted)) {
    $criteria['deleted'] = '0';
}
if (empty($showUnpublished)) {
    $criteria['published'] = '1';
}
if (empty($showHidden)) {
    $criteria['hidemenu'] = '0';
}
if (!empty($hideContainers)) {
    $criteria['isfolder'] = '0';
}
$criteria = $modx->newQuery('modResource', $criteria);
if (!empty($tvFilters)) {
    $tmplVarTbl = $modx->getTableName('modTemplateVar');
    $tmplVarResourceTbl = $modx->getTableName('modTemplateVarResource');
    $conditions = array();
    $operators = array(
        '<=>' => '<=>',
        '===' => '=',
        '!==' => '!=',
        '<>' => '<>',
        '==' => 'LIKE',
        '!=' => 'NOT LIKE',
        '<<' => '<',
        '<=' => '<=',
        '=<' => '=<',
        '>>' => '>',
        '>=' => '>=',
        '=>' => '=>'
    );
    foreach ($tvFilters as $fGroup => $tvFilter) {
        $filterGroup = array();
        $filters = explode($tvFiltersAndDelimiter, $tvFilter);
        $multiple = count($filters) > 0;
        foreach ($filters as $filter) {
            $operator = '==';
            $sqlOperator = 'LIKE';
            foreach ($operators as $op => $opSymbol) {
                if (strpos($filter, $op, 1) !== false) {
                    $operator = $op;
                    $sqlOperator = $opSymbol;
                    break;
                }
            }
            $tvValueField = 'tvr.value';
            $tvDefaultField = 'tv.default_text';
            $f = explode($operator, $filter);
            if (count($f) >= 2) {
                if (count($f) > 2) {
                    $k = array_shift($f);
                    $b = join($operator, $f);
                    $f = array($k, $b);
                }
                $tvName = $modx->quote($f[0]);
                if (is_numeric($f[1]) && !in_array($sqlOperator, array('LIKE', 'NOT LIKE'))) {
                    $tvValue = $f[1];
                    if ($f[1] == (integer)$f[1]) {
                        $tvValueField = "CAST({$tvValueField} AS SIGNED INTEGER)";
                        $tvDefaultField = "CAST({$tvDefaultField} AS SIGNED INTEGER)";
                    } else {
                        $tvValueField = "CAST({$tvValueField} AS DECIMAL)";
                        $tvDefaultField = "CAST({$tvDefaultField} AS DECIMAL)";
                    }
                } else {
                    $tvValue = $modx->quote($f[1]);
                }
                if ($multiple) {
                    $filterGroup[] =
                        "(EXISTS (SELECT 1 FROM {$tmplVarResourceTbl} tvr JOIN {$tmplVarTbl} tv ON {$tvValueField} {$sqlOperator} {$tvValue} AND tv.name = {$tvName} AND tv.id = tvr.tmplvarid WHERE tvr.contentid = modResource.id) " .
                        "OR EXISTS (SELECT 1 FROM {$tmplVarTbl} tv WHERE tv.name = {$tvName} AND {$tvDefaultField} {$sqlOperator} {$tvValue} AND tv.id NOT IN (SELECT tmplvarid FROM {$tmplVarResourceTbl} WHERE contentid = modResource.id)) " .
                        ")";
                } else {
                    $filterGroup =
                        "(EXISTS (SELECT 1 FROM {$tmplVarResourceTbl} tvr JOIN {$tmplVarTbl} tv ON {$tvValueField} {$sqlOperator} {$tvValue} AND tv.name = {$tvName} AND tv.id = tvr.tmplvarid WHERE tvr.contentid = modResource.id) " .
                        "OR EXISTS (SELECT 1 FROM {$tmplVarTbl} tv WHERE tv.name = {$tvName} AND {$tvDefaultField} {$sqlOperator} {$tvValue} AND tv.id NOT IN (SELECT tmplvarid FROM {$tmplVarResourceTbl} WHERE contentid = modResource.id)) " .
                        ")";
                }
            } elseif (count($f) == 1) {
                $tvValue = $modx->quote($f[0]);
                if ($multiple) {
                    $filterGroup[] = "EXISTS (SELECT 1 FROM {$tmplVarResourceTbl} tvr JOIN {$tmplVarTbl} tv ON {$tvValueField} {$sqlOperator} {$tvValue} AND tv.id = tvr.tmplvarid WHERE tvr.contentid = modResource.id)";
                } else {
                    $filterGroup = "EXISTS (SELECT 1 FROM {$tmplVarResourceTbl} tvr JOIN {$tmplVarTbl} tv ON {$tvValueField} {$sqlOperator} {$tvValue} AND tv.id = tvr.tmplvarid WHERE tvr.contentid = modResource.id)";
                }
            }
        }
        $conditions[] = $filterGroup;
    }
    if (!empty($conditions)) {
        $firstGroup = true;
        foreach ($conditions as $cGroup => $c) {
            if (is_array($c)) {
                $first = true;
                foreach ($c as $cond) {
                    if ($first && !$firstGroup) {
                        $criteria->condition($criteria->query['where'][0][1], $cond, xPDOQuery::SQL_OR, null, $cGroup);
                    } else {
                        $criteria->condition($criteria->query['where'][0][1], $cond, xPDOQuery::SQL_AND, null, $cGroup);
                    }
                    $first = false;
                }
            } else {
                $criteria->condition($criteria->query['where'][0][1], $c, $firstGroup ? xPDOQuery::SQL_AND : xPDOQuery::SQL_OR, null, $cGroup);
            }
            $firstGroup = false;
        }
    }
}
/* include/exclude resources, via &resources=`123,-456` prop */
if (!empty($resources)) {
    $resourceConditions = array();
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
        $criteria->where(array('OR:modResource.id:IN' => $include), xPDOQuery::SQL_OR);
    }
    if (!empty($exclude)) {
        $criteria->where(array('modResource.id:NOT IN' => $exclude), xPDOQuery::SQL_AND, null, 1);
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
            if ($sort == 'resources' && !empty($resources)) {
                $sort = 'FIELD(modResource.id, ' . implode($resources,',') . ')';
            }
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
$collection = $modx->getCollection('modResource', $criteria, $dbCacheFlag);

$idx = !empty($idx) || $idx === '0' ? (integer) $idx : 1;
$first = empty($first) && $first !== '0' ? 1 : (integer) $first;
$last = empty($last) ? (count($collection) + $idx - 1) : (integer) $last;

/* include parseTpl */
$core_path = $modx->getOption('getresources.core_path',null,MODX_CORE_PATH.'components/getresources/');
include_once $core_path.'elements/snippets/include.parsetpl.php';

$templateVars = array();
if (!empty($includeTVs) && !empty($includeTVList)) {
    $templateVars = $modx->getCollection('modTemplateVar', array('name:IN' => $includeTVList));
}
/** @var modResource $resource */
foreach ($collection as $resourceId => $resource) {
    $tvs = array();
    if (!empty($includeTVs)) {
        if (empty($includeTVList)) {
            $templateVars = $resource->getMany('TemplateVars');
        }
        /** @var modTemplateVar $templateVar */
        foreach ($templateVars as $tvId => $templateVar) {
            if (!empty($includeTVList) && !in_array($templateVar->get('name'), $includeTVList)) continue;
            if ($processTVs && (empty($processTVList) || in_array($templateVar->get('name'), $processTVList))) {
                $tvs[$tvPrefix . $templateVar->get('name')] = $templateVar->renderOutput($resource->get('id'));
            } else {
                $value = $templateVar->getValue($resource->get('id'));
                if ($prepareTVs && method_exists($templateVar, 'prepareOutput') && (empty($prepareTVList) || in_array($templateVar->get('name'), $prepareTVList))) {
                    $value = $templateVar->prepareOutput($value);
                }
                $tvs[$tvPrefix . $templateVar->get('name')] = $value;
            }
        }
    }
    $odd = ($idx & 1);
    $properties = array_merge(
        $scriptProperties
        ,array(
            'idx' => $idx
        ,'first' => $first
        ,'last' => $last
        ,'odd' => $odd
        )
        ,$includeContent ? $resource->toArray() : $resource->get($fields)
        ,$tvs
    );
    $resourceTpl = false;
    if ($idx == $first && !empty($tplFirst)) {
        $resourceTpl = parseTpl($tplFirst, $properties);
    }
    if ($idx == $last && empty($resourceTpl) && !empty($tplLast)) {
        $resourceTpl = parseTpl($tplLast, $properties);
    }
    $tplidx = 'tpl_' . $idx;
    if (empty($resourceTpl) && !empty($$tplidx)) {
        $resourceTpl = parseTpl($$tplidx, $properties);
    }
    if ($idx > 1 && empty($resourceTpl)) {
        $divisors = getDivisors($idx);
        if (!empty($divisors)) {
            foreach ($divisors as $divisor) {
                $tplnth = 'tpl_n' . $divisor;
                if (!empty($$tplnth)) {
                    $resourceTpl = parseTpl($$tplnth, $properties);
                    if (!empty($resourceTpl)) {
                        break;
                    }
                }
            }
        }
    }
    if ($odd && empty($resourceTpl) && !empty($tplOdd)) {
        $resourceTpl = parseTpl($tplOdd, $properties);
    }
    if (!empty($tplCondition) && !empty($conditionalTpls) && empty($resourceTpl)) {
        $conTpls = $modx->fromJSON($conditionalTpls);
        $subject = $properties[$tplCondition];
        $tplOperator = !empty($tplOperator) ? $tplOperator : '=';
        $tplOperator = strtolower($tplOperator);
        $tplCon = '';
        foreach ($conTpls as $operand => $conditionalTpl) {
            switch ($tplOperator) {
                case '!=':
                case 'neq':
                case 'not':
                case 'isnot':
                case 'isnt':
                case 'unequal':
                case 'notequal':
                    $tplCon = (($subject != $operand) ? $conditionalTpl : $tplCon);
                    break;
                case '<':
                case 'lt':
                case 'less':
                case 'lessthan':
                    $tplCon = (($subject < $operand) ? $conditionalTpl : $tplCon);
                    break;
                case '>':
                case 'gt':
                case 'greater':
                case 'greaterthan':
                    $tplCon = (($subject > $operand) ? $conditionalTpl : $tplCon);
                    break;
                case '<=':
                case 'lte':
                case 'lessthanequals':
                case 'lessthanorequalto':
                    $tplCon = (($subject <= $operand) ? $conditionalTpl : $tplCon);
                    break;
                case '>=':
                case 'gte':
                case 'greaterthanequals':
                case 'greaterthanequalto':
                    $tplCon = (($subject >= $operand) ? $conditionalTpl : $tplCon);
                    break;
                case 'isempty':
                case 'empty':
                    $tplCon = empty($subject) ? $conditionalTpl : $tplCon;
                    break;
                case '!empty':
                case 'notempty':
                case 'isnotempty':
                    $tplCon = !empty($subject) && $subject != '' ? $conditionalTpl : $tplCon;
                    break;
                case 'isnull':
                case 'null':
                    $tplCon = $subject == null || strtolower($subject) == 'null' ? $conditionalTpl : $tplCon;
                    break;
                case 'inarray':
                case 'in_array':
                case 'ia':
                    $operand = explode(',', $operand);
                    $tplCon = in_array($subject, $operand) ? $conditionalTpl : $tplCon;
                    break;
                case 'between':
                case 'range':
                case '>=<':
                case '><':
                    $operand = explode(',', $operand);
                    $tplCon = ($subject >= min($operand) && $subject <= max($operand)) ? $conditionalTpl : $tplCon;
                    break;
                case '==':
                case '=':
                case 'eq':
                case 'is':
                case 'equal':
                case 'equals':
                case 'equalto':
                default:
                    $tplCon = (($subject == $operand) ? $conditionalTpl : $tplCon);
                    break;
            }
        }
        if (!empty($tplCon)) {
            $resourceTpl = parseTpl($tplCon, $properties);
        }
    }
    if (!empty($tpl) && empty($resourceTpl)) {
        $resourceTpl = parseTpl($tpl, $properties);
    }
    if ($resourceTpl === false && !empty($debug)) {
        $chunk = $modx->newObject('modChunk');
        $chunk->setCacheable(false);
        $output[]= $chunk->process(array(), '<pre>' . print_r($properties, true) .'</pre>');
    } else {
        $output[]= $resourceTpl;
    }
    $idx++;
}

/* output */
$toSeparatePlaceholders = $modx->getOption('toSeparatePlaceholders', $scriptProperties, false);
if (!empty($toSeparatePlaceholders)) {
    $modx->setPlaceholders($output, $toSeparatePlaceholders);
    return '';
}

$output = implode($outputSeparator, $output);

$tplWrapper = $modx->getOption('tplWrapper', $scriptProperties, false);
$wrapIfEmpty = $modx->getOption('wrapIfEmpty', $scriptProperties, false);
if (!empty($tplWrapper) && ($wrapIfEmpty || !empty($output))) {
    $output = parseTpl($tplWrapper, array_merge($scriptProperties, array('output' => $output)));
}

$toPlaceholder = $modx->getOption('toPlaceholder', $scriptProperties, false);
if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder, $output);
    return '';
}
return $output;