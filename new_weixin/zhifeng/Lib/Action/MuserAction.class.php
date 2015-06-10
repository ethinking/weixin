<?php
class MuserAction  extends UserAction{
	protected function _initialize(){
		parent::_initialize();
		
		// 引导用户选择公众号
		$token = session('token');
		
		if (empty($token) && MODULE_NAME != 'Index' && ACTION_NAME != 'index'){
			$this->redirect(U('Index/index')); 
		}
		
		// 如果已经登录、列出用户帐号下的所有公众号
		if (session('uid')!=false){
			$where['uid']=session('uid');
			$db=M('Wxuser');
			$wxusers=$db->where($where)->select();
			$this->assign('wxusers',$wxusers);
		}
		
		
	}
	
	protected function ___init_ppc_data_dir( &$url_path ){
		 
		$token = $_SESSION['token'];
		$first_c = mb_substr($_SESSION['token'],0,1,'UTF-8');
	
		$dir_Uploads =  SITE_ROOT.'/data/Uploads';
		$dir_first_c =  SITE_ROOT.'/data/Uploads/'.$first_c;
		$dir_token_d =  SITE_ROOT.'/data/Uploads/'.$first_c.'/'.$token;
		$dir_ckeditor_d = SITE_ROOT.'/data/Uploads/'.$first_c.'/'.$token.'/PhonePhotoUpload';
	
		if (!file_exists($dir_Uploads)||!is_dir($dir_Uploads)){
			mkdir($dir_Uploads,0777);
		}
	
		if (!file_exists($dir_first_c)||!is_dir($dir_first_c)){
			mkdir($dir_first_c,0777);
		}
	
		if (!file_exists($dir_token_d)||!is_dir($dir_token_d)){
			mkdir($dir_token_d,0777);
		}
	
		if (!file_exists($dir_ckeditor_d)||!is_dir($dir_ckeditor_d)){
			mkdir($dir_ckeditor_d,0777);
		}
	
		if (!file_exists($dir_ckeditor_d)||!is_dir($dir_ckeditor_d)){
			echo '初始化数据存放目录失败，不能使用上传功能！';
			exit();
		}else{
			$url_path = '/data/Uploads/'.$first_c.'/'.$token.'/PhonePhotoUpload';
			return $dir_ckeditor_d;
		}
	}
	
	/**
	 * 检查一个文件是不是由PhonePhotoUpload插件上传的，如果是，则从磁盘中删除它
	 * @param string $file_url 文件的http访问url地址，被视为网站绝对路径，类似：/data/Uploads/q/qbyszj1426650408/PhonePhotoUpload/1433908810_image0_63778.png
	 * @return bool 表示是否执行了删除操作（不管删除是否成功）
	 */
	protected function ___delete_ppc_file($file_url) {
		
		$keyword = 'PhonePhotoUpload';
		$status = mb_strpos($file_url, $keyword,0,'UTF-8');
		
		if ($status !== false){
			//找到了关键字，删除该文件
			@unlink(SITE_ROOT.$file_url);
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 从给出的字符串中删除由PhonePhotoUpload插件附加的HTML标签,类似：<div class="PhonePhotoUpload_image PhonePhotoUpload_image_118"><img imageid="118" data-cke-saved-src="/data/Uploads/q/qbyszj1426650408/PhonePhotoUpload/1433908810_image2_19387.png" src="/data/Uploads/q/qbyszj1426650408/PhonePhotoUpload/1433908810_image2_19387.png"></div>
	 * @param unknown $html
	 */
	protected function ___delete_ppc_htmltags($html,$imageid) {
		$pattern = '#<div class="PhonePhotoUpload_image PhonePhotoUpload_image_'.$imageid.'.*?</div>#is';
		return preg_replace($pattern,'',$html);
	}
}