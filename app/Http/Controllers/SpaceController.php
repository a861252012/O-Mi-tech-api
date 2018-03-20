<?php


namespace App\Http\Controllers;


use App\Services\User\UserService;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Anchor;

/**
 *
 * Class SpaceController
 * @package Video\ProjectBundle\Controller
 * @author D.C
 *
 */
class SpaceController extends Controller{


    /**
     * 首页默认信息页面
     * @author D.C
     * @todo  待确认非主播用户是否有个人空间功能
     */
    public function index(){
        $uid = intval($this->make('request')->get('u')) ?:$this->make('request')->getSession()->get(self::SEVER_SESS_ID);

        if( !$uid ){
            throw $this->createNotFoundException();
            //return new response(json_encode(array('code'=>100,'info'=>'非法来源')));
        }

        $userServer = resolve(UserService::class);
        $vars['user'] = $userServer->getUserByUid($uid);

        /**
         * 按产品部要求，非主播无个人空间功能。
         * 判断用户是否存在及是否是主播，否则返回404异常。
         */
        if(!isset($vars['user']['uid']) || $vars['user']['roled']!=3){
            throw $this->createNotFoundException();
            //return new response(json_encode(array('code'=>101,'info'=>'该用户不存在')));
        }

        /**
         * 获取个人空间相册列表，进行瀑布流数据初始化。
         */
//        $gm = $this->getDoctrine()->getManager();
//        $queryBuilder = $gm->getRepository('Video\ProjectBundle\Entity\VideoAnchor')->createQueryBuilder('g')
//            ->select('g')
//            ->where('g.uid=:uid')
//            ->orderBy('g.jointime', 'DESC')
//            ->setParameter('uid',$uid)
//            ->getQuery();


        //瀑布流分页
        $thispage = $this->make("request")->get('page') ?: 1;

        //获取头像
        $vars['user']['headimg'] = $this->getHeadimg($vars['user']['headimg']);

        //获取星座
        $vars['user']['star'] = $vars['user']['birthday'] ? $this->getStarNames(date('m',strtotime($vars['user']['birthday']))) : null;

        //根据是否主播获取等级
        /*
        $level =  $vars['user']['roled']==3
                    ? $gm->getRepository('Video\ProjectBundle\Entity\VideoLevelExp')->findOneBy(array('levelId'=>$vars['user']['lv_exp']))
                    : $gm->getRepository('Video\ProjectBundle\Entity\VideoLevelRich')->findOneBy(array('levelId'=>$vars['user']['lv_rich']));
        */
//        $dm = new \Video\ProjectBundle\Service\DataModel($this);
        $level = $this->getLevelByRole($vars['user'],1);

        //$vars['user']['nextlevel'] = $level['lv_nums'];
        $vars['user']['highlight'] =  $level['lv_percent'];

        //获取用户所在地
        $vars['user']['address'] = $this->getArea($vars['user']['province'],$vars['user']['city'],$vars['user']['county']) ?: null;

        //获取关注数
        $vars['attenCount'] = $this->getUserAttensCount($uid,false);
        //分页
//        $vars['pagination'] =  \Video\ProjectBundle\Service\Pagination::page($queryBuilder, $thispage,6);
        $result = Anchor::where('uid',$uid)->orderBy('jointime','DESC')->paginate();
        //返回图片列表结果
        $vars['result'] = $result;

        //统计图片总数
        $vars['totals'] = count($vars['result']);

        //主播房间链接
        $vars['room'] = trim( $GLOBALS['REMOTE_JS_URL'],'/').'/'.$vars['user']['rid'];

        //房间直播状态
        $vars['liveStatus'] = $this->make('redis')->hget('hvediosKtv:'.$vars['user']['rid'],'status');

        //判断是否是瀑布流模式
        if($this->make('request')->get('handle')=='ajax'){
            return new Response($this->_getAjaxAnchor($uid, $vars['result']));
        }

        return  $this->render('Space/index', $vars);
    }

    /**
     * 瀑布流返回HTML数据。
     * @param $uid
     * @param $result
     * @return null|string
     * @author D.C
     * @update 2014.11.20
     * @example $this->_getAjaxAnchor( uid, result )
     */
    private function _getAjaxAnchor($uid,$result){
        $imghost = trim( $this->container->config['config.REMOTE_PIC_URL'],'/').'/';
        $container = null;
        foreach($result as $a){
        $name = $a['name'] ?: '无';
        $summary = $a['summary'] ?: '无';
        $jointime =  date('Y/m/d',$a['jointime']);
        $container .=<<<EOF
                            <div class="box list-imgBox">
                                <a href="{$imghost}{$a['file']}" title="名称：{$name} &nbsp;描述：{$summary}&nbsp; 时间：{$jointime}" rel="prettyPhoto[]"><img src="{$imghost}{$a['file']}?w=305" width="305"></a>
                                <p class="title"><span class="fl">{$a['name']}</span><span class="fr">{$jointime}</span></p>
                            </div>
EOF;
        }
        return $container;


    }

}