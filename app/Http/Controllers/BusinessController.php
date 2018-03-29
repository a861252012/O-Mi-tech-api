<?php

namespace App\Http\Controllers;

use App\Models\Agents;
use App\Models\Domain;
use App\Models\HostAudit;
use App\Models\Redirect;
use App\Models\UserExtends;
use App\Models\Users;
use App\Services\User\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Core\Exceptions\NotFoundHttpException;
use Illuminate\Support\Facades\DB;

class BusinessController extends Controller
{
    /**
     * 招募页面
     *
     * @param $id int 招募页面
     * @return \Core\Response
     */
    public function index($act)
    {
        if (!in_array($act, array('joining', 'agreement','signup','join'))) {      //TODO 考虑删除'join'
            throw new NotFoundHttpException();
        }

        $var = array();
        if( $act == 'signup'){
            return $this->signup();
        }
        if( $act == 'agreement' ){
            $var['login'] = Auth::id();
        }
        return $this->render('Business/'.$act,$var);
    }

    /**
     * 主播申请入驻方法
     * @author D.C
     * @update 2014.12.03
     * @return Response
     */
    public function signup(){
        if (Auth::guest())
            return new  RedirectResponse('/index.html');

        $user = $this->userInfo;
        if( $this->make('request')->IsMethod('POST') && $this->make('request')->get('handle') == 'signin' ){
            return $this->_ajaxSigninHandle($user);
        }
        return $this->render('Business/signup', array('user'=>$user));
    }

    /**
     * 推广链接
     * @author TX
     * @return Respose
     */
    public function extend($url=''){
        //$domain = $this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoDomain')->findOneBy(array('url'=>$url,'status'=>0));

        //查询域名表
        $domain = Domain::where('url','=',$url)->where('status','=',0)->first();
        //不存在，返回首页
        if(empty($domain)||!$domain->exists){
            return $this->render('Business/extend', array('extendUrl'=>'/'));
        }

        //$agent = $this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoAgents')->findOneBy(array('did'=>$domain->getId(),'status'=>0));

        //通过域名查询对应的代理列表（did为对应的domain id）
        $agent = Agents::where('did','=',$domain->id)->where('status','=',0)->first();
//        die(var_export($agent));
        //如果不存在，返回首页
        if(empty($agent)||!$agent->exists){
            return $this->render('Business/extend', array('extendUrl'=>'/'));
        }

        //获取url数组
        $arrUrl = $this->getURL($domain->id);

        /**
         * 新增条件判断
         * Update by Young
         */
        if (empty($_GET['dir'])) {
            $var['extendUrl'] =$arrUrl . '?agent=' . $url;
//            die(var_export($var));
            return $this->render('Business/extend', $var);
        }

        //参数判断，dir跳转方向，用于跳转到直播间的功能
        if (!empty($_GET['dir']) && $_GET['dir'] === 'room') {
            /**
             * 跳转到一个正在直播的主播房间中去
             */
            $flashVer = $this->make('redis')->get('flash_version');
            !$flashVer && $flashVer = 'v201504092044';
            $rooms = $this->make('redis')->get('home_js_data_'.$flashVer);
            $aRandRooms = [];
            if($rooms) {
                $rooms = json_decode(str_replace(array('cb(', ');'), array('', ''), $rooms), true);
                /**
                 * 取出在直播的，不为密码房间的，不为限制房间的 房间用于随机一个
                 */
                foreach ($rooms['rec'] as $aRoom) {
                    if ($aRoom['enterRoomlimit'] == 0 && $aRoom['live_status'] == 1 && $aRoom['tid'] == 1) {
                        $aRandRooms[] = $aRoom;
                    }
                }
            }
//die(var_export($aRandRooms));
            /**
             * 当有符合直播状态的主播时就跳转到主播房间 TODO 异常处理
             */
            if(!empty($aRandRooms)){
                // 解析域名
                $aUrlParse = parse_url($arrUrl);
                $sVDomain = $aUrlParse['host'];
                $aRandRoom = $aRandRooms[mt_rand(0,count($aRandRooms)-1)];
//                die(var_export($aRandRoom));
                $var['extendUrl'] = 'http://'.$sVDomain.'/'.$aRandRoom['rid'].'?agent='.$url;
//                die(var_export($var));
            }else {
                $var['extendUrl'] = $arrUrl . '?agent=' . $url;
            }

            return $this->render('Business/extend', $var);
        }

        //所有不符合条件的
        return $this->render('Business/extend', array('extendUrl'=>'/'));

    }

    /**
     * 获取域名
     * @param $did
     * @return int
     */
    public function getURL($did = 0)
    {
        $ret = '/';
        //先查找rid为空的
        $redirect = Redirect::where('did', $did)->whereRaw('(rid is null or rid = \' \')')->normal()->first();
        if ($redirect && $redirect->exists) {
            $ret = '/';
        } else {
            //再随机取rid不为空的
            $redirect = Redirect::where('did', $did)->normal()->orderByRaw('rand()')->first();
            if ($redirect && $redirect->exists) {
                $rurl = Domain::where('id', $redirect->rid)->normal()->first();
                $ret = $rurl->url;
            } else {
                return 0;
            }
        }
        $ret='http://'.rtrim(preg_replace('/^http:\/\//','',$ret,1),'/');
        Domain::whereId($redirect->did)->normal()->increment('click');
        Domain::whereId($redirect->rid)->normal()->increment('click');
        return $ret;
    }


