<?php

// 定义web目录位置
if (!defined('BASEDIR')) {
    exit('File not found');
}
define('APP_DIR', BASEDIR);
define('PDO_DEBUG', false);
$redis = new \Redis();
$_W = include APP_DIR . '/Vcore/App/Config/config.php';  //database_host  database_port database_name database_user database_password

//irwin$_W = include APP_DIR.'/app/cache/cli-files/php-conf-cache.php';

/**
 * 初始化 主库pdo 对象实例
 * @param bool $newinstance 是否要创建新实例
 * @return object->PDO
 */
function pdo($newinstance = false)
{
    global $_W;
    if ($newinstance) {
        $dsn = 'mysql:host=' . $_W['database']['connections']['mysql']['host'] . ';port=' . $_W['database']['connections']['mysql']['port'] . ';dbname=' . $_W['database']['connections']['mysql']['database'];
        $username = $_W['database']['connections']['mysql']['username'];
        $password = $_W['database']['connections']['mysql']['password'];
        $opts = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
            PDO::ATTR_PERSISTENT => false);
        $db = new PDO(
            $dsn,
            $username,
            $password,
            $opts//非永久连接

        );
        $sql = "SET NAMES 'utf8';";
//        $result=$db->exec($sql);irwin
        $db->exec("SET sql_mode='';");
        if (PDO_DEBUG) {
            $info = array();
            $info['sql'] = $sql;
            $info['error'] = $db->errorInfo();
            pdo_debug(false, $info);
        }
        return $db;
    } else {
        if (empty($_W['pdo'])) {
            $_W['pdo'] = $GLOBALS['pdo'] = pdo(true);
        }
        return $_W['pdo'];
    }
}

/**
 * 初始化 从库pdo 对象实例
 * @param bool $newinstance 是否要创建新实例
 * @return object->PDO
 */
function pdo_slave($newinstance = false)
{
    global $_W;
    if ($newinstance) {
        $dsn = "mysql:dbname={$_W['config']['db2']['database']};host={$_W['config']['db2']['host']}";
        $dbclass = '';
        $options = array();
        if (class_exists('PDO')) {
            if (extension_loaded("pdo_mysql") && in_array('mysql', PDO::getAvailableDrivers())) {
                $dbclass = 'PDO';
                $options = array(PDO::ATTR_PERSISTENT => $_W['config']['db']['pconnect']);
            } else {
                $dbclass = '_PDO';
            }
        } else {
            $dbclass = 'PDO';
        }
        $db = new $dbclass($dsn, $_W['config']['db2']['user_name'], $_W['config']['db2']['password'], $options);
        $sql = "SET NAMES '{$_W['config']['db']['charset']}';";
        $db->exec($sql);
        $db->exec("SET sql_mode='';");
        if (PDO_DEBUG) {
            $info = array();
            $info['sql'] = $sql;
            $info['error'] = $db->errorInfo();
            pdo_debug(false, $info);
        }
        return $db;
    } else {
        if (empty($_W['pdo2'])) {
            $_W['pdo2'] = $GLOBALS['pdo2'] = pdo_slave(true);
        }
        return $_W['pdo2'];
    }
}

/**
 * 执行一条非查询语句
 *
 * @param string $sql
 * @param array or string $params
 * @return mixed
 *          成功返回受影响的行数
 *          失败返回FALSE
 */
function pdo_query($sql, $params = array())
{
    if (empty($params)) {
        $result = pdo()->exec($sql);
        if (PDO_DEBUG) {
            $info = array();
            $info['sql'] = $sql;
            $info['error'] = pdo()->errorInfo();
            pdo_debug(false, $info);
        }
        return $result;
    }
    $statement = pdo()->prepare($sql);
    $result = $statement->execute($params);
    if (PDO_DEBUG) {
        $info = array();
        $info['sql'] = $sql;
        $info['params'] = $params;
        $info['error'] = $statement->errorInfo();
        pdo_debug(false, $info);
    }
    if (!$result) {
        return false;
    } else {
        return $statement->rowCount();
    }
}

/**
 * 执行SQL返回第一个字段
 *
 * @param string $sql
 * @param array $params
 * @param int $column 返回查询结果的某列，默认为第一列
 * @return mixed
 */
function pdo_fetchcolumn($sql, $params = array(), $column = 0)
{
    $statement = pdo_slave()->prepare($sql);
    $result = $statement->execute($params);
    if (PDO_DEBUG) {
        $info = array();
        $info['sql'] = $sql;
        $info['params'] = $params;
        $info['error'] = $statement->errorInfo();
        pdo_debug(false, $info);
    }
    if (!$result) {
        return false;
    } else {
        return $statement->fetchColumn($column);
    }
}

/**
 * 执行SQL返回第一行
 *
 * @param string $sql
 * @param array $params
 * @return mixed
 */
function pdo_fetch($sql, $params = array())
{
    $statement = pdo()->prepare($sql);
    $result = $statement->execute($params);
    if (PDO_DEBUG) {
        $info = array();
        $info['sql'] = $sql;
        $info['params'] = $params;
        $info['error'] = $statement->errorInfo();
        pdo_debug(false, $info);
    }
    if (!$result) {
        return false;
    } else {
        return $statement->fetch(pdo::FETCH_ASSOC);
    }
}

/**
 * 执行SQL返回全部记录
 *
 * @param string $sql
 * @param array $params
 * @return mixed
 */
