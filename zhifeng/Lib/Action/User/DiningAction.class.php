<?php
class DiningAction extends UserAction{
	public $token;
	public $dining_model;
	public $dining_cat_model;
	public $isDining;
	public $isBranch;
	public $company_model;
	public function _initialize() {
		parent::_initialize();

		$this->canUseFunction('dining');

		$this->token=session('token');
		$this->assign('token',$this->token);
		$this->dining_stores=M('Dining_company');
		$this->dining_model=M('Dining');
		//查询店铺列表
		$stores =M('Company')->where(array('token'=>$this->token))->select();
		if(!$stores){
			$this->error('请设置公司LBS信息',U('Company/index',array('token'=>$this->token)));
		}
		$this->stores=$stores;
		$this->assign('Stores',$stores);
		$storeid=$this->_get('storeid','intval');
		$this->storeid=$storeid;
		$this->assign('storeid',$storeid);
	}
	public function index(){
		$catid=intval($_GET['catid']);
		$dining_model=M('Dining');
		$dining_cat_model=M('Dining_cat');
		$where=array('token'=>$this->token);
		if($this->storeid){
			$where['storeid']=$this->storeid;
		}
		if ($catid){
			$where['catid']=$catid;
		}
		$where['groupon']=0;
        if(IS_POST){
            $key = $this->_post('searchkey');
            if(empty($key)){
                $this->error("关键词不能为空");
            }

            $map['token'] = $this->_get('token'); 
            $map['name|intro|keyword'] = array('like',"%$key%"); 
            $list = $dining_model->where($map)->select(); 
            $count      = $dining_model->where($map)->count();       
            $Page       = new Page($count,20);
        	$show       = $Page->show();
        }else{
        	$count      = $dining_model->where($where)->count();
        	$Page       = new Page($count,20);
        	$show       = $Page->show();
        	$list = $dining_model->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        }
		foreach($list as $k=>$v){
			$list[$k]['catname']=M('Dining_cat')->where(array('id'=>$v['catid']))->getField('name');
		}
		$this->assign('page',$show);		
		$this->assign('list',$list);
		$this->assign('isDiningPage',1);
		
		$this->display();		
	}
	public function cats(){
		$parentid=intval($_GET['parentid']);
		$parentid=$parentid==''?0:$parentid;
		$data=M('Dining_cat');
		$where=array('parentid'=>$parentid,'token'=>$this->token);
		if($this->storeid){
			$where['storeid']=$this->storeid;
		}
        if(IS_POST){
            $key = $this->_post('searchkey');
            if(empty($key)){
                $this->error("关键词不能为空");
            }

            $map['token'] = $this->_get('token'); 
            $map['name|des'] = array('like',"%$key%"); 
            $list = $data->where($map)->select(); 
            $count      = $data->where($map)->count();       
            $Page       = new Page($count,20);
        	$show       = $Page->show();
        }else{
        	$count      = $data->where($where)->count();
        	$Page       = new Page($count,20);
        	$show       = $Page->show();
        	$list = $data->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($list as $key=>$val){
				if($val['storeid']){
					$list[$key]['storename']=M('Company')->where(array('id'=>$val['storeid']))->getField('name');										
				}
			}
        }
		$this->assign('page',$show);		
		$this->assign('list',$list);
		if ($parentid){
			$parentCat = $data->where(array('id'=>$parentid))->find();
		}
		$this->assign('parentCat',$parentCat);
		$this->assign('parentid',$parentid);
		$this->display();		
	}
	public function catAdd(){ 
		if(IS_POST){
			//子分类继承上级分类storeid
			if($parentid !=0){
				$_POST['storeid']=M('Dining_cat')->where(array("id"=>$parentid))->getField('storeid');
			}
			$this->insert('Dining_cat','/cats?parentid='.$this->_post('parentid'));
		}else{
			$parentid=intval($_GET['parentid']);
			$this->assign('parentid',$parentid);
			$this->display('catSet');
		}
	}
	public function catDel(){
		if($this->_get('token')!=$this->token){$this->error('非法操作');}
        $id = $this->_get('id');
        if(IS_GET){                              
            $where=array('id'=>$id,'token'=>$this->token);
            $data=M('Dining_cat');
            $check=$data->where($where)->find();
            if($check==false)   $this->error('非法操作');
            $dining_model=M('Dining');
            $diningsOfCat=$dining_model->where(array('catid'=>$id))->select;
            if (count($diningsOfCat)){
            	$this->error('本分类下有商品，请删除商品后再删除分类',U('Dining/cats',array('token'=>$this->token,'storeid'=>$this->storeid)));
            }
            $back=$data->where($wehre)->delete();
            if($back==true){
                $this->success('操作成功',U('Dining/cats',array('token'=>$this->token,'parentid'=>$check['parentid'],'storeid'=>$this->storeid)));
            }else{
                 $this->error('操作失败');
            }
        }        
	}
	public function catSet(){
        $id = $this->_get('id'); 
		$checkdata = M('Dining_cat')->where(array('id'=>$id))->find();
		if(empty($checkdata)){
            $this->error("没有相应记录.您现在可以添加.",U('Dining/catAdd',array('token'=>$this->token,'storeid'=>$this->storeid)));
        }
		if(IS_POST){ 
            $data =D('Dining_cat');
			$parentid =$this->_post('parentid');
            //不能把自己放到自己或者自己的子目录们下面
			$pid_str = $this->get_catpids($parentid);
			$pid_arr =explode("|",$pid_str);
            if (in_array($id, $pid_arr)) {
                 $this->error("不能选择此分类");
            }
			//子分类继承上级分类storeid
			if($parentid !=0){
				$_POST['storeid']=M('Dining_cat')->where(array("id"=>$parentid))->getField('storeid');
			}
			if($data->create()){
				if($data->where($where)->save($_POST)){
					$this->success('修改成功',U('Dining/cats',array('token'=>$this->token,'parentid'=>$this->_post('parentid'),'storeid'=>$this->storeid)));	
				}else{
					$this->error('操作失败');
				}
			}else{
				$this->error($data->getError());
			}
		}else{
			//获得分类节点
            $spid = $this->get_catpids($checkdata['parentid']);
            $this->assign('selected_ids',$spid); //分类选中
			
			$this->assign('parentid',$checkdata['parentid']);
			$this->assign('set',$checkdata);
			$this->display();	
		
		}
	}
	public function add(){ 
		if(IS_POST){
			$catid=$pid = $this->_post('catid', 'intval');
			if(!$catid)  $this->error("请选择分类");
			$_POST['storeid']=M('Dining_cat')->where(array("id"=>$catid))->getField('storeid');
			$this->all_insert('Dining','/index?token='.$this->token.'&storeid='.$this->storeid);
		}else{
			$this->display('set');
		}
	}
	/**
	 * 商品类别ajax select
	 *
	 */
	public function ajaxCatOptions(){
		$pid = $this->_get('pid', 'intval');
        $catWhere=array('parentid'=>$pid,'token'=>$this->token);
		if($this->storeid){
			$catWhere['storeid']=$this->storeid;
		}
        $return = M('Dining_cat')->field('id,name')->where($catWhere)->select();
        if ($return) {
            $this->ajaxReturn(1,'操作成功', $return);
        } else {
            $this->ajaxReturn(0, '操作失败');
        }
	}
	public function set(){
        $id = $this->_get('id'); 
		$checkdata = $this->dining_model->where(array('id'=>$id))->find();
		if(empty($checkdata)){
            $this->error("没有相应记录.您现在可以添加.",U('Dining/add'));
        }
		if(IS_POST){ 
            $where=array('id'=>$this->_post('id'),'token'=>$this->token);
			$check=$this->dining_model->where($where)->find();
			if($check==false)$this->error('非法操作');
			
			$catid=$pid = $this->_post('catid', 'intval');
			if(!$catid)  $this->error("请选择分类");
			$_POST['storeid']=M('Dining_cat')->where(array("id"=>$catid))->getField('storeid');
			if($this->dining_model->create()){
				if($this->dining_model->where($where)->save($_POST)){
					$this->success('修改成功',U('Dining/index',array('token'=>$this->token)));
					$keyword_model=M('Keyword');
					$keyword_model->where(array('token'=>$this->token,'pid'=>$this->_post('id'),'module'=>'Dining'))->save(array('keyword'=>$this->_post('keyword')));
				}else{
					$this->error('操作失败');
				}
			}else{
				$this->error($this->dining_model->getError());
			}
		}else{
			//获得分类节点
            $spid = $this->get_catpids($checkdata['catid']);
            $this->assign('selected_ids',$spid); //分类选中

			$this->assign('isUpdate',1);
			$this->assign('set',$checkdata);
			$this->assign('isDiningPage',1);
			$this->display();	
		
		}
	}
	public function get_catpids($catid){
		$dining_cat_model=M('Dining_cat');
		$parentid = $dining_cat_model->where(array('id'=>$catid))->getField('parentid');
		if( $parentid==0 ){
			$spid = $catid;
		}else{
			$spid = $this->get_catpids($parentid)."|".$catid;
		}
		return $spid;
	}
	//商品类别下拉列表
	public function catOptions($cats,$selectedid){
		$str='';
		if ($cats){
			foreach ($cats as $c){
				$selected='';
				if ($c['id']==$selectedid){
					$selected=' selected';
				}
				$str.='<option value="'.$c['id'].'"'.$selected.'>'.$c['name'].'</option>';
			}
		}
		return $str;
	}
	public function del(){
		$dining_model=M('Dining');
		if($this->_get('token')!=$this->token){$this->error('非法操作');}
        $id = $this->_get('id');
        if(IS_GET){                              
            $where=array('id'=>$id,'token'=>$this->token);
            $check=$dining_model->where($where)->find();
            if($check==false)   $this->error('非法操作');

            $back=$dining_model->where($wehre)->delete();
            if($back==true){
            	$keyword_model=M('Keyword');
            	$keyword_model->where(array('token'=>$this->token,'pid'=>$id,'module'=>'Dining'))->delete();
                $this->success('操作成功',U('Dining/index',array('token'=>$this->token)));
            }else{
                 $this->error('服务器繁忙,请稍后再试',U('Dining/index',array('token'=>$this->token)));
            }
        }        
	}
	public function orders(){
		$dining_cart_model=M('Product_cart');
		if (IS_POST){
			if ($_POST['token']!=$this->_session('token')){
				exit();
			}
			if($_POST['allsmtype']){
				switch($_POST['allsmtype']){
					case 1:
						for ($i=0;$i<40;$i++){
							if (isset($_POST['id_'.$i])){
								$thiCartInfo=$dining_cart_model->where(array('id'=>intval($_POST['id_'.$i])))->find();
								if ($thiCartInfo['handled']){
								$dining_cart_model->where(array('id'=>intval($_POST['id_'.$i])))->save(array('handled'=>0));
								}else {
									$dining_cart_model->where(array('id'=>intval($_POST['id_'.$i])))->save(array('handled'=>1));
								}
							}
						}
						break;
					case 2:
						for ($i=0;$i<40;$i++){
							if (isset($_POST['id_'.$i])){
								$thiCartInfo=$dining_cart_model->where(array('id'=>intval($_POST['id_'.$i])))->delete();
							}
						}
						break;
					case 3:
						for ($i=0;$i<40;$i++){
							if (isset($_POST['id_'.$i])){
								$thiCartInfo=$dining_cart_model->where(array('id'=>intval($_POST['id_'.$i])))->find();
								$this->printer_order($thiCartInfo['token'],$thiCartInfo['storeid'],$thiCartInfo['orderid'],true);
							}
						}
						break;
				}
				$this->success('操作成功',U('Dining/orders',array('token'=>$this->token,'storeid'=>$this->storeid)));
				exit;
			}else{
				$key = $this->_post('searchkey');
				if(empty($key)){
					$this->error("关键词不能为空");
				}

				$where['truename|address|tel'] = array('like',"%$key%");
				$orders = $dining_cart_model->where($where)->select();
				foreach($orders as $key=>$val){
					$orders[$key]['storename']=M('Company')->where(array('id'=>$val['storeid']))->getField('name');	
				}
				$Page       = new Page($count,20);
				$show       = $Page->show();
				$count      = $dining_cart_model->where($where)->limit($Page->firstRow.','.$Page->listRows)->count();
			}
		}else{
			$where=array('token'=>$this->_session('token'),'dining'=>1);
			if($this->storeid){
				$where['storeid']=$this->storeid;
			}
		
			if (isset($_GET['handled'])){
				$where['handled']=intval($_GET['handled']);
			}
			$count      = $dining_cart_model->where($where)->count();
			$Page       = new Page($count,20);
			$show       = $Page->show();
			$orders=$dining_cart_model->where($where)->order('time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($orders as $key=>$val){
				$orders[$key]['storename']=M('Company')->where(array('id'=>$val['storeid']))->getField('name');	
			}
		}
		
			$unHandledCount=$dining_cart_model->where(array('token'=>$this->_session('token'),'handled'=>0,'dining'=>1))->count();
			$this->assign('unhandledCount',$unHandledCount);
			$this->assign('orders',$orders);
			$this->assign('page',$show);
			$this->display();
	}
	public function orderInfo(){
		$dining_cart_model=M('Product_cart');
		$dining_cart_list_model=M('Product_cart_list');
		$thisOrder=$dining_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		//检查权限
		if (strtolower($thisOrder['token'])!=strtolower($this->_session('token'))){
			exit();
		}
		if (IS_POST){
			$dining_cart_model->where(array('id'=>intval($_POST['id'])))->save(array('paid'=>intval($_POST['paid']),'sent'=>intval($_POST['sent']),'logistics'=>$_POST['logistics'],'logisticsid'=>$_POST['logisticsid'],'handled'=>1));
			$this->success('修改成功');
		}else {
			//订餐信息
			$diningtables_model=M('dining_tables');
			if ($thisOrder['tableid']) {
				$thisTable=$diningtables_model->where(array('id'=>$thisOrder['tableid']))->find();
				$thisOrder['tableName']=$thisTable['name'];
			}
			//
			$this->assign('thisOrder',$thisOrder);
			
			//订单产品列表
			$list =$dining_cart_list_model->where(array('cartid'=>intval($_GET['id'])))->select();
			foreach($list as $k=>$v){
				$product=$this->dining_model->where(array('id'=>$v['productid']))->find();
				$list[$k]['name'] =$product['name'];
				$list[$k]['logourl'] =$product['logourl'];
			}
			$this->assign('dinings',$list);
			//
			$this->assign('totalFee',$totalFee);
			$this->display();
		}
	}
	public function deleteOrder(){
		$dining_model=M('dining');
		$dining_cart_model=M('Product_cart');
		$dining_cart_list_model=M('Product_cart_list');
		$thisOrder=$dining_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		//检查权限
		$id=$thisOrder['id'];
		if ($thisOrder['token']!=$this->_session('token')){
			exit();
		}
		//删除订单和订单列表
		$dining_cart_model->where(array('id'=>$id))->delete();
		$dining_cart_list_model->where(array('cartid'=>$id))->delete();
		//商品销量做相应的减少
		$carts=unserialize($thisOrder['info']);
		foreach ($carts as $k=>$c){
			if (is_array($c)){
				$diningid=$k;
				$price=$c['price'];
				$count=$c['count'];
				$dining_model->where(array('id'=>$k))->setDec('salecount',$c['count']);
			}
		}
		$this->success('操作成功', U('Dining/orders',array('token'=>$this->token,'storeid'=>$this->storeid)));
	}
	//店铺列表
	public function stores(){
		$stores=$this->stores;
		foreach ($stores as $k=>$c){
				$storeid=$c['id'];
				$dining_company=M('dining_company')->where(array('storeid'=>$storeid))->find();
				$stores[$k]['time']=$dining_company['time'];
				$stores[$k]['scope']=$dining_company['scope'];
				$stores[$k]['areaid']=$dining_company['areaid'];
				$stores[$k]['category']=$dining_company['category'];
		}
		$where['token']=$this->token;
		$where['_string']="type='dining' or type=''";
		$category=M('Company_cate')->where($where)->select();
		$storearea=M('Company_area')->where($where)->select();
		$this->assign('category',$category);
		$this->assign('storearea',$storearea);
		
		$this->assign('stores',$stores);
		$this->display();
	}
	
