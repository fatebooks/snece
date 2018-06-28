<?php
global $_W,$_GPC;
$fans = $_W['fans'];
//检查LEVEL
if ($_W['account']['level']<4) {
    //不是认证服务号，需要借权
    if(empty($fans['nickname'])){
        load()->model('mc');
        $fans = mc_oauth_userinfo();
    }
}

$id = ((!empty($_GPC['id']) ? intval($_GPC['id']) : 0));
$op = ((!empty($_GPC['op']) ? strtolower($_GPC['op']) : 'display'));
$isok = false;
$message = '';
if ($id>0) {
    $sql = "select * from ".tablename('scene_luckydraw_organizers')." where `id`={$id} limit 1;";
    $rs = pdo_fetchall($sql);
    $settings = unserialize($rs[0]['settings']);
    $disp = $settings['disp'];
    $sour = intval($settings['sour']);
    if (($disp <> 'name') && ($disp <> 'phone')) {
        $op = 'post';
    }
    $sql = "select * from ".tablename('scene_luckydraw_sign')." where `c_id`={$id} and `openid`='{$_W['openid']}'";
    $rs = pdo_fetchall($sql);

    if (count($rs)<=0) {
        if (($op == 'post')&&($sour==2)) {
            $name = trim($_GPC['name']);
            $phone = trim($_GPC['phone']);

                $ali = $_GPC['data'];
                $u = array();
                $u['openid'] = $fans['openid'];
                $u['wechat_name'] = base64_encode($fans['nickname']);
                $u['image'] = $fans['avatar'];
                $u['c_id'] = $id;
                $u['timestamp'] = time();
                $u['name'] = $name;
                $u['phone'] = $phone;
                $u['datas']=iserializer($ali);
            
                if (pdo_insert('scene_luckydraw_sign',$u)) {
                    $isok = true;
                }else{
                    $message = "签到失败，请重试";
                }
        }
    }else{
        $isok = true;
//        $name = $rs[0][$disp];
        $name = $rs[0]['name'];
        $phone = $rs[0]['phone'];
        if ($name == base64_encode(base64_decode($name))) $name = base64_decode($name);
        $image = $rs[0]['image'];
        $message = "您已签到过了，签到信息为：<table class=\"table table-striped\"><tr><td>姓名:</td><td>{$name}</td></tr><tr><td>电话:</td><td>{$phone}</td></tr></table>";
    }
}

include $this->template('sign');

