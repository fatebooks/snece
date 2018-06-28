<?php

defined('IN_IA') or exit('Access Denied');
require 'payments/alipaytransfer/aop/AopClient.php';
require 'payments/alipaytransfer/aop/request/AlipayFundTransToaccountTransferRequest.php';
require 'payments/alipaytransfer/aop/SignData.php';
require 'payments/wxpay.php';
class Scene_luckydrawModuleSite extends WeModuleSite
    {
    public $id,$uniacid;
    public $sour_ary = array(0 => "后台导入数据",1=>"手工录入抽奖号",2=>"微信扫码(开放)",3=>"测试数据");

    public function __construct()
    {
        global $_W, $_GPC;
        $this->id = !empty($_GPC['id'])?intval($_GPC['id']):0;
        $this->uniacid = $_W['uniacid'];
    }
    public function doWebOrganizers()
    {
        global $_W, $_GPC;
        $title = '现场活动设置';
        $orgwhere = ['id'=>$this->id,'uniacid'=>$_W['uniacid']];
        $op = !empty($_GPC['op']) ? strtolower($_GPC['op']) : 'false';
        $id = !empty($_GPC['id'])?intval($_GPC['id']):0;
        $all = !empty($_GPC['all'])?intval($_GPC['all']) : 0;
        $orgarr = array('id'=>$id,'uniacid'=>$_W['uniacid']);
        switch ($op){
            case 'add':
                if($this->id>0){
                    $rs = pdo_get('scene_luckydraw_organizers', array('id' =>$id,'uniacid'=>$this->uniacid,'isend'=>0));
                    if($rs){
                       $settings = unserialize($rs['settings']);
                    }else{
                        message('未找到修改数据，请认真核对','referer','error');
                    }
                    $resule = pdo_get('scene_luckydraw_draw', array('c_id' =>$id));
                    if($resule)
                        message('已完成抽奖活动无法修改','referer','error');
                }
                $submittext = $this->id ==0?'新增':'修改';
                $tpl = 'web/organizers/shows';
                break;
            case 'edit':

                $settings = !empty($_POST['settings'])?$_POST['settings']:array();
                if (empty($settings['name']) || empty($settings['items'])) {
                    message('举办方名称以及奖项必须设置','referer','error');
                }
                if ($settings) {
                    $u = array();
                    if ($_GPC['id'] == 0) {
                        //空的，插入
                        $u['uniacid'] = $_W['uniacid'];
                        $u['timestamp'] = strtotime($settings['date']);
                        $u['name'] = $settings['name'];
                        $u['sour'] = intval($settings['sour']);
                        $u['isend'] = false;
                        $u['settings'] = serialize($settings);
                        $result = pdo_insert('scene_luckydraw_organizers',$u);
                    }else{
                        $rs = pdo_get('scene_luckydraw_organizers', array('id' => $this->id,'uniacid'=>$this->uniacid));
                        if (count($rs)<=0) {
                            message('修改的数据未找到，请确认是否已删除！','referer','error');
                        }
                        //有ID修改，先较验ID是否是本公众号的
                        $u['timestamp'] = strtotime($settings['date']);
                        $u['name'] = $settings['name'];
                        $u['sour'] = intval($settings['sour']);
                        $u['settings'] = serialize($settings);
                        $result = pdo_update('scene_luckydraw_organizers',$u,$orgwhere);
                    }

                }else{
                    message('提交的数据为空，请重新检查','referer','error');
                }
                message('数据保存成功',$this->createWebUrl('Organizers'),'success');
                break;
            case 'del':
                if ($id>0) {
                    $rs = pdo_get('scene_luckydraw_organizers', array('id' =>$id,'uniacid'=>$this->uniacid));
                    if (count($rs)>0) {
                        pdo_delete('scene_luckydraw_draw',array('c_id'=>$this->id));
                        pdo_delete('scene_luckydraw_sign',array('c_id'=>$this->id));
                        pdo_delete('scene_luckydraw_organizers',$orgarr);
                    }else{
                        message('未找到匹配数据！','referer','error');
                    }
                    message('所有相关数据已成功删除！',$this->createWebUrl('settings'),'success');
                }else{
                    message('未找到匹配数据！','referer','error');
                }
                break;
            case  'export':
                $rs = pdo_get('scene_luckydraw_organizers', array('id' =>$id,'uniacid'=>$this->uniacid,'isend'=>0));
                if ($rs==0) {
                    message('未找到匹配数据！','referer','error');
                }
                $settings = unserialize($rs['settings']);
                $sql = "SELECT d.id,d.text,d.time,d.item_id,s.name,s.phone FROM " . tablename('scene_luckydraw_draw') . " d  JOIN ". tablename('scene_luckydraw_sign') ." s ON d.uid=s.id AND d.c_id={$this->id}";

                $rs = pdo_fetchall($sql);
                if(!$rs){
                    message('未找到中奖数据！,无法导出','referer','error');
                }
                foreach ($rs as $key=>$value){
                    $rs[$key]['time']=date('Y-m-d H:i:s',$value['time']);

                }
                $data = $this->buildlogdata($rs);
                if ($data) {
                    $name = "Awawd-".date('Ymd').".csv";
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="'.$name.'"');
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . strlen($data));//获取文件大小
                    echo $data;
                }
                exit();
                break;
            case  'end':
                $rs = pdo_get('scene_luckydraw_organizers', array('id' =>$id,'uniacid'=>$this->uniacid,'isend'=>0));
                if (count($rs)>0) {
                    $u = array('isend'=>true);
                    pdo_update('scene_luckydraw_organizers',$u,array('id'=>$id));
                    message('数据已成功修改',$this->createWebUrl('settings'),'success');
                }else{
                    message('未找到匹配数据','referer','error');
                }
                break;
            case  'select':
                $rs = pdo_get('scene_luckydraw_organizers', array('id' =>$id,'uniacid'=>$this->uniacid,'isend'=>0));

                if ($rs==0) {
                    message('未找到活动数据！','referer','error');
                }
                $sql = "SELECT d.id,d.text,d.url,d.time,d.status,s.wechat_name,s.name,s.phone FROM " . tablename('scene_luckydraw_draw') . " d  JOIN ". tablename('scene_luckydraw_sign') ." s ON d.uid=s.id AND d.c_id={$this->id}";

                $lists = pdo_fetchall($sql);
                if(!$lists){
                    message('未找到中奖数据!','referer','error');
                }
                $money = $lists[0]['status'];
                $tpl = 'web/organizers/select';
                break;
            case 'wxinfo':
                $sql = "SELECT d.id,d.text,d.url,s.wechat_name,s.name,s.phone,w.status,w.money,w.status,w.paytype FROM " . tablename('scene_luckydraw_draw') . " d  JOIN ". tablename('scene_luckydraw_sign') ." s ON d.uid=s.id AND d.c_id={$this->id} JOIN". tablename('scene_luckydraw_wxmoney') ."w ON d.id=w.did";
                $lists = pdo_fetchall($sql);
                if(!$lists){
                    message('未找到红包数据!','referer','error');
                }
                $tpl = 'web/organizers/wxinfo';
                break;
            case 'getuserlist':
                $this->getuserlist();   //获取抽奖用户
                break;
            case 'getsetting':
                $this->getsetting();    //获取活动奖项
                break;
            case 'activitys':
                //获取奖项用户数据
                $this->activitys();
                break;
            case 'money':
                //保存红包数据
                $this->actmoney();
                break;
            case 'getwinner':
                //显示抽奖数据
                $this->getwinner();
                break;
            case 'winner':
                //红包发放
                $this->wxsnece();
                break;
            case 'aa':
                $this->aas();die();
                break;
            default:
                if ($all) {
                    $where = '1 ';
                }else{
                    $where = "`isend` = 0 ";
                }
                $where .= " AND `uniacid` = {$_W['uniacid']} ";
                $sql = "SELECT * FROM ".tablename('scene_luckydraw_organizers')." WHERE {$where} ORDER BY `timestamp`";
                $rs = pdo_fetchall($sql);
                $count = count($rs);
                $tpl = 'web/organizers/showsadd';
                break;
        }
        include $this->template($tpl);
    }

	public function doMobileLuckydraw()
    {

	}
    public function doWebSign()
    {
        global $_W, $_GPC;
        $title = '现场活动签到';
        $op = !empty($_GPC['op']) ? strtolower($_GPC['op']) : 'false';
        $id = !empty($_GPC['id'])?intval($_GPC['id']):0;
        $tpl = 'web/sign/';

        switch ($op){
            case 'signinfo' :
                $rs = pdo_get('scene_luckydraw_organizers', array('id' =>$id,'uniacid'=>$this->uniacid,'isend'=>0));
                if(empty($rs))
                    message('未找到匹配数据或活动以结束','referer','error');
                $settings = unserialize($rs['settings']);
                $sql = "SELECT * FROM ".tablename('scene_luckydraw_sign')." WHERE `c_id`={$id} ORDER BY `timestamp`";
                $lists = pdo_fetchall($sql);
                foreach ($lists as $key=>$value){
                    if(!empty($value['datas'])){
                       $lists[$key]['datas']= iunserializer($value['datas']);
                    }
                }
//                var_dump($lists);
                $tpl .= 'signinfo';
                break;
            case 'scavenging' :
                $rs = pdo_get('scene_luckydraw_organizers', array('id' =>$id,'uniacid'=>$this->uniacid,'isend'=>0));
                if(!$rs)
                    message('未找到匹配数据或活动以结束','referer','error');
                $name = $rs['name'];
                $settings = unserialize($rs['settings']);
                $attenturl = $_W['attachurl'];
                $bg = (isset($settings['signbg'])&&$settings['signbg']?$attenturl.$settings['signbg']:"/addons/scene_luckydraw/template/web/draw/background/04.jpg");
                $url = $this->createMobileUrl('sign',array('id'=>$id));
                $url = murl('entry', array('id'=>$id,'do'=>'sign','m'=>strtolower($this->modulename)), true, true);
                
                $tpl .= 'savesing';
                break;
            case 'clear':
                //清除已签到数据
                $sql = "select * from ".tablename('scene_luckydraw_draw'). " where `c_id`={$id}";
                $rs = pdo_fetchall($sql);
                if (empty($rs)) {
                    pdo_delete('scene_luckydraw_sign',array('c_id'=>$id));
                    message('签到及中奖数据已清除','referer','success');
                }else{
                    message('已抽奖的活动无法删除签到','referer','error');
                }
                break;
            case 'bgsign':
                //后台签到
                $sub = ((!empty($_GPC['sub']) ? strtolower($_GPC['sub']) : ''));
                if ($sub) {
                    //提交
                    $up = $_GPC['up'];
                    $_SESSION['up_data'] = $up;
                    if (!$up['name']) {
                        message("姓名不能为空",'referer','error');
                    }
                    if ($this->checkdump($id,$up['ld_id'],$up['phone'])) {
                        message("电话号码或抽奖号重复，请检查后重试",'referer','error');
                    }
                    if ($up['image']) {
                        $up['image'] = $_W['attachurl'].$up['image'];
                    }
                    $up['timestamp'] = time();
                    $up['c_id'] = $id;
                    pdo_insert('scene_luckydraw_sign',$up);
                    unset($_SESSION['up_data']);
                    message('数据已成功保存','referer','success');
                }
                if (isset($_SESSION['up_data'])) {
                    $up = $_SESSION['up_data'];
                }
                $up['image'] = str_replace($_W['attachurl'],'',$up['image']);
                $tpl = 'web/sign/bgsign';
                break;
            case 'upload':
                //后台导入
                if (isset($_FILES['file'])) {
                    $name = $_FILES['file']['tmp_name'];
                    $data = '';
                    if (file_exists($name)) $data = file_get_contents($name);
                    if ($data) {
                        //有数据
                        $data = str_replace("\r\n","\n",$data);
                        $data = iconv('GBK','UTF-8//IGNORE',$data);
                        $ary = explode("\n",$data);
                        $count = count($ary);
                        $p = '/^([^,]*),([^,]*),([^,]*),([^,]*),([^,]*)$/i';
                        $u = array();
                        $u['c_id'] = $id;
                        $u['timestamp'] = time();
                        $nums = 0;
                        $dump = 0;
                        for ($i=1;$i<=$count;$i++) {
                            if (preg_match($p,$ary[$i],$m)) {
                                //抽奖号(id),姓名(name),昵称(nickname),电话(phone),头像(image)
                                $u['ld_id'] = trim(ltrim($m[1],"'"));
                                $u['name'] = trim(ltrim($m[2],"'"));
                                $u['wechat_name'] = base64_encode(trim(ltrim($m[3],"'")));
                                $u['phone'] = trim(ltrim($m[4],"'"));
                                $u['image'] = trim(ltrim($m[5],"'"));
                                if ($this->checkdump($id,$u['ld_id'],$u['phone'])) {
                                    //已有重复的
                                    $dump ++;
                                }else{
                                    $nums ++;
                                    pdo_insert('scene_luckydraw_sign',$u);
                                }
                            }
                        }
                        $add = "{$nums}条记录成功导入；";
                        if ($dump) {
                            $add .= "{$dump}条记录因重复未导入；";
                        }
                        message("所有数据上传完成。[{$add}]",'referer','success');
                    }else{
                        message('上传失败，请重新上传。','referer','error');
                    }
                }else{
                    message('未选择需要上传的文件','referer','error');
                }
                break;
            case 'down':
                //下载模板
//                    var_dump(MODULE_ROOT.'/template/web/down/templates.csv');die;
                $data = file_get_contents(MODULE_ROOT.'/template/web/down/templates.csv');
                if ($data) {
                    $name = "template-".date('Ymd').".csv";
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="'.$name.'"');
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . strlen($data));//获取文件大小
                    echo $data;
                    exit();
                }else{
                    message('未找到模板数据，请联系管理员!','referer','error');
                }
                break;
            default:
                $sql = "SELECT * FROM ".tablename('scene_luckydraw_organizers')."  WHERE `isend`= 0 AND `uniacid`= {$_W['uniacid']} ORDER BY  `timestamp`";
                $rs = pdo_fetchall($sql);
                $count = count($rs);
                $tpl .= 'index';
                break;
        }
        include $this->template($tpl);
    }
    public function doWebDraw() {
        global $_W, $_GPC;
        $op = !empty($_GPC['op']) ? strtolower($_GPC['op']) : 'false';
        $id = !empty($_GPC['id'])?intval($_GPC['id']):0;
        $attenturl = $_W['attachurl'];
        $tpl = 'web/draw/';
        $basedir = '/addons/scene_luckydraw/template/web/draw/';
        switch ($op){
            case 'goods':
                $rs = pdo_get('scene_luckydraw_organizers', array('id' =>$id,'uniacid'=>$this->uniacid,'isend'=>0));
                if(!$rs){
                    message('暂未找到数据','referer','error');
                }
                $res = pdo_get('scene_luckydraw_draw', array('c_id' =>$id,'status'=>1));
                if($res)
                    message('已参与红包抽奖无法参与商品抽奖','referer','error');
                $settings = unserialize($rs['settings']);
                $title = $settings['drawtitle'];
                $tpl .='goods';
                break;
            case 'scene':
                $rs = pdo_get('scene_luckydraw_organizers', array('id' =>$id,'uniacid'=>$this->uniacid,'isend'=>0));
                if(!$rs){
                    message('暂未找到数据','referer','error');
                }
                if($rs['sour']!=2){
                    message('红包抽奖仅支持微信扫码','referer','error');
                }
                $res = pdo_get('scene_luckydraw_draw', array('c_id' =>$id,'status'=>0));
                if($res)
                    message('已参与商品抽奖无法参与红包抽奖','referer','error');
                $resu = pdo_get('scene_luckydraw_itemsnece', array('cid' => $id));
                if(!$resu){
                    message('需要添加奖项才能参加抽奖',$this->createWebUrl('draw',array('op'=>'add','id'=>$id)),'error');
                }
                $settings = unserialize($rs['settings']);
                $title = $settings['drawtitle'];
                $tpl .='moeny';
                break;
            case 'add':
                $orgname = pdo_get('scene_luckydraw_organizers',array('id'=>$id,'uniacid' => $_W['uniacid'],'isend'=>0),array('name'));
                $prize = $this->getsetting(1);
                $prozearr = [];
                foreach ($prize['data'] as $kyes=>$vo){
                    $prozearr[]=['item'=>$kyes,'name'=>$vo['name'],'nums'=>$vo['nums']];
                }
                $resul = pdo_getall('scene_luckydraw_itemsnece',array('cid' =>$id));
                if($resul){
                    foreach ($resul as $k=>$val){
                        foreach ($prozearr as $key=>$value){
                            if($val['item']==$value['item']){
                                $resul[$k]['name']=$value['name'];
                            }
                        }
                    }
                }
//                var_dump($resul);
                $sub = $resul?'修改':'新增';
                $tpl .='item';
                break;
            case 'edits':
                if($_W['ispost']){
                    $itemdata = $_GPC['ids'];
                    if(empty($itemdata[0]['cid'])){

                        foreach ($itemdata as $k=>$v){
                            $res = pdo_get('scene_luckydraw_itemsnece',array('id'=>$v['id']),array('id','money'));
                            if($res['money']==$v['money']){
                                unset($itemdata[$k]);
                            }
                        }
                        if(empty($itemdata)){
                            echo json_encode(3);die();
                        }
                        $money = pdo_getall('scene_luckydraw_wxmoney',array('cid'=>$this->id));
                        if($money){
                            echo json_encode(2);die();
                        }
                        foreach ($itemdata as $val){
                            $result = pdo_update('scene_luckydraw_itemsnece',array('money'=>$val['money']), array('id' =>$val['id']));
                        }
                    }else{
                        $valuedata=$this->arrstr($itemdata);
                        if(!$valuedata){
                            message('转换失败','referer','error');
                        }
                        $sql = "INSERT INTO ".tablename('scene_luckydraw_itemsnece')." (`item`,`cid`,`nums`,`money`) VALUES {$valuedata}";
                        $result = pdo_query($sql);
                    }
                }
                if($result){
                    echo json_encode(1111);exit;
                }else{
                    echo json_encode(2222);exit;
                }
                break;
            default:
                $sql = "SELECT * FROM ".tablename('scene_luckydraw_organizers')." WHERE `isend`=0 AND `uniacid` = {$_W['uniacid']} ORDER BY `timestamp`";
                $rs = pdo_fetchall($sql);
                foreach ($rs as $key=>$val){
                    $res = pdo_get('scene_luckydraw_itemsnece',array('cid'=>$val['id']));
                    if($res){
                        $rs[$key]['status']=1;
                    }
                }
                $count = count($rs);
                $tpl .= 'adraw';
                break;
        }
        include $this->template($tpl);
    }
    private function getuserlist() {
        $sql = "select * from ".tablename('scene_luckydraw_organizers')." where `id` = {$this->id} and `uniacid`={$this->uniacid} and `isend` = 0 limit 1";
        $rs = pdo_fetchall($sql);
        $sour = -1;
        if (count($rs)>0) {
            $settings = unserialize($rs[0]['settings']);
            $sour = intval($settings['sour']);
        }else{
            exit(json_encode(array()));
        }
        $uid = $this->peoplenums($settings['items'],$this->id);

        $ret = array();
        if (($sour == 3)||($sour == -1)) {
            $dir = ZW_ROOT.'/template/web/draw/images';
            $dir = iconv('UTF-8','GB2312',$dir);
            $list = glob($dir.'/*.jpg');
            $add = array();
            foreach ($list as $file) {
                $name = str_replace($dir.'/','',$file);
                $add['file'] = "/addons/zw_field/template/web/draw/images/{$name}";
                $add['name'] = '名字'.str_ireplace('.jpg','',$name);
                $ret[] = $add;
            }
        }else{
            //从数据库取数据
            $sql = "select * from ".tablename('scene_luckydraw_sign')." where `c_id`={$this->id}";
            $rs = pdo_fetchall($sql);
            foreach ($rs as $v) {
                $tmp = [];
                $tmp['id'] = $v['id'];
                $tmp['file'] = $v['image'];
                $tmp['name'] = ($v['name']);
                unset($tmp['image']);
                $ret[] = $tmp;
            }
            //排除已抽奖用户
            if($uid){
                foreach ($ret as $keys=>$vals){
                    if(in_array($vals['id'],$uid)){
                        unset($ret[$keys]);
                    }
                }
            }
        }
        echo json_encode($ret);
        exit;
    }
    private function getsetting($status=0) {
        $sql = "select * from ".tablename('scene_luckydraw_organizers')." where `id`=:id and `uniacid`=:uniacid and isend = 0 limit 1";
        $rs = pdo_fetchall($sql,array(':id'=>$this->id,':uniacid'=>$this->uniacid));
        $out = array();
        if (count($rs)>0) {
            $settings = unserialize($rs[0]['settings']);
            $item = explode("\n",str_replace("\r\n","\n",$settings['items']));
            $spec = explode("\n",str_replace("\r\n","\n",$settings['cheats']));
            $items = array();
            $specs = array();
            $ta = array();
            foreach ($item as $v) {
                $tmp = explode(',',$v);
                if (count($tmp)>=3) {
                    $items[$tmp[0]] = $tmp[1].','.intval($tmp[2]);
                    $ta['name'] = $tmp[1];
                    $ta['nums'] = intval($tmp[2]);
                    $out['data'][$tmp[0]] = $ta;
                }
            }
            foreach ($spec as $v) {
                $tmp = explode(',',$v);
                if (count($tmp)>=2) {
                    $k = $tmp[0];
                    if (isset($items[$k])) {
                        unset($tmp[0]);
                        foreach ($tmp as $n) {
                            $specs[$k][] = $n;
                        }
                    }
                }
            }
            ksort($out['data']);
            ksort($specs);
            if ($specs) {
                $out['spec'] = $specs;
            }else{
                $out['spec'] = array();
            }
        }
        if($status){
            return $out;
        }else{
           $scene = $this-> scenenums($this->id);
            if($scene){
                foreach ($out['data'] as $k=>$values){
                    if(in_array($k,$scene)){
                        unset($out['data'][$k]);
                    }
                }
            }
            echo json_encode($out);
        }
        exit;
    }
    private function buildlogdata($rs) {

        $ret = array();

        $title = array(

            'id' => '内部编号',
            'text' => '中奖名称',
            'time' => '中奖时间',
            'item_id' => '奖项',
            'name'=>'姓名',
            'phone'=>'手机号'

        );

        $ret[] = implode(',',array_values($title));

        foreach ($rs as $ke=>$v) {
            $tmp = array();
            foreach ($title as $m=>$n) {
                $str = '-';
                        if ($v[$m]) {
                            $str = "'".$v[$m];
                        }
                $tmp[] = $str;
            }
            $ret[] = implode(',',$tmp);
        }
        $out = implode("\r\n",$ret);
        $out = iconv('UTF-8','GBK//IGNORE',$out);
        return $out;
    }
    private function activitys() {
        global $_W, $_GPC;
        $uid = implode(',',$_GPC['data']);
        $res = pdo_fetchall("SELECT id,image,name,phone FROM ".tablename('scene_luckydraw_sign')." WHERE `id` in ({$uid})");
        echo json_encode($res);
        exit;
    }
    private function actmoney(){
        global $_W, $_GPC;
        $ativity_data = $_GPC;
        $prize = $this->getsetting(1);
        $prizearr = array();
        $cid = intval($ativity_data['cid']);
        $item_id = $ativity_data['item'];
        foreach ($prize['data'] as $k=>$v){
            $prizearr[$k]=$v['name'];
        }
        $res = pdo_get('scene_luckydraw_draw',array('c_id'=>$cid,'item_id'=>$ativity_data['item']));
        if($res){
            echo 2;exit;
        }
        $j=0;
        $total=count($ativity_data['nid']);
            foreach ($ativity_data['nid'] as $val){
//                获取户头像
                $user_tops = pdo_get('scene_luckydraw_sign',array('id'=>$val),array('image'));
                if($_W['ispost']){
                    $datas = ['c_id' => $cid,'url'=>$user_tops['image'], 'text' => $prizearr[$item_id], 'item_id' => $item_id, 'time' => time(), 'code' => md5(mt_rand(1,9999)),'uid'=>$val,'status'=>1];
                }else{
                    $datas = ['c_id' => $cid,'url'=>$user_tops['image'], 'text' => $prizearr[$item_id], 'item_id' => $item_id, 'time' => time(), 'code' => md5(mt_rand(1,9999)),'uid'=>$val];
                }
                $result = pdo_insert('scene_luckydraw_draw',$datas);
                if(!empty($result))
                    $j+=1;
            }
        $bool = $total==$j?1:0;
        echo json_encode($bool);
        exit;
    }
    //$data活动奖项，$cid活动ID，返回未完全抽奖的人数或flase
    private function peoplenums($data,$cid){
        try{
            $item = explode("\n",str_replace("\r\n","\n",$data));
            $i = 0;
            foreach ($item as $val) {
                $tmp = explode(',',$val);
                if (count($tmp)>=3) {
                    $i+=intval($tmp[2]);
                }
            }
            $drawdata = pdo_getall('scene_luckydraw_draw', array('c_id'=>$cid));
            if(count($drawdata)>0){
                if(count($drawdata)!=$i){
                    $scene_uid = array();
                    foreach ($drawdata as $va){
                        $scene_uid[] = $va['uid'];
                    }
                    return $scene_uid;
                }
            }
            return false;
        }catch (Exception $e){
            return false;
        }
    }
    //排除已抽奖项
    private function scenenums($cid){
        $sql = "SELECT * FROM ".tablename('scene_luckydraw_draw')." WHERE c_id={$cid} GROUP BY item_id";
        $scenedata = pdo_fetchall($sql);
        if(count($scenedata)>0){
            $item=[];
            foreach ($scenedata as $value){
                $item[] = intval($value['item_id']);
            }
            return $item;
        }
        return false;
    }
    private function getwinner(){
        $rs = pdo_get('scene_luckydraw_organizers', array('id' =>$this->id,'uniacid'=>$this->uniacid));
        if (count($rs)>0) {
            $settings = unserialize($rs['settings']);
            $item = explode("\n",str_replace("\r\n","\n",$settings['items']));
            $i = 0;
            $scene = [];
            foreach ($item as $val) {
                $tmp = explode(',',$val);
                $scene[]=$tmp[0];
                if (count($tmp)>=3) {
                    $i+=intval($tmp[2]);
                }
            }
            $drawdata = pdo_getall('scene_luckydraw_draw', array('c_id'=>$this->id));
            if (count($drawdata)==$i){
                $data = [];
                $str='';
                foreach ($scene as $v){
                    foreach ($drawdata as $va){
                        if($v==$va['item_id']){
                            $user = pdo_get('scene_luckydraw_sign',array('id'=>$va['uid']),array('name','image','phone'));
                            $data[$va['text']][] = $user;
                        }
                    }
                }
//                $str='';
//                foreach ($data as $ke =>$vi){
//                    $str.=$ke.':'.implode(',',$data[$ke]).'       ';
//                }
                echo json_encode($data);exit;
            }
        }
        echo 0;exit();
    }
    private function wxsnece(){
        global $_W, $_GPC;
        $scene_datas = $_GPC;
        $c_id = intval($scene_datas['cid']);
        $item_id = intval($scene_datas['item']);
        $prize = $this->getsetting(1);
        foreach ($prize['data'] as $k=>$v){
            if($k==$item_id){
                $prizenum = $v['nums'];
            }
        }
       $rs = pdo_getall('scene_luckydraw_draw',array('c_id'=>$c_id,'item_id'=>$item_id));
        if(!$rs && count($rs)!=$prizenum){
            echo 2;exit;
        }
        $res = pdo_getall('scene_luckydraw_wxmoney',array('cid'=>$c_id,'item'=>$item_id));
        if($res){
            echo 4;exit;
        }
        $money = pdo_get('scene_luckydraw_itemsnece',array('cid'=>$c_id,'item'=>$item_id),array('money'));
        if(!$money){
            echo 3;exit;
        }
        $wx_data = [];
        if(!$res){
            foreach ($rs as $value){
                $ordersn= '\''.'SH'.date('ymdHis',time()).sprintf("%05d",mt_rand(1,99999)).'\'';
                $wx_data[]=['cid'=>$value['c_id'],'item'=>$value['item_id'],'did'=>$value['id'],'uid'=>$value['uid'],'money'=>$money['money'],'addtiem'=>time(),'ordersn'=>$ordersn];
            }
            $valuedata=$this->arrstr($wx_data);
            if(!$valuedata){
//                message('转换失败','referer','error');
                echo json_encode(6666);exit;
            }

            $sql = "INSERT INTO ".tablename('scene_luckydraw_wxmoney')." (`cid`,`item`,`did`,`uid`,`money`,`addtime`,`ordersn`) VALUES {$valuedata}";
            $result = pdo_query($sql);

        }
        if($result){
            $pay = pdo_get('scene_luckydraw_pay', array('status' =>0,'use'=>1));
            if($pay['paytype']==2){
                $sql = "SELECT w.id,w.money,w.ordersn,s.datas FROM `ims_scene_luckydraw_wxmoney` w JOIN `ims_scene_luckydraw_sign` s ON w.uid=s.id AND w.status=0 AND w.cid={$c_id} AND w.item={$item_id}";
                $wxmoney = pdo_fetchall($sql);
                foreach ($wxmoney as $v){
                    $des = iunserializer($v['datas']);
                    $prr=['id'=>$v['id'],'money'=>$v['money'],'alinumber'=>$des['alinumber'],'alirealname'=>$des['alirealname'],'ordersn'=>$v['ordersn']];
                    $this->alipays($pay['id'],$prr);
                }
                echo json_encode(1);exit;
            }else{
                echo json_encode(10001);exit;
            }

        }
        echo json_encode(88888);exit;

    }
    private function aas(){
        $prize = $this->getsetting(1);
    }
    /**
     * 二维数组变字符串
    */
    private function arrstr($arr){
        $brr = [];
        foreach ($arr as $val){
            if(is_array($val)){
                $brr[] = '('.implode(',',array_values($val)).')';
            }
        }
        if(count($brr)!=count($arr)){
            return false;
        }
        $result = implode(',',$brr);
        return $result;
    }
    public function doWebPay()
    {
        global $_W, $_GPC;
        $title = '支付类型设置';
        $op = !empty($_GPC['op']) ? strtolower($_GPC['op']) : 'false';
        $id = !empty($_GPC['id'])?intval($_GPC['id']):0;
        switch ($op){
            case 'wechatadd':
                if($id>0){
                    $rs = pdo_get('scene_luckydraw_pay', array('id'=>$id));
                    if($rs){
                        $paydata = unserialize($rs['set_up']);
                    }else{
                        message('未找到修改数据，请认真核对','referer','error');
                    }
                }
                $submittext = $_GPC['id'] ==0?'新增':'修改';
                $tpl = 'web/pay/wechat';
                break;
            case 'alipayadd':
                if($id>0){
                    $rs = pdo_get('scene_luckydraw_pay', array('id'=>$id));
                    if($rs){
                        $paydata = unserialize($rs['set_up']);
                    }else{
                        message('未找到修改数据，请认真核对','referer','error');
                    }
                }
                $submittext = $_GPC['id'] ==0?'新增':'修改';
                $tpl = 'web/pay/alipay';
                break;
            case 'edit':
                $settings = !empty($_POST['settings'])?$_POST['settings']:array();
                $rs = pdo_get('scene_luckydraw_pay', array('id' => $id,'uniacid'=>$this->uniacid,'status'=>0));
                if($rs)
                    $sets = unserialize($rs['set_up']);
                if ($settings) {
                    $data=['uniacid'=> $_W['uniacid'],'createtime'=>time(),'paytype'=>$_GPC['paytype']];
                    $u = array();
                    if( $settings['wechat_type']==1) {
                        if ($_FILES['cert_file']['name']) {
                            $u['cert_file'] = $this->upload_cert('cert_file');
                        }
                        if ($_FILES['key_file']['name']) {
                            $u['key_file'] = $this->upload_cert('key_file');
                        }
                        if ($_FILES['root_file']['name']) {
                            $u['root_file'] = $this->upload_cert('root_file');
                        }
                        if($id>0){
                            if(empty($u['cert_file']) && empty($u['root_file']) && empty($u['key_file'])){
                                if($sets['cert_file'])
                                    $u['cert_file']=$sets['cert_file'];
                                if($sets['cert_file'])
                                    $u['cert_file']=$sets['cert_file'];
                                if($sets['key_file'])
                                    $u['key_file']=$sets['key_file'];
                            }
                        }
                        $u['wechat_type'] = $settings['wechat_type'];
                        $u['uniacid'] = $_W['uniacid'];
                        $u['appid'] = $settings['appid'];
                        $u['name'] = $settings['name'];
                        $u['appsecret'] = $settings['appsecret'];
                        $u['mch_id'] = $settings['mch_id'];
                        $u['apikey'] = $settings['apikey'];
                        $data['set_up'] = serialize($u);
                    }else{
                        $u['uniacid'] = $_W['uniacid'];
                        $u['name'] = $settings['name'];
                        $u['appid'] = $settings['appid'];
                        $u['pid'] = $settings['pid'];
                        $u['md5key'] = $settings['md5key'];
                        $u['publickey'] = $settings['publickey'];
                        $u['privatekey'] = $settings['privatekey'];
                        $u['pay_account'] = $settings['pay_account'];
                        $u['admin'] = $settings['admin'];
                        $u['adminnumber'] = $settings['adminnumber'];
                        $u['remark'] = $settings['remark'];
                        $u['cost'] = $settings['cost'];
                        if($_GPC['id']>0 && $_GPC['paytype']==2){
                            if (empty($u['privatekey'])){
                                $u['privatekey'] = $sets['privatekey'];
                            }
                            if (empty($u['publickey'])){
                                $u['publickey'] = $sets['publickey'];
                            }
                        }
                        $data['set_up'] = serialize($u);
                    }
                    //增加操作
                    if ($_GPC['id'] == 0) {
//                        var_dump($data);exit;
                        $result = pdo_insert('scene_luckydraw_pay',$data);
                        if($result){
                            message('添加成功',$this->createWebUrl('pay'),'success');
                        }else{
                            message('添加失败','referer','error');
                        }
                    }else{
                        //修改操作

                        if (count($rs)<=0) {
                            message('修改的数据未找到，请确认是否已删除！','referer','error');
                        }
                        $datas=['set_up'=>$data['set_up']];
//                        var_dump($datas);exit;
                        $result = pdo_update('scene_luckydraw_pay',$datas,array('id'=>$id));
                        if($result){
                            message('',$this->createWebUrl('pay'),'success');
                        }else{
                            message('修改失败','referer','error');
                        }
                    }
                }else{
                    message('提交的数据为空，请重新检查','referer','error');
                }
                break;
            case 'del':
                    $rs = pdo_get('scene_luckydraw_pay', array('id' =>$id));
                    if (count($rs)>0) {
                        pdo_update('scene_luckydraw_pay',array('status'=>1),array('id'=>$id));
                    }else{
                        message('未找到匹配数据！','referer','error');
                    }
                    message('所有相关数据已成功删除！',$this->createWebUrl('pay'),'success');
                break;
            case 'use':
                $rs = pdo_getall('scene_luckydraw_pay', array('use' =>1));
                if (count($rs)>0) {
                    pdo_update('scene_luckydraw_pay',array('use'=>0),array('use'=>1));
                }
                    pdo_update('scene_luckydraw_pay',array('use'=>1),array('id'=>$id));
                header('location: '.$_SERVER['HTTP_REFERER']);
                break;
            default:
//                $where = "`status`=0 AND `uniacid` = {$_W['uniacid']} "; // 按公众号查询
                $where = "`status`=0";          //查询所有
                $sql = "SELECT * FROM ".tablename('scene_luckydraw_pay')." WHERE {$where} ORDER BY `id`";
                $rs = pdo_fetchall($sql);
                foreach ($rs as $k=>$v){
                    $set_up=unserialize($v['set_up']);
                    $rs[$k]['name']=$set_up['name'];
                }
                $count = count($rs);
                $tpl = 'web/pay/paytype';
                break;
        }
        include $this->template($tpl);
    }
    //文件上传
    protected function upload_cert($fileinput)
    {
        global $_W;
        $filename = $_FILES[$fileinput]['name'];
        $tmp_name = $_FILES[$fileinput]['tmp_name'];
        if (!empty($filename) && !empty($tmp_name)) {
            $ext = strtolower(substr($filename, strrpos($filename, '.')));

            if ($ext != '.pem') {
                $errinput = '';

                if ($fileinput == 'cert_file') {
                    $errinput = 'CERT文件格式错误';
                }
                else if ($fileinput == 'key_file') {
                    $errinput = 'KEY文件格式错误';
                }
                else {
                    if ($fileinput == 'root_file') {
                        $errinput = 'ROOT文件格式错误';
                    }
                }

                show_json(0, $errinput . ',请重新上传!');
            }

            return file_get_contents($tmp_name);
        }

        return '';
    }
    public function doWebalipay(){

    }
    public function alipays($id,$sign){
        $rs = pdo_get('scene_luckydraw_pay',array('id'=>$id));
        $data = iunserializer($rs['set_up']);
//        echo '<pre>';
//        var_dump($data);
        $c = new AopClient();
        $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $c->appId = $data['appid'];
        $c->rsaPrivateKey = $data['privatekey'] ;
        $c->format = "json";
        $c->charset= "UTF-8";
        $c->signType= "RSA2";
        $c->alipayrsaPublicKey = $data['publickey'];
        $order_number = $sign['ordersn'];
        $pay_no = $sign['alinumber'];
        $amount = number_format($sign['money'],1);
        $complcename = $data['pay_account'];
        $unsername = $sign['alirealname'];
        $memo = '抽奖红包';
        $request = new AlipayFundTransToaccountTransferRequest();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"".$order_number."\"," .//商户生成订单号
            "\"payee_type\":\"ALIPAY_LOGONID\"," .//收款方支付宝账号类型
            "\"payee_account\":\"".$pay_no."\"," .//收款方账号
            "\"amount\":\"".$amount."\"," .//总金额
            "\"payer_show_name\":\"".$complcename."\"," .//付款方账户
            "\"payee_real_name\":\"".$unsername."\"," .//收款方姓名
            "\"remark\":\"".$memo."\"" .//转账备注
            "}");
        $response= $c->execute($request);
