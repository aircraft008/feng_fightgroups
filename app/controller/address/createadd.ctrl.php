<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * addmanage.ctrl
 * 我的地址控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('address');

// 判断用户是否已注册
if (pdd_isLoginedStatus() == false) {
    header("Location: ".app_url('member/login'));exit;  
}

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

session_start();
$goodsid = $_SESSION['goodsid'];
$pagetitle = !empty($config['tginfo']['sname']) ? 'modificar endereço - '.$config['tginfo']['sname'] : 'modificar endereço';

if($goodsid){
	$bakurl = app_url('order/orderconfirm');
}else{
	$bakurl = app_url('address/addmanage');
}

$id=$_GPC['id'];
$weid = $_W['uniacid'];
$openid = $_W['openid'];

if ($op == 'display') {
    if($id){
        $addres = address_get_by_id($id);
    }  	
	include wl_template('address/createadd');
}

if ($op == 'addwechat'){
	$shareAddress = shareAddress();
	include wl_template('address/createadd');
}

if ($op == 'post') {
	$citys = explode(", ", $_GPC['citys']); 
    if(!empty($id)){
        $status = address_get_by_id($id);
        $data=array(
            'openid'           => $openid,
            'uniacid'          => $weid,
            'cname'            => $_GPC['myname'],
            'tel'              => $_GPC['myphone'],
            'province'         => $citys[0],
            'city'             => $citys[1],
            'county'           => !empty($_GPC['county']) ? $_GPC['county'] : $citys[2],
            'zipcode'          => $_GPC['zipcode'],
            'detailed_address' => $_GPC['detailed'],
            'type'             => $_GPC['type'],
            'addtime'          => time()
        );
        if(pdo_update('tg_address',$data,array('id' => $id))){ 
        	wl_json(1);
        }else{   
            wl_json(0,'erro,endereço invalido!');
        }
    }else{
        $data1=array(
            'openid' => $openid,
            'uniacid'=> $weid,
            'cname'            => $_GPC['myname'],
            'tel'              => $_GPC['myphone'],
            'province'         => $citys[0],
            'city'             => $citys[1],
            'county'           => !empty($_GPC['county']) ? $_GPC['county'] : $citys[2],
            'zipcode'          => $_GPC['zipcode'],
            'detailed_address' => $_GPC['detailed'],
            'type'             => $_GPC['type'],
            'addtime'          => time(),
            'status'           => '1'
    	);
    	$moren = address_get_by_params("status = 1 and openid = '$openid'");
    	pdo_update('tg_address',array('status' => 0),array('id' => $moren['id']));
        if(pdo_insert('tg_address',$data1)){
        	wl_json(1);
        }else{
            wl_json(0,'modifacaçao nao realizado!');
        }                 
    }   
}

if ($op == 'deletes'){
	if($id){
        if(pdo_delete('tg_address',array('id' => $id ))){
            wl_json(1);
        }else{
            wl_json(0,'modifacaçao nao realizado!');
        }        
    }else{
        wl_json(0,'falta alguma dados');
    }
}
