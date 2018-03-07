<?php

/**
 * @param string $param
 * @return string
 */
function L($param=""){
    global $app,$langArr,$_LANG;
    $langArr = $app ? $app->make('lang') : $_LANG;
    $args = func_get_args();
    $key = array_shift($args);

    $rs_lang = _L($langArr,$key);
    if (is_array($rs_lang)){
        return array_reduce(array_keys($rs_lang),function ($return,$key) use ($args,$rs_lang){
            $return[$key]=vsprintf($rs_lang[$key],$args);
            return $return;
        });
    }
    return vsprintf($rs_lang,$args);
}

function _L(&$arr=[],$param=''){
    $key = $param;
    if(strpos($key,'.') === false){
        return  isset($arr[$key]) ? $arr[$key] : null;
    }else{
        $keys = explode('.',$key,2);
        return _L($arr[$keys[0]],$keys[1]);
    }
}

function array_column_multi(array $input, array $column_keys) {
    $result = array();
    $column_keys = array_flip($column_keys);
    foreach($input as $key => $el) {
        $result[$key] = array_intersect_key($el, $column_keys);
    }
    return $result;
}

function array_modify_multi(&$v, $k, $kname){
    $v = array_combine($kname, $v);
}
/**
 * get the language
 * @return mixed
 */
function lang()
{
    /**
     * @var $app \Core\Application
     */
    global $app;
    return call_user_func_array(array($app->make('lang'), 'get'), func_get_args());
}
