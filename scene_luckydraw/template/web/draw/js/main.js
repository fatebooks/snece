var userlists = [];
var itemlists = [];
var spec = [];
var usercount = 0;
var itemcount = 0;
var idlist = [];
var current,remind;
var win = [];
var c_cheat = [];
var a_cheat = [];

var g_Interval = 4;
var g_Timer;
var running = false;
var isrerand = false;
var re_obj;
var re_text = '';
var tmp_obj;
//新建存储中奖信息
var win_arr = [];
var iii = '';
var vdata =[];
var totalnum=0;
var tot=0;


$(function(){
    $.base64.utf8encode = true;
    $.ajax({
        url: get_users_url,
        async: false,
        type: 'POST',
        success: function(data) {
            // console.log(data);
            usercount = 0;
            var preload = [];
            $.each(data,function(idx,obj){
                var tmp = [];
                tmp['id']=obj.id;
                tmp['file'] = obj.file;
                tmp['name'] = obj.name;
                tmp[disp_type] = obj[disp_type];
                preload.push(obj.file);
                usercount ++;
                userlists.push(tmp);
            });
            updatecount();
            preloadimages(preload).done(function(){
            });
        },
        dataType:"json"
    });
    $.ajax({
        url: get_sets_url,
        async: false,
        type: 'GET',
        success: function(data) {
            // console.log(data);
            itemcount = 0;
            $('#main').html('');
            $.each(data.data,function(idx,obj){
                var tmp = [];
                tmp['name'] = obj.name;
                tmp['nums'] = obj.nums;
                itemcount ++;
                itemlists[idx] = tmp;
                idlist.push(idx);
                vdata.push(parseInt(idx));
                var html = '<div style="position: relative" class="user_lists" id="item'+idx+'"><div class="stitle" style="position: absolute;left: 0;top: -30px;"><span>'+obj.name+'('+obj.nums+'名)</span><button scename="'+obj.name+'" name="'+idx+'" num="'+obj.nums+'" onclick="alerthtml(this)" style="height: 50px;font-size: 25px;background: cornflowerblue;border-radius: 5px">当前奖项名单</button></div><div class="user"><img src="'+defaultimg+'"><span></span></div></div>';
                $('#main').prepend(html);
            });
            prize_code = vdata.reverse(); //获取奖项编号
            spec = data.spec;
            allcheatuser();
            getcurrent();
        },
        dataType:"json"
    });
    $('#btn').bind('click',function(){
        start();
    });
    $('#next').bind('click',function(){
        if (isrerand) {
            layer.alert('请先完成重新抽奖后再进入下一项');
            return;
        }
        if (remind<=0) {
            // console.log(saves_data())
            priceadd(saves_data());
            getcurrent();
            $('#btn').attr('disabled',false);
            $('#next').attr('disabled',true);
        }else{
            layer.alert('请等当前奖项抽完后才能进入下一项！');
            $('#btn').attr('disabled',false);
            $('#next').attr('disabled',true);
        }
    });
});

/**
 * 取当前抽奖项
 * @returns {boolean}
 */
function getcurrent() {
    if (idlist.length>0) {
        checkclear();
        bindclick(current,false);       //清除原来绑定的重新抽奖功能
        current = idlist.pop();
        remind = itemlists[current]['nums'];
        if (spec[current] != undefined) {
            c_cheat = spec[current];
            checkcheatuser();
        } else {
            c_cheat = [];
        }
        $('#item' + current).show();
        $('#btn').attr('disabled',false);
    }else{
        sneceinfo();
        layer.alert('已无更多奖项设置，抽奖结束');
        disabledallbtn();
    }
    return true;
}
function disabledallbtn() {
    $('#next').attr('disabled',true);
    $('#btn').attr('disabled',true);
}
/**
 * 检查是否需要隐藏原有奖项（当屏幕空间不足时自动隐藏全部或者第一项）
 */
function checkclear() {
    if (current == undefined) return;
    var b_top = $('#btn').offset().top;
    var c_top = $('#item'+current).offset().top;
    if (c_top + 130*2 >= b_top) {
        $('.user_lists:visible:first').slideUp(2000);
    }
}
/**
 * 点击开始或停止按钮
 */
