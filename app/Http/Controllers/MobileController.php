<?php


namespace App\Http\Controllers;

class MobileController extends Controller
{
    /**
     * 移动端首页
     * @author Young <Young@wisdominfo.my>
     * @return index page
     */
    public function index()
    {
        return $this->render('Mobile/index', array());
    }

    /**
     * 移动端排行榜
     * @author Young <Young@wisdominfo.my>
     * @return rank page
     */
    public function rank()
    {
        return $this->render('Mobile/rank', array());
    }

    /**
     * 移动端登录
     * @author Young <Young@wisdominfo.my>
     * @return login page
     */
    public function login()
    {
        return $this->render('Mobile/login', array());
    }


    /**
     * 移动端注册
     * @author Young <Young@wisdominfo.my>
     * @return register page
     */
    public function register()
    {
        return $this->render('Mobile/register', array());
    }
}