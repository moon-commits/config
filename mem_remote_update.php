<?php
/*系统配置*/
//nohup php /root/shadowsocks-2.8.2/mem_remote_update.php >> /root/shadowsocks-2.8.2/sql.log 2>&1 &
ini_set('memory_limit','-1');
//单一键值存储数据过大会有问题,远程更新
class MEM
{
    public $mem;
    function __construct(){
        $this->mem = new Memcached();
        $this->mem->addServer('127.0.0.1', 11211);
    }
    public function __call($name,$args){
        return call_user_func_array([$this->mem, $name], $args);
    }
}
class updateUserInfo
{
    // config
    public $config;
    public $users;
    //数据库连接
    public $database = null;
    public $mem;
    public function __construct()
    {
    }
    #使用post的传输
	public static function post($url, $params=array(),$header=array()) { // 模拟提交数据函数
        /*
         * $header = array (
            "Content-Type:application/json",
            "Content-Type:x-www-form-urlencoded",
            "Content-type: text/xml",
		"Content-Type:multipart/form-data"
        )
         */
        //启动一个CURL会话
        $ch = curl_init();
        // 设置curl允许执行的最长秒数
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //忽略证书
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        // 获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_URL,$url);
        if(!empty($header)){
            curl_setopt( $ch, CURLOPT_HTTPHEADER,$header);
        }
        //发送一个常规的POST请求。
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($params)? http_build_query($params):$params);//可post多维数组
        //curl_setopt($ch, CURLOPT_HEADER,0);//是否需要头部信息（否）
        // 执行操作
        $result = curl_exec($ch);
        curl_close($ch);
        #返回数据
        return $result;
    }
    //uploaded,downloaded,isClosed
    public function getUserFromMemcached(){
        $mem        = new MEM();
        $user_data  = $post_data = [];
        $mem_aff    = $mem->getAllKeys();
        $affs       = array_values($mem_aff);
        if( !empty($affs) ) {
			foreach ($affs as $aff) {//更新用户流量
				$aff                = (int)$aff;
				$mem_data = $mem->get($aff);
				$mem_data = json_decode($mem_data,true);
				//$mem_data['isClosed']	= $mem_data['isClosed']??0;
				$mem_data['downloaded']	= $mem_data['downloaded']??0;
				$mem_data['uploaded']	= $mem_data['uploaded']??0;
				if( $mem_data['downloaded']!=0||$mem_data['uploaded']!=0 ){	
					$post_data[$aff]    = $user_data[$aff]  = $mem_data;
				}
			}
			foreach ($user_data as $aff=>$val){
				$user_data[$aff]['uploaded']=$user_data[$aff]['downloaded']=0;
				$mem->set($aff,json_encode($user_data[$aff]),(time()+600));//uploaded,downloaded 放空
			}
			//$server_ip = getHostByName(getHostName());
			//$url = "https://proxy.antss020.com/mem_remote.php";//大陆
			$url = "https://proxy.antss.me/mem_remote.php";
			$close_data = self::post($url,$post_data);
			$close_data = json_decode($close_data,true);
			if( !empty($close_data) ){
				foreach ($close_data as $aff=>$val){//数据库中没有这个 aff 记录
					$mem->set($aff,json_encode($close_data[$aff]),(time()+1800));//缓存
				}
			}
			echo date('Y-m-d H:i:s',time());echo "\n";
			print_r($close_data);
        }
    }
	//获取本机ip
}
while(1){
    $obj = new updateUserInfo();
    $obj->getUserFromMemcached();
    sleep(120);
}
?>