    /**
     * ajax提交申请主播功能
     * @author D.C
     * @update 2014-12-03
     * @param $user
     * @return JsonResponse
     */
    private function _ajaxSigninHandle($user){

        $data = $this->make('request')->request->all();

        //TODO 重复判断考虑删除
        if(!$user || $user['uid']<1){
            return new JsonResponse( array('code'=>100,'info'=>'对不起，你未登录，请登录后申请主播功能！') );
        }

        if ($user['roled']==3){
            return new JsonResponse( array('code'=>101,'info'=>'对不起，你已申请了主播功能！') );
        }


        //判断资料填写是否完整
        if( sizeof(array_filter(array_values($data))) <= sizeof(array_keys($data)) - 2 ){
            return new JsonResponse( array('code'=>102,'info'=>'请把资料填写完整!') );
        }

        //检查是否已申请过主播
        $VideoUserExtends = UserExtends::where('uid',$user['uid'])->first();
        $VideoHostAudit = HostAudit::where('host_id',$user['uid'])->orderBy('auid','DESC')->first();

        if(count($VideoHostAudit)>0){

            switch($VideoHostAudit->status){
                case '0':
                    return new JsonResponse( array('code'=>103,'info'=>'对不起，你已申请了主播功能,请等待审核！') );
                    break;

                case '1':
                    $Message = '你之前的主播身份已被取消，现重新提交申请成功，请等待审核';
                    break;

                case '2':
                    $Message = '你之前的申请已被驳回，现重重新提交申请，请等待审核！';
                    break;
            }

        }
        //更新用户信息
        $this->setUserField(array('birthday'=>$data['birthday'],'sex'=>$data['sex']), $user['uid']);

        //播入用户扩展信息表
        if(!$VideoUserExtends ){
            $VideoUserExtends = new UserExtends();
        }

        $VideoUserExtends->uid=$user['uid'];
        $VideoUserExtends->realname=htmlentities($data['realname']);
        $VideoUserExtends->phone=htmlentities($data['phone']);
        $VideoUserExtends->qq=htmlentities($data['qq']);
        $VideoUserExtends->bankname=htmlentities($data['bankname']);
        $VideoUserExtends->banknumber=htmlentities($data['banknum']);
        $VideoUserExtends->bankaddress=htmlentities($data['bankaddr']);

        $VideoUserExtends->save();

        //插入后台审核表
        //if(!$VideoHostAudit){
        $VideoHostAudit = new HostAudit();
        //}
        $VideoHostAudit->host_id = ($user['uid']);
        $VideoHostAudit->serial = (time().'-'.$user['uid']);
        $VideoHostAudit->ctime = ( new \DateTime("now") );
        $VideoHostAudit->is_del =(1);
        $VideoHostAudit->status = (0);

        $VideoHostAudit->save();
        if( isset($Message) ){
            return new JsonResponse( array('code'=>0,'info'=>$Message) );
        }else{
            return new JsonResponse( array('code'=>0,'info'=>'提交申请成功，请耐心等待审核。') );
        }
    }
    /**
     * chang user info note: nickname   TODO 事务,注释待删除
     * @param $criteria
     * @param $uid
     * @return bool
     */
    public function setUserField($criteria,$uid){
        if(empty($criteria)){
            return false;
        }
//        $sql = '';
//        $nickname = false;
//        foreach( $criteria as $key=>$item ){
//            $sql .= $sql == ''?'`'.$key.'`="'.$item.'"':',`'.$key.'`="'.$item.'"';
//            if($key == 'nickname'){
//                $nickname = $item;
//            }
//        }
        //昵称重复
        $nickname = isset($criteria['nickname']) ? $criteria['nickname'] : false;
        $userServer = resolve(UserService::class)->setUser(Users::find($this->userInfo['uid']));
        if(!$userServer->checkNickNameUnique($nickname)){
            return false;
        }

//        if( isset($postData['nickname']) && ! $this->checkNameUnique(array('uid'=>$this->_ctrl_object->_uid,'nickname'=> $postData['nickname'])) ){
//            return false;
//        }
//        $sql = 'update `video_user` set '.$sql.' where uid='.$uid;
        return DB::transaction(function() use ($uid, $criteria,$nickname)
        {
            $updated = Users::where('uid','=',$uid)->update($criteria);

            $this->make('redis')->hMSet('huser_info:'.$uid,$criteria);
            if( $nickname ){
                $this->make('redis')->hSet('hnickname_to_id',$nickname,$uid);
            }
            if($updated){
                return true;
            }else{
                return false;
            }

        }); // suspend auto-commit


//        try {
//            $stmt = $this->_doctrine_em->getConnection()->prepare($sql);
//            $stmt->execute();
//            $this->_doctrine_em->getConnection()->commit();
//
//            $this->make('redis')->hMSet('huser_info:'.$uid,$criteria);
//            //$this->_redis_instace->hMSet('huser_info:'.$uid,$criteria);
//            if( $nickname ){
//                $this->make('redis')->hSet('hnickname_to_id',$nickname,$uid);
//            //    $this->_redisInstance->hSet('hnickname_to_id',$nickname,$uid);
//            }
//            return true;
//        } catch (\Exception $e) {
//            $this->_doctrine_em->getConnection()->rollback();
//            $this->_doctrine_em->close();
//            //throw $e;
//            return false;
//        }
    }

}