function pdo_fetchall($sql, $params = array(), $keyfield = '')
{
    $statement = pdo()->prepare($sql);
    $result = $statement->execute($params);
    if (PDO_DEBUG) {
        $info = array();
        $info['sql'] = $sql;
        $info['params'] = $params;
        $info['error'] = $statement->errorInfo();
        pdo_debug(false, $info);
    }
    if (!$result) {
        return false;
    } else {
        if (empty($keyfield)) {
            return $statement->fetchAll(pdo::FETCH_ASSOC);
        } else {
            $temp = $statement->fetchAll(pdo::FETCH_ASSOC);
            $rs = array();
            if (!empty($temp)) {
                foreach ($temp as $key => &$row) {
                    if (isset($row[$keyfield])) {
                        $rs[$row[$keyfield]] = $row;
                    } else {
                        $rs[] = $row;
                    }
                }
            }
            return $rs;
        }
    }
}

/**
 * 更新记录
 *
 * @param string $table
 * @param array $data
 *          要更新的数据数组
 *          array(
 *              '字段名' => '值'
 *          )
 * @param array $params
 *          更新条件
 *          array(
 *              '字段名' => '值'
 *          )
 * @param string $gule
 *          可以为AND OR
 * @return mixed
 */
function pdo_update($table, $data = array(), $params = array(), $gule = 'AND')
{
    global $_W;
    $fields = pdo_implode($data, ',');
    $condition = pdo_implode($params, $gule);
    $params = array_merge($fields['params'], $condition['params']);
    $sql = "UPDATE $table SET {$fields['fields']}";
    $sql .= $condition['fields'] ? ' WHERE ' . $condition['fields'] : '';
    return pdo_query($sql, $params);
}

/**
 * 更新记录
 *
 * @param string $table
 * @param array $data
 *          要更新的数据数组
 *          array(
 *              '字段名' => '值'
 *          )
 * @param boolean $replace
 *          是否执行REPLACE INTO
 *          默认为FALSE
 * @return mixed
 */
function pdo_insert($table, $data = array(), $replace = FALSE)
{
    global $_W;
    $cmd = $replace ? 'REPLACE INTO' : 'INSERT INTO';
    $condition = pdo_implode($data, ',');
    return pdo_query("$cmd $table SET {$condition['fields']}", $condition['params']);
}

/**
 * 删除记录
 *
 * @param string $table
 * @param array $params
 *          更新条件
 *          array(
 *              '字段名' => '值'
 *          )
 * @param string $gule
 *          可以为AND OR
 * @return mixed
 */
function pdo_delete($table, $params = array(), $gule = 'AND')
{
    global $_W;
    $condition = pdo_implode($params, $gule);
    $sql = 'DELETE FROM ' . $table;
    $sql .= $condition['fields'] ? ' WHERE ' . $condition['fields'] : '';
    return pdo_query($sql, $condition['params']);
}

/**
 * 返回lastInsertId
 *
 */
function pdo_insertid()
{
    return pdo()->lastInsertId();
}

/**
 * 转换PDO的字段与参数列表
 *
 * @param array or string $params
 *          可以是数组或字符串
 *          是字符串直接返回
 * @param string $glue
 *          字段间的分隔符
 *          可以为逗号（,）或是 AND OR 应对不同的SQL
 * @return mixed
 *          array(
 *              'fields' 字段列表或条件
 *              'params' 参数列表
 *          )
 */
function pdo_implode($params, $glue = ',')
{
    $result = array('fields' => ' 1 ', 'params' => array());
    $split = '';
    if (!is_array($params)) {
        $result['fields'] = $params;
        return $result;
    }
    if (is_array($params)) {
        $result['fields'] = '';
        foreach ($params as $fields => $value) {
            $result['fields'] .= $split . "`$fields` =  :$fields";
            $split = ' ' . $glue . ' ';
            $result['params'][':' . $fields] = is_null($value) ? '' : $value;
        }
    }
    return $result;
}

/**
 * 获取pdo操作错误信息列表
 * @param bool $output 是否要输出执行记录和执行错误信息
 * @param array $append 加入执行信息，如果此参数不为空则 $output 参数为 false
 * @return array
 */
function pdo_debug($output = true, $append = array())
{
    static $errors = array();
    if (!empty($append)) {
        $output = false;
        array_push($errors, $append);
    }
    if ($output) {
        print_r($errors);
    } else {
        if (!empty($append['error'][1]) && $append['error'][1] != null) {
            if ($append['error'][1] == 1062) {//优惠券唯一
                $info = explode(' ', $append['error'][2]);
                $coupon_no = $info[2];
                message('优惠券: ' . $coupon_no . ' 已存在,请去掉后再提交');
            } else {
                message($append, '', 'sql');
            }
        }
    }
    return $errors;
}

/**
 * 执行SQL文件
 */
function pdo_run($sql)
{
    if (!isset($sql) || empty($sql)) return;

    //$sql = str_replace("\r", "\n", str_replace(' ims_', ' '.$GLOBALS['_W']['config']['db']['tablepre'], $sql));
    $ret = array();
    $num = 0;
    foreach (explode(";\n", trim($sql)) as $query) {
        $ret[$num] = '';
        $queries = explode("\n", trim($query));
        foreach ($queries as $query) {
            $ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0] . $query[1] == '--') ? '' : $query;
        }
        $num++;
    }
    unset($sql);
    foreach ($ret as $query) {
        $query = trim($query);
        if ($query) {
            pdo_query($query);
        }
    }
}


$db = pdo();
function pdoAdd($arr,$tablename){
    $new_arr = array();
    $cols1 = $cols2 = '';
    foreach($arr as $key=>$item){
        $new_arr[':'.$key] = $item;
        $cols1 .= $cols1 ==''?'`'.$key.'`':',`'.$key.'`';
        $cols2 .= $cols2 ==''?':'.$key.'':',:'.$key.'';
    }
    $sql = 'INSERT INTO `'.$tablename.'` ('.$cols1.') VALUES ('.$cols2.')';
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($new_arr);
    return $GLOBALS['pdo']->lastInsertId();
}