	//店铺修改
	public function storeset(){
		if(IS_POST){
			$where=array('token'=>$this->token);
			$where['storeid'] =$this->_post('storeid'); 
			$_POST['sctime']=serialize($this->_post('sctime')); //sctime
			$thisCompany=$this->dining_stores->where($where)->find();
			if (!$thisCompany){
				$db   = M('Dining_company');
				if ($db->create()) {
					$id = $db->add();
					if ($id == true) {
						$this->success('添加成功', U('Dining/stores',array('token'=>$this->token,'storeid'=>$storeid)));die;
					} else {
						$this->error('添加失败');die;
					}
				}
			}else {
				if($this->dining_stores->create()){
					if($this->dining_stores->where($where)->save($_POST)){
						$this->success('修改成功',U('Dining/stores',array('token'=>$this->token,'storeid'=>$storeid)));die;
					}else{
						$this->error('修改失败');die;
					}
				}else{
					$this->error($this->dining_stores->getError());
				}
			}
			
		}
		$storeid=$this->_get('id');
		$store_info=M('Dining_company')->where(array('token'=>$this->token,'storeid'=>$storeid))->find();
		$Company_name=M('Company')->where(array('token'=>$this->token,'id'=>$storeid))->getField('name');
		//店铺分类，店铺区域
		$where['token']=$this->token;
		$where['_string']="type='dining' or type=''";
		$category=M('Company_cate')->where($where)->select();
		$storearea=M('Company_area')->where($where)->select();
		$this->assign('category',$category);
		$this->assign('storearea',$storearea);
		$store_info['storeid']=$storeid;
		$store_info['name']=$Company_name;
		$store_info['sctime']=unserialize($store_info['sctime']);
		$this->assign('set',$store_info);
		$this->display();
	}
	//店铺统计
	public function storecount(){
		$dining_cart_model=M('Product_cart');
		
		$starttime=strtotime(date('Y-m-d 00:00:00',time()));
		$endtime=strtotime(date('Y-m-d 23:59:59',time()));
		$statdate =strtotime($_POST['starttime'].' 00:00:00');
		$enddate =strtotime($_POST['endtime'].' 23:59:59');
		
		$starttime=$statdate?$statdate:$starttime;
		$endtime=$enddate?$enddate:$endtime;
		
		if($starttime>$endtime){
		 $this->error("开始时间大于结束时间，请认真设置后重试！！");
		}
		$where['time']=array('between',array($starttime,$endtime));
		
		if($this->storeid){
				$where['storeid']=$this->storeid;
				$dining_order=$dining_cart_model->where($where)->select();
				foreach($dining_order as $order){
					$totalprice+=$order['price'];
				}
				$stores['totalprice']=$totalprice;
				$totalprice=0;
				$stores['starttime']=$starttime;
				$stores['endtime']=$endtime;
				$stores['name']=M('Company')->where(array('id'=>$this->storeid))->getField('name');
				$storescount=array($stores);
		}else{	
			$storescount=$this->stores;
			foreach ($storescount as $k=>$c){
				$storeid=$c['id'];
				$where['storeid']=$storeid;
				$dining_order=$dining_cart_model->where($where)->select();
				foreach($dining_order as $order){
					$totalprice+=$order['price'];
				}
				$storescount[$k]['totalprice']=$totalprice;
				$totalprice=0;
				$storescount[$k]['starttime']=$starttime;
				$storescount[$k]['endtime']=$endtime;
			}				
		}
		$this->assign('storescount',$storescount);
		$this->assign('starttime',$starttime);
		$this->assign('endtime',$endtime);
		$this->display();
	}
	
