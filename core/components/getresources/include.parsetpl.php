<?php
if (!function_exists('parseTplElement')) {
    function parseTplElement(& $_cache, $_validTypes, $type, $source, $properties = null) {
        global $modx;
        $output = false;
        if (!is_string($type) || !in_array($type, $_validTypes)) $type = $modx->getOption('tplType', $properties, '@CHUNK');
        $content = false;
        switch ($type) {
            case '@FILE':
                $path = $modx->getOption('tplPath', $properties, $modx->getOption('assets_path', $properties, MODX_ASSETS_PATH) . 'elements/chunks/');
                $key = $path . $source;
                if (!isset($_cache['@FILE'])) $_cache['@FILE'] = array();
                if (!array_key_exists($key, $_cache['@FILE'])) {
                    if (file_exists($key)) {
                        $content = file_get_contents($key);
                    }
                    $_cache['@FILE'][$key] = $content;
                } else {
                    $content = $_cache['@FILE'][$key];
                }
                if (!empty($content) && $content !== '0') {
                    $chunk = $modx->newObject('modChunk', array('name' => $key));
                    $chunk->setCacheable(false);
                    $output = $chunk->process($properties, $content);
                }
                break;
            case '@INLINE':
                $uniqid = uniqid();
                $chunk = $modx->newObject('modChunk', array('name' => "{$type}-{$uniqid}"));
                $chunk->setCacheable(false);
                $output = $chunk->process($properties, $source);
                break;
            case '@CHUNK':
            default:
                $chunk = null;
                if (!isset($_cache['@CHUNK'])) $_cache['@CHUNK'] = array();
                if (!array_key_exists($source, $_cache['@CHUNK'])) {
                    if ($chunk = $modx->getObject('modChunk', array('name' => $source))) {
                        $_cache['@CHUNK'][$source] = $chunk->toArray('', true);
                    } else {
                        $_cache['@CHUNK'][$source] = false;
                    }
                } elseif (is_array($_cache['@CHUNK'][$source])) {
                    $chunk = $modx->newObject('modChunk');
                    $chunk->fromArray($_cache['@CHUNK'][$source], '', true, true, true);
                }
                if (is_object($chunk)) {
                    $chunk->setCacheable(false);
                    $output = $chunk->process($properties);
                }
                break;
        }
        return $output;
    }
}
if (!function_exists('parseTpl')) {
    function parseTpl($tpl, $properties = null) {
        static $_tplCache;
        $_validTypes = array(
            '@CHUNK'
            ,'@FILE'
            ,'@INLINE'
        );
        $output = false;
        if (!empty($tpl)) {
            $bound = array(
                'type' => '@CHUNK'
                ,'value' => $tpl
            );
            if (strpos($tpl, '@') === 0) {
                $endPos = strpos($tpl, ' ');
                if ($endPos > 2 && $endPos < 10) {
                    $tt = substr($tpl, 0, $endPos);
                    if (in_array($tt, $_validTypes)) {
                        $bound['type'] = $tt;
                        $bound['value'] = substr($tpl, $endPos + 1);
                    }
                }
            }
            if (is_array($bound) && isset($bound['type']) && isset($bound['value'])) {
                $output = parseTplElement($_tplCache, $_validTypes, $bound['type'], $bound['value'], $properties);
            }
        }
        return $output;
    }
}
if (!function_exists('getDivisors')) {
    function getDivisors($integer) {
        $divisors = array();
        for ($i = $integer; $i > 1; $i--) {
            if (($integer % $i) === 0) {
                $divisors[] = $i;
            }
        }
        return $divisors;
    }
}