function start() {
    //console.log(userlists.length);
    if (userlists.length<=0) {
        running = false;
        clearTimeout(g_Timer);
        disabledallbtn();
        layer.alert('已无用户可参与抽奖，抽奖结束！');
        return;
    }
    if (running) {
        var info = '';
        var tmp = [];
        if (!isrerand) {
            remind--;
            var s_obj = $('#item' + current + '.user:last');
            if (remind > 0) {
                deluserlist();
                adduser();
            } else {
                var qwe;
                var win_obj = '';
                $('#btn').val('开始');
                $('#btn').attr('disabled', true);
                var img = '.userimg' + current;
                var txt = '.usertxt' + current;
                $(img).each(function(){
                    qwe = $(this).attr('src');
                    win_arr.push({user_img:qwe,user_txt:""});
                });
                $(txt).each(function(i){
                    qwe = $(this).text();
                    index_img = win_arr[i].user_img;
                    win_arr[i].user_txt = qwe;
                    win_obj = win_obj + '<div style="width: 40%;float:left;margin-left:15px;" class="n'+i+'"><div style="width: 50%;display: inline-block"><img style="float: right" width="50px" height="50px" src=' + index_img + ' ></div><div style="width: 48%;display: inline-block;text-align: center"><span>'+ qwe +' </span></div></div>'
                })
                // console.log(win_obj);
                iii = win_obj;
                deluserlist();
                win_obj = '';
                win_arr.length = 0;
                $('#next').attr('disabled', false);
                clearTimeout(g_Timer);
                running = false;

                bindclick(current);     //绑定重抽某获奖用户
            }
        }else{
            if (re_obj == undefined) {
                clearTimeout(g_Timer);
                var s_obj = tmp_obj;
            }else{
                var s_obj = re_obj;
            }
        }

        tmp.file = s_obj.find('img').attr('src');
        tmp.text = s_obj.find('span').html();
        tmp.time = Math.floor(Date.parse(new Date())/1000).toString();
        tmp.id = current;
        info = tmp.file +'|'+tmp.text+'|'+tmp.time+'|'+tmp.id;
        info += '|'+ $.md5(tmp.file+'-+'+tmp.text+'=-'+tmp.time+','+tmp.id);

        if (isrerand) {
            $('#btn_div').hide();
            running = false;
            isrerand = false;
        }

    }else{
        if (!isrerand) {
            if (remind > 0) {
                running = true;
                $('#btn').val('停止');
                beginTimer();

            } else {
                console.log(info);
                layer.alert('已无更多奖项名额');
            }
        }else{
            running = true;
            beginTimer();
        }
    }
    updatecount();
}
/**
 * 开启计时器
 */
function beginTimer() {
    g_Timer = setTimeout(randnum,g_Interval);

}
/**
 * 开始滚动
 */
function randnum() {
    var len = userlists.length;
    if (len>0) {
        var num = Math.floor(Math.random() * len + 1);
        var r_url = userlists[num-1]['file'];
        if (r_url.length<= 0) {
            // r_url = empty_url;
            r_url = topimg;
        }
        var html = '<img class="userimg'+ current +'" src="'+r_url+'"><span class="usertxt' + current +'" name="'+userlists[num-1]['id']+'">'+userlists[num-1]['name']+'</span>';
        if (isrerand == false) {
            $('#item' + current + ' .user:first').html(html);
        }else{
            re_obj.html(html);
        }
            g_Timer = setTimeout(randnum,g_Interval);
    }else{
        start();
    }
}
/**
 * 添加新的抽奖用户
 */
function adduser() {
    if (userlists.length>1) {
        var html = '<div class="user"><img src="'+defaultimg+'"><span></span></div>';
        $('#item' + current).prepend(html);
    }
}

function getspecdata(a_nums) {
    //cheat要用全局
    var g_all = userlists.length;
    var g_ischeat = false;

    var g_nums = c_cheat.length;
    if (g_nums > 0) {
        //var g_r = Math.floor(Math.random()*(a_nums-g_nums)+1);
        var g_r = rand(a_nums-g_nums);
        if (g_r<=1) {
            var g_idx,g_val;
            if (g_nums>1) {
                //g_idx = Math.floor(Math.random()*g_nums);
                g_idx = rand(g_nums)-1;
                g_val = c_cheat[g_idx];
                c_cheat.splice(g_idx,1);
            }else{
                g_val = c_cheat.pop();
            }
            return g_val;
        }else{
            return '';
        }
    }else{
        return '';
    }
}