//        var_dump($response);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $response->$responseNode->code;
        $order_id = $response->$responseNode->order_id;

        $user_data = ['status'=>1,'paytime'=>time(),'order_id'=>$order_id,'paytype'=>2];
        if(!empty($resultCode)&&$resultCode == 10000){
            //提现成功以后 更新表状态
            //并且记录 流水等等
            pdo_update('scene_luckydraw_wxmoney', $user_data, array('id' => $sign['id']));
            load()->func('logging');
            logging_run($unsername.'支付宝转账成功');
        } else {
            //$response->$responseNode->sub_msg 这个参数 是返回的错误信息
//            throw new Exception($response->$responseNode->sub_msg);
            load()->func('logging');
            logging_run(array('wxmoneyid' =>$sign['id'], 'error' =>$response->$responseNode->sub_msg));
        }
    }
    public function doWebwechat(){
        $rs = pdo_get('scene_luckydraw_pay',array('id'=>3));
        $data = iunserializer($rs['set_up']);
//        var_dump($data);
        $money = 1; //最低1元，单位分
        $sender = "智爱网络";
        $obj2 = array();
        $obj2['wxappid'] = $data['appid']; //appid
        $obj2['mch_id'] = $data['mch_id'];//商户id
        $obj2['mch_billno'] = $data['mch_id'].date('YmdHis').rand(1000,9999);//组合成28位，根据官方开发文档，可以自行设置
        $obj2['client_ip'] = $_SERVER['REMOTE_ADDR'];
        $obj2['re_openid'] = "oDZSb03htDis_MWF3CSkW6cX8hBA";//接收红包openid
        $obj2['total_amount'] = $money;
        $obj2['min_value'] = $money;
        $obj2['max_value'] = $money;
        $obj2['total_num'] = 1;
        $obj2['nick_name'] = $sender;
        $obj2['send_name'] = $sender;
        $obj2['wishing'] = "恭喜发财";
        $obj2['act_name'] = $sender."红包";
        $obj2['remark'] = $sender."红包";

        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
        $wxpay = new wxPay();
        $res = $wxpay->pay($url, $obj2);
        var_dump($res);
    }
}