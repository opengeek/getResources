<?php
/**
 * getResources
 *
 * A general purpose Resource listing and summarization snippet for MODx 2.0.
 *
 * @author Jason Coward
 * @copyright Copyright 2009, Jason Coward
 * @version 1.0.0-beta - June 26, 2009
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
 * delimiters and two value search formats. THe first delimeter || represents a logical OR and the
 * primary grouping mechanism.  Within each group you can provide a comma-delimited list of values.
 * These values can be either tied to a specific TemplateVar by name, e.g. myTV==value, or just the
 * value, indicating you are searching for the value in any TemplateVar tied to the Resource. An
 * example would be &tvFilters=`filter2==one,filter1==bar%||filter1==foo`
 * [NOTE: filtering by values uses a LIKE query and % is considered a wildcard.]
 * [NOTE: this only looks at the raw value set for specific Resource, i. e. there must be a value
 * specifically set for the Resource and it is not evaluated.]
 *
 * sortby - (Opt) Field to sort by [default=publishedon]
 * sortbyAlias - (Opt) Query alias for sortby field [default=modResource]
 * sortdir  - (Opt) Order which to sort by [default=DESC]
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
 *
 */
$output = array();

$tpl = !empty($tpl) ? $tpl : '';
$includeContent = !empty($includeContent) ? true : false;
$includeTVs = !empty($includeTVs) ? true : false;
$processTVs = !empty($processTVs) ? true : false;
$tvPrefix = !empty($tvPrefix) ? $tvPrefix : 'tv.';
$parents = !empty($parents) ? explode(',', $parents) : array($modx->resource->get('id'));
$depth = !empty($depth) && ($depth = intval($depth)) ? $depth : 10;
$children = array();
foreach ($parents as $parent) {
    $pchildren = $modx->getChildIds($parent, $depth);
    if (!empty($pchildren)) $children = array_merge($children, $pchildren);
}
if (!empty($children)) $parents = array_merge($parents, $children);

$tvFilters = !empty($tvFilters) ? explode('||', $tvFilters) : array();

$sortby = !empty($sortby) ? $sortby : 'publishedon';
$sortbyAlias = !empty($sortbyAlias) ? $sortbyAlias : 'modResource';
$sortby = "`{$sortbyAlias}`.`{$sortby}`";
$sortdir = !empty($sortdir) ? $sortdir : 'DESC';
$limit = !empty($limit) ? intval($limit) : 5;
$offset = !empty($offset) ? intval($offset) : 0;
$totalVar = !empty($totalVar) ? $totalVar : 'total';

$contextResourceTbl = $modx->getTableName('modContextResource');
$context = empty($context) ? $modx->quote($modx->context->get('key')) : $modx->quote($context);
$criteria = $modx->newQuery('modResource', array(
    'deleted' => '0'
    ,'published' => '1'
    ,"`modResource`.`parent` IN (" . implode(',', $parents) . ")"
    ,"(`modResource`.`context_key` = {$context} OR EXISTS(SELECT 1 FROM {$contextResourceTbl} `ctx` WHERE `ctx`.`resource` = `modResource`.`id` AND `ctx`.`context_key` = {$context}))"
));
if (empty($showHidden)) {
    $criteria->andCondition(array('hidemenu' => '0'));
}
if (!empty($hideContainers)) {
    $criteria->andCondition(array('isfolder' => '0'));
}
if (!empty($tvFilters)) {
    $tmplVarTbl = $modx->getTableName('modTemplateVar');
    $tmplVarResourceTbl = $modx->getTableName('modTemplateVarResource');
    $conditions = array();
    foreach ($tvFilters as $fGroup => $tvFilter) {
        $filterGroup = count($tvFilters) > 1 ? $fGroup + 1 : 0;
        $filters = explode(',', $tvFilter);
        foreach ($filters as $filter) {
            $f = explode('==', $filter);
            if (count($f) == 2) {
                $tvName = $modx->quote($f[0]);
                $tvValue = $modx->quote($f[1]);
                $conditions[$filterGroup][] = "EXISTS (SELECT 1 FROM {$tmplVarResourceTbl} `tvr` JOIN {$tmplVarTbl} `tv` ON `tvr`.`value` LIKE {$tvValue} AND `tv`.`name` = {$tvName} AND `tv`.`id` = `tvr`.`tmplvarid` WHERE `tvr`.`contentid` = `modResource`.`id`)";
            } elseif (count($f) == 1) {
                $tvValue = $modx->quote($f[0]);
                $conditions[$filterGroup][] = "EXISTS (SELECT 1 FROM {$tmplVarResourceTbl} `tvr` JOIN {$tmplVarTbl} `tv` ON `tvr`.`value` LIKE {$tvValue} AND `tv`.`id` = `tvr`.`tmplvarid` WHERE `tvr`.`contentid` = `modResource`.`id`)";
            }
        }
    }
    if (!empty($conditions)) {
        foreach ($conditions as $cGroup => $c) {
            if ($cGroup > 0) {
                $criteria->orCondition($c, null, $cGroup);
            } else {
                $criteria->andCondition($c);
            }
        }
    }
}

$total = $modx->getCount('modResource', $criteria);
$modx->setPlaceholder($totalVar, $total);

$criteria->sortby($sortby, $sortdir);
if (!empty($limit)) $criteria->limit($limit, $offset);
$columns = $includeContent ? '*' : $modx->getSelectColumns('modResource', 'modResource', '', array('content'), true);
$criteria->select($columns);
if (!empty($debug)) {
    $criteria->prepare();
    $modx->log(MODX_LOG_LEVEL_ERROR, $criteria->toSQL());
}
$collection = $modx->getCollection('modResource', $criteria);

$idx = !empty($idx) ? intval($idx) : 1;
$first = empty($first) && $first !== '0' ? 1 : intval($first);
$last = empty($last) ? (count($collection) + $idx - 1) : intval($last);

include_once(MODX_CORE_PATH . 'components/getresources/include.parsetpl.php');

foreach ($collection as $resourceId => $resource) {
    $tvs = array();
    if (!empty($includeTVs)) {
        $templateVars =& $resource->getMany('TemplateVars');
        foreach ($templateVars as $tvId => $templateVar) {
            $tvs[$tvPrefix . $templateVar->get('name')] = !empty($processTVs) ? $templateVar->renderOutput($resource->get('id')) : $templateVar->get('value');
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
        ,$resource->toArray()
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
return implode("\n", $output);