/**
 * 删除已中奖用户
 * @param text  中奖用户
 * @param type  类型
 */

/**
 * 统计所有需要中奖的用户，防止这些用户中其它奖项
 */
function allcheatuser() {
    for (var o in spec) {
        for (var j in spec[o]) {
            a_cheat.push(spec[o][j]);
        }
    }
}
/**
 * 检查值是否在数据中
 * @param value     值
 * @param ary       数组
 * @param name      子数组名称，针对二维数组
 * @returns {boolean}
 */
function in_array(value,ary,name)  {
    var v = '';
    for (var o in ary) {
        if (name == undefined) {
            v = ary[o];
        }else{
            v = ary[o][name];
        }
        if (value == v) {
            return true;
        }
    }
    return false;
}
function indexOf(value,ary) {
    for (var o in ary) {
        if (ary[o] == value) {
            return o;
        }
    }
    return -1;
}
/**
 * 清除无效cheat用户
 */
function checkcheatuser() {
    var g_del = 0;
    for(var i=0;i<c_cheat.length;i++) {
        if (!in_array(c_cheat[i-g_del],userlists,disp_type)) {
            c_cheat.splice(i-g_del,1);
            g_del++;
        }
    }
}

/**
 * 更新统计人数
 */
function updatecount() {
    $('#count').find('a').html(userlists.length);
}


function bindclick(cur,isbind) {
    if (cur == undefined) return;
    if ((isbind == undefined)||(isbind == true)) {
        $('#item' + cur + ' .user').bind('click', function () {
            // re_rand($(this));
        });
    }else{
        $('#item' + cur + ' .user').unbind('click');
    }
}
//获取当前抽奖信息
function saves_data(){
    var len = $('#item'+current).find('span.usertxt'+current);
    var totalarr = new Object();
    totalarr['cid'] = c_id;
    totalarr['item']=current;
    var arr = [];
    for (var i=0;i<len.length;i++) {
        arr.push(len[i].getAttribute("name"));
    }
    totalarr['nid'] = arr;
    return totalarr;
}

function priceadd(data) {
    $.ajax({
        type: "GET",
        url: set_money_url,
        data: data,
        success: function(msg){
            // console.log(msg);
            // alert(msg);
            if(msg==1){
                layer.alert('该奖项录入成功');
            }else if(msg==2){
                layer.alert('该奖项无法重复提交');
            }else {
                layer.alert('该奖项录入失败');
            }
        }
    });
}
//防止重复抽奖
function deluserlist() {
    var userid = $('#item'+current).find('span.usertxt'+current);
    if(!userid){
        return ;
    }
    var nid = userid[0].getAttribute("name");
    // console.log(nid);
    for (var j=0;j<userlists.length;j++){
        if(userlists[j]['id']==nid){
            userlists.splice(j,1);
        }
    }
    totalnum+=1;
}
//点击

