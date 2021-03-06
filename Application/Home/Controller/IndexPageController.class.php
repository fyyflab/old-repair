<?php
namespace Home\Controller;

use Think\Controller;


class IndexPageController extends BasePageController
{


    public function index() {
        //调用checkComputer函数，判断是否注册电脑，如果没有则跳转到注册页面注册
        if (!checkComputer($_SESSION['user_id'])) {
            redirect('/Home/IndexPage/registerpc?token=' . $_GET['token']);
        }

        //调用checkOrder函数，判断是有订单未完成，如果有则跳转至【我的订单】
        if (checkOrder($_SESSION['user_id'])) {
            $this->error('您有尚未完成的订单，请确认完成后再进行报修！', 'Home/IndexPage/order?token=' . $_GET['token']);
        }

        //查找用户信息
        $a = M('userextend');
        $map['user_id'] = $_SESSION['user_id'];
        $userextend = $a->where($map)->find();

        //用户电脑信息
        $b = M('computer');
        $computer = $b->where($map)->order('time desc')->select();

        //用户类型 type
        $c = M('user');
        $user = $c->where($map)->find();

        $d = M('order');
        $ordermap['time'] = array('egt', get_week_start());
        $ordermap['status'] = array('in', '0,1,3,4');
        $order = $d->where($ordermap)->count();

        $set = M('set');
        $setting = $set->where('id=1')->find();
        if ($order >= $setting['week_max']) {
            $tips = "非会员请等待下周报修,会员不受此影响";
            $repair_status = "1";
        } else {
            $tips = "您可继续报修,当接机量超过" . $setting['week_max'] . "台，非会员本周内无法报修。";
            $repair_status = "0";
        }
        $this->assign('repair_status', $repair_status);
        $this->assign('order_count', $order);
        $this->assign('tips', $tips);
        $this->assign('user', $userextend);//赋值输出 userextend表 中的用户扩展信息
        $this->assign('computer_list', $computer);
        $this->assign('type', $user);
        $this->display();
    }


    /*------------增加电脑-------------*/
    public function registerpc() {
        $this->display();
    }


    /*-------------修改个人信息--------------*/
    public function set() {

        $a = M('userextend');
        $map['user_id'] = $_SESSION['user_id'];
        $userinfo = $a->where($map)->find();
        $this->assign('user', $userinfo);
        $this->display();


    }


    /*------------用户的订单-------------*/
    public function order() {

        //查找用户的所有订单
        $a = M('order');
        $map_order['user_id'] = $_SESSION['user_id'];
        if (!isset($_GET['p'])) {
            $_GET['p'] = 1;
        }
        if (!isset($_GET['pagesize'])) {
            $pagesize = 10;
        } else {
            $pagesize = $_GET['pagesize'];
        }
        $order = $a->where($map_order)->page($_GET['p'] . ',' . $pagesize)->order('time desc')->select();
        $count = $a->where($map_order)->count('order_id');
        $Page = new \Think\Page($count, $pagesize);
        $show = $Page->show();
        $this->assign('page', $show);
        //查找订单的所有信息
        foreach ($order as $key => $value) {
            $b = M('staff');
            $map_staff['staff_id'] = $value['staff_id'];
            $staff = $b->where($map_staff)->find();

            //如果系统已经分配技术员，则查找技术员信息
            //如果系统未经分配技术员，则不查找技术员信息
            if ($staff) {

                //技术员信息
                $c = M('userextend');
                $map_userextend['user_id'] = $staff['user_id'];
                $userextend = $c->where($map_userextend)->find();

                //用户电脑信息
                $d = M('computer');
                $map_computer['computer_id'] = $value['computer_id'];
                $computer = $d->where($map_computer)->find();

                //电脑报修原因
                $e = M('orderextend');
                $map_orderextend['order_id'] = $value['order_id'];
                $orderextend = $e->where($map_orderextend)->find();
                $info[$key] = array_merge($value, $userextend, $computer, $orderextend);

            } else {
                //用户电脑信息
                $d = M('computer');
                $map_computer['computer_id'] = $value['computer_id'];
                $computer = $d->where($map_computer)->find();
                //电脑报修原因
                $e = M('orderextend');
                $map_orderextend['order_id'] = $value['order_id'];
                $orderextend = $e->where($map_orderextend)->find();
                $info[$key] = array_merge($value, $computer, $orderextend);
            }

        }
        $this->assign('info', $info);
        $this->assign('count', $count);
        $this->display();

    }


}

?>