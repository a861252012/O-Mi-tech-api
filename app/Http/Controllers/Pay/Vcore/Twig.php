<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/25
 * Time: 11:16
 */

namespace Pay;

trait Twig {
    function render($tpl,$params){
        $config = include 'config/template.php';
        $loader = new \Twig_Loader_Filesystem ($config['template_dir']);
        $twig = new \Twig_Environment ($loader, array(
            'cache' => $config['cache_dir'],// twig生成的缓存的路径
            'debug' => $config['debug'], // twig的debug模式是否开启
        ));
        return ($twig->render($tpl . '.html.twig', $params, true));
    }

}