$('.stitle').delegate('span','click',function(){
    var ch = $(this).parent().parent().text();
    console.log(ch);
    //alert($('.usertxt4').text());
})
//全部抽奖信息
function sneceinfo() {
    $.base64.utf8encode = true;
    $.ajax({
        type: "POST",
        url:  get_winter_url,
        success: function(msg){
            if (msg) {
                aleryer(msg);
            }else{
                layer.alert('获取全部抽奖名单失败');
            }
        },
        dataType:"json"
    });
}
function alerthtml(obj) {
    var curs = $(obj).attr('name');
    var peonum = $(obj).attr('num');
    var scename = $(obj).attr('scename');
    var totalarr=new Object();
    var len = $('#item'+curs).find('.user span');
    var disnext = $('#next').attr('disabled');
    if(len.length!=peonum){
        layer.alert('奖项抽奖未完成');
        return false;
    }
    clearTimeout(g_Timer);
    var arr = [];
    for (var i=0;i<len.length;i++) {
        arr.push(parseInt(len[i].getAttribute("name")));
    }
    totalarr['data']=arr;

    $.base64.utf8encode = true;
    $.ajax({
        type: "POST",
        url:  set_activity_url,
        data:totalarr,
        success: function(msg) {
            // console.log(msg);
            if (msg) {
                var user_arr=new Object();
                user_arr[scename] = msg;
                // console.log(user_arr);
                aleryer(user_arr,curs);
            }else{
                layer.alert('获取抽奖名单失败');
            }
        },
        dataType:"json"
    });
}
//弹窗
function aleryer(a,b=0) {
    var str1='';
    for (var x in a){
        if(b){
            var cent = '<div style="position: relative"><h1 style="text-align:center;">'+x+'</h1><button name="'+b+'" style="font-size: 24px;background-color: cornflowerblue ;border-radius: 8px;padding: 5px;position: absolute;right: 30px;top:1px" onclick="againscene(this)">重抽选中名单</button></div><hr><div id="aga" style="width:750px;margin:10px">';
        }else{
            var cent = '<h1 style="text-align:center;">'+x+'</h1><hr><div style="width:750px;margin:10px">';
        }

        var ce='';
        for (var i=0;i<a[x].length;i++){
            var imgss =a[x][i]['image']?a[x][i]['image']:topimg;
            ce=ce+'<div class="tops1" onclick="swchs(this)"><img src="'+imgss+'"><p class="tts1">'+a[x][i]['name']+'</p><p class="tts1">'+a[x][i]['phone']+'</p><input type="hidden" name="'+a[x][i]['id']+'"></div>';
        }
        str1 =str1+cent+ce+'<div style="clear:both"></div></div>'
    }
    // console.log(str1);
    index=layer.open({
        type: 1 //Page层类型
        ,area: ['800px', '400px']
        ,title: '中奖名单'
        ,shade: 0.6 //遮罩透明度
        ,maxmin: true //允许全屏最小化
        ,anim: 1 //0-6的动画形式，-1不开启
        ,content: str1
    });
}
//标记
function swchs(obj) {
    var users1 = $(obj);
    users1.toggleClass("bls");
}
//重新抽奖，排除掉不在场的中奖用户
function againscene(obj) {
    var curs = $(obj).attr('name');
    var agasiuser = $('#aga').find('.bls input');

    var arr=[];
    for (var i=0;i<agasiuser.length;i++) {
        arr.push(parseInt(agasiuser[i].getAttribute('name')));
    }
    if(arr==''){
        layer.close(index);
        layer.alert('未选中用户，无法重新抽奖');
        return ;
    }
    var len = userlists.length;
    var surplus = tot-totalnum;
    var uselit = len-arr.length;
    if (len<arr.length || uselit<surplus) { //会员少于重选人数，或重选后导致奖项比人数多
        layer.close(index);
        layer.alert('用户不足，无法重新抽奖');
        return ;
    }
    if(curs!=current){
        layer.close(index);
        layer.alert('已录入奖项，无法重新抽奖');
        return ;
    }
    $('#item'+curs).children().each(function (i) {
       var nid = $(this).find('span').attr('name');
        // console.log(nid);
       for(k in arr){
           if(arr[k]==nid){
              $(this).remove();
           }
       }
    })
    var brr=[]
    // console.log(arr.length);
    for(var i=0;i<arr.length;i++){
        var len = userlists.length;
        var num = Math.floor(Math.random() * len );
        // console.log(userlists[num]['id'])
        brr.push(userlists[num]['name']);
        if(!userlists[num]['file']){
            userlists[num]['file']=topimg
        }
        var html = '<div class="user"><img class="userimg'+ curs +'" src="'+userlists[num]['file']+'"><span class="usertxt' + curs +'" name="'+userlists[num]['id']+'">'+userlists[num]['name']+'</span></div>';
        $('#item' + curs).prepend(html);
        userlists.splice(num,1);
    }
    var addusers = brr.join('--');
    layer.close(index);
    layer.alert('重新抽奖名单：'+addusers)
    updatecount()
}
/**
 * 返回抽奖总人数
*/
$(function(){
//     console.log(itemlists)
    for(var x in itemlists){
        tot+=parseInt(itemlists[x]['nums']);
    }
    // console.log(tot);
    if(tot>userlists.length){
        layer.alert('人数少于奖项数，抽奖结束');
        disabledallbtn();
    }
});