	//桌台管理
	public function tables(){
		$diningtables_model=M('dining_tables');
		$where=array('token'=>$this->token);
		if($this->storeid){
			$where['storeid']=$this->storeid;
		}
		if(IS_POST){
			$tables = $diningtables_model->where($where)->select();
			$count      = $diningtables_model->where($where)->count();
			$Page       = new Page($count,20);
			$show       = $Page->show();
		}else {
			$count      = $diningtables_model->where($where)->count();
			$Page       = new Page($count,20);
			$show       = $Page->show();
			$tables=$diningtables_model->where($where)->order('taxis ASC')->limit($Page->firstRow.','.$Page->listRows)->select();
		}

		$this->assign('tables',$tables);
		$this->assign('page',$show);
		$this->display();
	}
	public function tableAdd(){ 
		if(IS_POST){
			$_POST['token']=$this->token;
			$this->insert('dining_tables','/tables?token='.$this->token);
		}else{
			$this->display('tableSet');
		}
	}
	public function tableSet(){
		$diningtables_model=M('dining_tables');
        $id = $this->_get('id'); 
		$checkdata = $diningtables_model->where(array('id'=>$id))->find();
		if(IS_POST){ 
            $where=array('id'=>$this->_post('id'),'token'=>$this->token);
			$check=$diningtables_model->where($where)->find();
			if($check==false)$this->error('非法操作');
			if($diningtables_model->create()){
				if($diningtables_model->where($where)->save($_POST)){
					$this->success('修改成功',U('Dining/tables',array('token'=>$this->token,'storeid'=>$this->storeid)));
				}else{
					$this->error('操作失败');
				}
			}else{
				$this->error($data->getError());
			}
		}else{
			$this->assign('set',$checkdata);
			$this->display();	
		
		}
	}
	public function tableDel(){
		if($this->_get('token')!=$this->token){$this->error('非法操作');}
        $id = $this->_get('id');
        if(IS_GET){                              
            $where=array('id'=>$id,'token'=>$this->token);
            $diningtables_model=M('dining_tables');
            $check=$diningtables_model->where($where)->find();
            if($check==false)   $this->error('非法操作');
           
            $back=$diningtables_model->where($wehre)->delete();
            if($back==true){
            	$this->success('操作成功',U('Dining/tables',array('token'=>$this->token,'storeid'=>$this->storeid)));
            }else{
                 $this->error('服务器繁忙,请稍后再试',U('Dining/tables',array('token'=>$this->token,'storeid'=>$this->storeid)));
            }
        }        
	}
	//获得打印机列表执行打印方法
	public function printer_order($token,$storeid,$orderid,$allprint=false){
		isset($token)?$token:$token=$this->_get('token');
		if($token!=$this->token){
			$this->error('错误操作！');exit;
		}
        isset($storeid)?$storeid:$storeid=$this->_get('storeid');
		isset($orderid)?$orderid:$orderid=$this->_get('orderid');
		//是否开启打印
		
		$printwhere['token']=$token;
		$printwhere['status']=1;
		$printwhere['_string']='storeid='.$storeid.' OR storeid=0';
		$printer_set_state =M('Printer_set')->where($printwhere)->getField('status');
		if ($printer_set_state==0) return;
		
		$content=$this->getPrintcontet($orderid);
		
		$set_state =M('Printer_set')->field('id')->where($printwhere)->order('storeid','DESC')->limit(0,3)->select();
		$url=U('Dining/orders',array('token'=>$token));
		foreach($set_state as $val){			
			$status=$this->executeprint($val['id'],$content);
		}
		if(!$allprint){
			$status?$this->success('打印成功！',$url):$this->error('打印失败！',$url);
		}
	}
	//获得要打印内容
	public function getPrintcontet($orderid){
		$thisOrder =M('Product_cart')->where(array('orderid'=>$orderid))->find();
		if ($thisOrder){
			switch ($thisOrder['diningtype']){
				case 1:
					$orderType='点餐';
					break;
				case 2:
					$orderType='外卖';
					break;
				case 3:
					$orderType='预定餐桌';
					break;
			}
			//订餐信息
			if ($thisOrder['tableid']) {
				$thisTable=M('dining_tables')->where(array('id'=>$thisOrder['tableid']))->find();
				$thisOrder['tableName']=$thisTable['name'];
			}else{
				$thisOrder['tableName']='未指定';
			}
			$str="订单类型：".$orderType."\r\n订单编号：".$thisOrder['orderid']."\r\n姓名：".$thisOrder['truename']."\r\n电话：".$thisOrder['tel']."\r\n地址：".$thisOrder['address']."\r\n桌台：".$thisOrder['tableName']."\r\n下单时间：".date('Y-m-d H:i:s',$thisOrder['time'])."\r\n打印时间：".date('Y-m-d H:i:s',time())."\r\n--------------------------------\r\n";
			//菜单
			$carts=unserialize($thisOrder['info']);
			$totalFee=0;
			$totalCount=0;
			$products=array();
			$ids=array();
			foreach ($carts as $k=>$c){
				if (is_array($c)){
					$productid=$k;
					$price=$c['price'];
					$count=$c['count'];
					if (!in_array($productid,$ids)){
						array_push($ids,$productid);
					}
					$totalFee+=$price*$count;
					$totalCount+=$count;
				}
			}
			if (count($ids)){
				$products=$this->dining_model->where(array('id'=>array('in',$ids)))->select();
			}
			if ($products){
				foreach ($products as $k=>$p){
					$products[$k]['count']=$carts[$p['id']]['count'];
					$str.=$p['name']." X ".$products[$k]['count']."份  单价：".$p['price']."元\r\n";
				}
			}
			$str.="\r\n----------------------------\r\n合计：".$thisOrder['price']."元\r\n";
			if($thisOrder['beizhu']!=''){
				$str=$str."     "."\r\n----------------------------\r\n".$thisOrder['beizhu'];
			}
			//$str=$str."     "."\r\n--------------------------------\r\n".$printermodel['Describe'];
			$str=$str."     "."\r\n------".$printer_set['head']."------\r\n";
			$str=$str."     "."\r\n     谢谢惠顾，欢迎下次光临\r\n----------------------------\r\n     智风微信提供技术支持\r\n";
			return $str;
		}else{
			$this->error("无效订单！");			
		}
		
	}
}


?>