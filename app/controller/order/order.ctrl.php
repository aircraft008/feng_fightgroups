<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * order.ctrl
 * 订单控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('order');
wl_load()->model('goods');
wl_load()->model('merchant');

// 判断用户是否已注册
if (pdd_isLoginedStatus() == false) {
	header("Location: ".app_url('member/login'));exit;	
}

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'list';
$pagetitle = !empty($config['tginfo']['sname']) ? 'meu pedido - '.$config['tginfo']['sname'] : 'meu pedido';

if($op =='list'){
	$status = ($_GPC['status']!='')?$_GPC['status']:'';
	include wl_template('order/order_list');
}

if($op =='ajax'){
	$page = $_GPC['page'];
	$pagesize = $_GPC['pagesize'];
	$status = $_GPC['status'];
	$data = order_get_list(array('openid'=>$_W['openid'],'usepage'=>1,'page'=>$page,'pagesize'=>$pagesize,'status'=>$status));
	foreach($data['list'] as $key =>&$vlaue){
		$goods = goods_get_by_params(" id = {$vlaue['g_id']} ");
		switch($vlaue['status']){
			case 0:$statusname='Pendente';break;
			case 1:$statusname='Em baerto';break;
			case 2:$statusname='Pendente';break;
			case 3:$statusname='A caminho';break;
			case 4:$statusname='Entregues';break;
			case 5:$statusname='Cancelados';break;
			case 6:$statusname='devoluçao pendente';break;
			case 7:$statusname='devoluçao realizado';break;
			default:$statusname='Pendente';
		}
		$vlaue['gimg'] = $goods['gimg'];
		$vlaue['name'] = $goods['gname'];
		$vlaue['ga'] = $goods['a'];
		$vlaue['statusname'] = $statusname;
		if($vlaue['status'] != 5 && $vlaue['status'] != 0 && $vlaue['is_tuan'] == 1){
			$vlaue['ta'] = app_url('order/group', array('tuan_id'=>$vlaue['tuan_id']));
		}
		if($vlaue['merchantid']){
			$merchant = merchant_get_by_id($vlaue['merchantid']);
			$vlaue['merchant_name'] = $merchant['name'];
		}else{
			$vlaue['merchant_name'] = $_W['account']['name'];
		}
	}
	die(json_encode($data));
}

if($op =='detail'){
	$id = intval($_GPC['id']);
	if($id){
		$order = order_get_by_id($id);
		$goods = goods_get_by_params(" id = {$order['g_id']} ");
		if($order['merchantid']){
			$merchant = merchant_get_by_id($order['merchantid']);
			$order['merchant_name'] = $merchant['name'];
		}else{
			$order['merchant_name'] = $_W['account']['name'];
		}
		if($goods['is_hexiao']==1){
			//门店信息
			$storesids = explode(",", $goods['hexiao_id']);
			foreach($storesids as$key=>$value){
				if($value){
					$stores[$key] =  pdo_fetch("select * from".tablename('tg_store')."where id ='{$value}' and uniacid='{$_W['uniacid']}'");
				}
			}
		}
	}
	include wl_template('order/order_detail');
}

if($op =='cancel'){
	$orderno = $_GPC['orderno'];
	if($orderno){
		$order = order_update_by_params(array('status' => 5),array('orderno' => $orderno));
		if($order){
			$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE orderno = :orderno", array(':orderno' => $orderno));
			$goods = goods_get_by_params(" id={$item['g_id']} ");
			cancelorder($_W['openid'], $item['price'], $goods['gname'], $orderno, '');
			wl_json(1);
		}else{
			wl_json(0,'cancelamento nao provado！');
		}
	}else{
		wl_json(0,'falta numero do pedido');
	}
}

if($op =='topay'){
	$orderno = $_GPC['orderno'];
	if($orderno){
		$order = order_get_by_params(" orderno = '{$orderno}' ");
		if($order['status'] == 0){
			$goods = goods_get_by_params(" id = {$order['g_id']} ");
			if($goods['isshow'] == 1){
				wl_json(1);
			}else{
				wl_json(0,'o produto sem estoque');
			}
		}else{
			wl_json(0,'erro statu do pedido');
		}
	}else{
		wl_json(0,'falta numero do pedido');
	}
}

if($op =='receipt'){
	$orderno = $_GPC['orderno'];
	if($orderno){
		$order = order_update_by_params(array('status' => 4),array('id' => $orderno));
		if($order){
			wl_json(1);
		}else{
			wl_json(0,'confirmaçao do recebimento nao concluido！');
		}
	}else{
		wl_json(0,'falta numero do pedido');
	}
}

if($op =='tip'){
	$list = pdo_fetchall("SELECT * FROM ".tablename('tg_order')." WHERE uniacid = '{$_W['uniacid']}' AND status in(1,2,3,4) AND openid is not null order by rand() LIMIT 1"); 
	foreach($list as $k=> $v){
		$sql = 'SELECT * FROM '.tablename('tg_member').' WHERE openid=:openid and uniacid=:uniacid';
		$paramse = array(':openid'=>$v['openid'], ':uniacid'=>$_W['uniacid']);
		$members = pdo_fetch($sql, $paramse);		
		$list[$k]['nickname'] = mb_substr($members['nickname'], 0,3,'utf-8');	
		$list[$k]['avatar'] = $members['avatar'];	
		$citylist = pdo_fetch("select * from ".tablename('tg_address')." where uniacid = '{$_W['uniacid']}' and openid='{$v['openid']}'");
		$list[$k]['city'] = $citylist['city'];
	}
	$sec = rand(1,10);
	$result=array(
		'nickname'=>trim($list[0]['nickname']),
		'city'=>trim($list[0]['city']),	
		'avatar'=>$list[0]['avatar'],	
		'sec'=>$sec,	
	);
	echo json_encode($result);
}
