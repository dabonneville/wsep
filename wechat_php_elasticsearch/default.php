<?php
/**
 * 预处理
 */
require_once('search/vendor/autoload.php');
ini_set('max_execution_time','50000');
header("Content-Type:text/html;charset=utf8");
error_reporting(E_ALL&~E_NOTICE);
/*ini_set('error_log','php-errer.log');
ini_set('log_errors',true);*/
$client = new Elasticsearch\Client();
$userid;//微信用户的ID
$_id;
$page=0;
$userlatitude;
$userlongitude;
$flag;
/**
 * 调用
 */
$wechatObj = new wechat();
$wechatObj->responseMsg();
/**
 * [微信公共平台]
 * @var wechat
 */
class wechat {
/*    public $help="欢迎使用找乐微信平台!
    我们为您提供各类线下活动
    主要活动类型有：
    1.I T 风云   2.亲子家庭
    3.游园集市 4.文艺演出
    5.体育赛事 6.电影电视
    7.博物博览 8.留学移民

    以下为可键入命令信息
    =>“s#内容(#地点#类型)”:检索,括号内为可不填项,类型填序号,如“s#互联网”或“s#互联网#北京”
    =>“n”:获取下页结果
    =>“c”:获取您收藏的活动
    =>发送位置:服务更优质
    提醒:“#”键在数字键旁边,位置在输入栏旁边的“+”里";*/
    public $textTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        <FuncFlag>0</FuncFlag>
        </xml>";
    public $textImg = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <ArticleCount>%s</ArticleCount>
        <Articles>%s</Articles>
        </xml>";
    public $textItem="<item>
        <Title><![CDATA[%s]]></Title> 
        <Description><![CDATA[]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
        </item>";
    /**
     * [getBird elasticsearch检索]
     * @param  [type] $content [检索内容]
     * @param  [type] $city    [检索城市]
     * @param  [type] $sort    [检索类型]
     * @return [array]$result  [返回标题、图片、活动id]
     */
    public function getBird($content,$city,$sort)
    {
        global $client;
        global $userid;
        global $_id;
        global $page;
        $params['index']="bird";
        $params['body']['from']=$page;
        $params['body']['size']=4;
        $params['body']['query']['bool']['must']['bool']['should'][]['match_phrase']['title']=$content;
        $params['body']['query']['bool']['must']['bool']['should'][]['match_phrase']['info']=$content;
        if($city!="")
        { 
        if($city!="其他")
         $params['body']['query']['bool']['must'][]['match']['city']=$city;
        else
        {
            $params['body']['query']['bool']['must_not'][]['match']['city']="北京";
            $params['body']['query']['bool']['must_not'][]['match']['city']="上海";
            $params['body']['query']['bool']['must_not'][]['match']['city']="广州";
            $params['body']['query']['bool']['must_not'][]['match']['city']="武汉";
        }
        }
        if($sort!="")
        {
            if($sort!='5')
            $params['body']['query']['bool']['must']['bool']['must'][]['match']['sort']=$sort;
            else
            {
              // $params['body']['query']['bool']['must']['bool']['must'][]['match']['sort']=$sort;
            }
        }
        $bird_result=$client->search($params);
        $rtn=array();
        if(!isset($bird_result['hits']["hits"][0]["_source"]["title"]))
        {
            $rtn['error']="很抱歉，暂时没有您想关注的活动，我们将尽快添加！谢谢支持";
        }
        else {
        for($i=0;$i<4;$i++)
        {
            $rtn['title'][]=$bird_result['hits']["hits"][$i]["_source"]["title"];
            $rtn['imgSrc'][]=$bird_result['hits']["hits"][$i]["_source"]["imgSrc"];
            $rtn['eventid'][]=$bird_result['hits']["hits"][$i]["_source"]["eventid"];
        }}
        return $rtn;
    }
    /**
     * [getUser 判断是否为新用户，并将用户id存入公有变量]
     * @return [type] [无]
     */
    public function getUser()
    {
        global $client;
        global $userid;
        global $_id;
        global $page;
        global $userlatitude;
        global $userlongitude;
        global $flag;
        $params['index']='user';
        $params['type']='weixin';
        $params['body']['query']['term']['userid']=$userid;
        $user_result=$client->search($params);
        if($user_result['hits']['total']==0)
        {
            $params['body']=array('userid'=>$userid,'page'=>0);
            $rtn=$client->index($params);
            $page=0;
            $_id=$rtn['_id'];
        }
        else
        {
            // $page=$user_result['hits']['hits'][0]['_source']['page'];
            $_id=$user_result['hits']['hits'][0]['_id'];
            if(!empty($user_result['hits']['hits'][0]['_source']['latitude']))
            {
                $userlatitude=$user_result['hits']['hits'][0]['_source']['latitude'];
                $userlongitude=$user_result['hits']['hits'][0]['_source']['longitude'];
            }
            if(empty($user_result['hits']['hits'][0]['_source']['fakeid']))
            {
                $flag=1;
            }
        }
    }
    /**
     * [setAddress 存入用户的位置信息，并调用百度API获取用户的经纬度]
     * @param [type] $address   [所在位置的label]
     * @param [type] $latitude  [纬度]
     * @param [type] $longitude [经度]
     */
    public function setAddress($address,$latitude,$longitude)
    {
        global $client;
        global $_id;
        $url="http://api.map.baidu.com/geocoder/v2/?ak=DDa0545d1e03780687ead34bc4701ee1&callback=renderReverse&location=".$latitude.",".$longitude."&output=xml&pois=1";
        $rtn=file_get_contents($url);
        $rtnCnt=simplexml_load_string($rtn,'SimpleXMLElement',LIBXML_NOCDATA);
        $usercity=trim($rtnCnt->result->addressComponent->city);
        $area=trim($rtnCnt->result->addressComponent->district);
        $usercity=str_replace("市", "",(string)$usercity);
        $params['index']='user';
        $params['type']='weixin';
        $params['id']=$_id;
        $params['body']=array(
            'doc'=>array(
                'address'=>$address,
                'latitude'=>$latitude,
                'longitude'=>$longitude,
                'usercity'=>(string)$usercity,
                'area'=>(string)$area));
        $client->update($params);
    }
    /**
     * [getSave 返回用户的收藏]
     * @return [type] [返回标题、图片、活动id]
     */
    public function getSave(){
        global $client;
        global $userid;
        global $_id;
        global $page;
        $params['index']='user';
        $params['type']='weixin';
        $params['body']['query']['term']['userid']=$userid;
        $save_result=$client->search($params);
        // if(isset($save_result['hits']['hits'][0]['_source']['save']))
        $save_id=$save_result['hits']['hits'][0]['_source']['save'];
        if(empty($save_id))
        {
            $rtn['error']="您目前还没有收藏活动或收藏的活动已结束";
        }
        else{
            $eventid=explode('|',$save_id);
            $params=array();
            $params['index']='bird';
            for($i=0;$i<(count($eventid)-1);$i++)
            {
                $params['body']['query']['term']['eventid']=(string)$eventid[$i];
                $save_bird=$client->search($params);
                $rtn['title'][]=$save_bird['hits']["hits"][0]["_source"]["title"];
                $rtn['imgSrc'][]=$save_bird['hits']["hits"][0]["_source"]["imgSrc"];
                $rtn['eventid'][]=$save_bird['hits']["hits"][0]["_source"]["eventid"];
            }
        }
        return $rtn;
    }
    /**
     * [nextPage 返回下一页的结果]
     * @param  [type] $fromUsername [来源用户的id]
     * @param  [type] $toUsername   [目标用户的id]
     * @param  [type] $time         [时间戳]
     * @return [type]               [返回标题、图片、活动id]
     */
    public function nextPage($fromUsername,$toUsername,$time){
        global $client;
        global $userid;
        global $_id;
        global $page;
        $params['index']="user";
        $params['type']="weixin";
        $params['body']['query']['term']['userid']=$userid;
        $page_result=$client->search($params);
        $operation=$page_result['hits']['hits'][0]['_source']['operation'];
        $page=$page_result['hits']['hits'][0]['_source']['page']+4;
        $params['id']=$_id;
        $params['body']=array(
            'doc'=>array(
                'page'=>$page));
        $client->update($params); 
        if($operation=="search")
        {
        $keyContent=$page_result['hits']['hits'][0]['_source']['title'];
        $keyloc=$page_result['hits']['hits'][0]['_source']['city'];
        $keysort=$page_result['hits']['hits'][0]['_source']['sort'];
        $result=self::getBird($keyContent,$keyloc,$keysort);
        }
        else if($operation=='today')
        {
            $result=self::getToday();
        }
        else if($operation=='around')
        {
            $result=self::getAround();
        }
        if(isset($result['error']))
        {
            $resultStr=sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
        }
        else { 
        $num=4;
        $resultItem="";
        for($i=0;$i<4;$i++)
        {   
            $url="http://wjbianjason.eicp.net/display.php?eventid=".$result['eventid'][$i]."&userid=".$userid;
            $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
        }
        $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
        $time,$num,$resultItem);
        }
        return $resultStr;
    }
    public function getContact($friend,$message,$createTime){
            global $_id;
            $errno = 0; 
            $errstr = "";
            $timeout=5; 
            $fp=fsockopen('localhost',80,$errno,$errstr,$timeout);
            if(empty($friend))
            fputs($fp,"GET /weixindenglu.php?flag=0&message=$message&id=$_id&createTime=$createTime\r\n");
            else
            fputs($fp,"GET /weixindenglu.php?flag=0&message=$message&friend=$friend&id=$_id&createTime=$createTime\r\n");
            fclose($fp);
    }
    public function setfake($createTime)
    {   
            global $_id;
            $errno = 0; 
            $errstr = "";
            $timeout=5; 
            $fp=fsockopen('localhost',80,$errno,$errstr,$timeout);
            fputs($fp,"GET /weixindenglu.php?id=$_id&createTime=$createTime&flag=1\r\n");
            fclose($fp);
    }
    /**
     * [getToday 获取今天的活动，默认选择用户所在的城市]
     * @return [type] [返回标题、图片、活动id]
     */
    public function getToday(){
        global $client;
        global $userid;
        global $_id;
        global $page;
        $params=array();
        $params['index']='user';
        $params['type']="weixin";
        $params['body']['query']['term']['userid']=$userid;
        $today_result=$client->search($params);
        $content=$today_result['hits']['hits'][0]['_source']['title'];
        $usercity=$today_result['hits']['hits'][0]['_source']['usercity'];
        $city=$today_result['hits']['hits'][0]['_source']['city'];
        $params=array();
        $params['index']="bird";
        $params['body']['from']=$page;
        $params['body']['size']=4;
        $params['body']['query']['bool']['must']['bool']['should'][]['match']['title']=$content;
        $params['body']['query']['bool']['must']['bool']['should'][]['match']['info']=$content;
        $params['body']['query']['bool']['must'][]['match']['today']=1;
        if($usercity!="")
        {
         $params['body']['query']['bool']['must']['bool']['must'][]['match']['city']=$usercity; 
        }
        else if($city!="")
        {
         $params['body']['query']['bool']['must']['bool']['must'][]['match']['city']=$city; 
        }
        $resultToday=$client->search($params);
        if($resultToday['hits']['total']==0)
        {
            $answer['error']="今天您所在城市没有任何活动";
        }
        else{
            // $num=$resultToday['hits']['total'];
            // $params['body']['size']=$num;
            // $resultToday=$client->search($params);
            for($i=0;$i<4;$i++)
            {
                $answer['title'][]=$resultToday['hits']['hits'][$i]['_source']['title'];
                $answer['imgSrc'][]=$resultToday['hits']['hits'][$i]['_source']['imgSrc'];
                $answer['eventid'][]=$resultToday['hits']['hits'][$i]['_source']['eventid'];
            }
        }
        return $answer;
    }
    /**
     * [getdistance 通过经纬度计算距离]
     * @param  [type] $lng1 [经度1]
     * @param  [type] $lat1 [纬度1]
     * @param  [type] $lng2 [经度2]
     * @param  [type] $lat2 [纬度2]
     * @return [type]       [距离（米）]
     */
    public function getdistance($lng1,$lat1,$lng2,$lat2){
        $radLat1=deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2=deg2rad($lat2);
        $radLng1=deg2rad($lng1);
        $radLng2=deg2rad($lng2);
        $a=$radLat1-$radLat2;
        $b=$radLng1-$radLng2;
        $s=2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137*1000;
        return $s;
    }
    /**
     * [getAround 获取周围活动]
     * @return [type] [返回标题、图片、活动id]
     */
    public function getAround(){
        global $client;
        global $userid;
        global $_id;
        global $userlatitude;
        global $userlongitude;
        global $page;
        if(empty($userlatitude))
        {
            $answer['error']="您还没有发送位置";
            return $answer;
        }
        $params=array();
        $params['index']='user';
        $params['type']='weixin';
        $params['body']['query']['term']['userid']=$userid;
        $result_user=$client->search($params);
        $usercity=$result_user['hits']['hits'][0]['_source']['usercity'];
        $userarea=$result_user['hits']['hits'][0]['_source']['area'];
        $params=array();
        $params['index']='bird';
        $params['body']['query']['bool']['must'][]['match']['city']=$usercity;
        $params['body']['query']['bool']['must'][]['match']['area']=$userarea;
        $resultAround=$client->search($params);
        if($resultAround['hits']['total']==0)
        {
            $answer['error']="您附近没有任何活动";
        }
        else{
            $num=$resultAround['hits']['total'];
            $params['body']['size']=$num;
            $resultAround=$client->search($params);
            for($i=0;$i<$num;$i++)
            {
                $result['title'][]=$resultAround['hits']['hits'][$i]['_source']['title'];
                $result['imgSrc'][]=$resultAround['hits']['hits'][$i]['_source']['imgSrc'];
                $result['eventid'][]=$resultAround['hits']['hits'][$i]['_source']['eventid'];
                $distance=self::getdistance($userlongitude,$userlatitude,$resultAround['hits']['hits'][$i]['_source']['longitude'],$resultAround['hits']['hits'][$i]['_source']['latitude']);
                $range[(string)$distance]=$i;
            }
            if($page==0)
            {
                $a=current($range);
                for($i=0;$i<4;$i++)
                {
                    $answer['title'][]=$result['title'][$a];
                    $answer['imgSrc'][]=$result['imgSrc'][$a];
                    $answer['eventid'][]=$result['eventid'][$a];
                    $a=next($range);
                }
            }
            else{
                for($i=0;$i<$page-1;$i++)
                {
                    next($range);
                }
                for($i=0;$i<4;$i++)
                {
                    $a=next($range);   
                    $answer['title'][]=$result['title'][$a];
                    $answer['imgSrc'][]=$result['imgSrc'][$a];
                    $answer['eventid'][]=$result['eventid'][$a];
                }
            }
        } 
        return $answer;
    }
    /**
     * [responseEvent 对msg类型为event的消息进行回复]
     * @param  [type] $postObj      [返回的微信消息的对象]
     * @param  [type] $fromUsername [来源用户的id]
     * @param  [type] $toUsername   [目标用户的id]
     * @param  [type] $time         [时间戳]
     * @return [type]               [返回已经格式的回复信息]
     */
    public function responseEvent($postObj,$fromUsername,$toUsername,$time){
        global $client;
        global $userid;
        global $_id;
        global $page;
        $eventType=$postObj->Event;
        if($eventType='CLICK')
        {
            $eventKey=explode("|",$postObj->EventKey);
            if($eventKey[0]=="sort")
            {
                $params['index']="user";
                $params['type']="weixin";
                $params['id']=$_id;
                $params['body']=array(
                    'doc'=>array(
                        'sort'=>(string)$eventKey[1]));
                $client->update($params);
                switch ($eventKey[1]) {
                      case '1':
                          $answer="您已记录类型为IT风云";
                          break;
                      case '2':
                          $answer="您已记录类型为文艺演出";
                          break;
                      case '3':
                          $answer="您已记录类型为体育赛事";
                          break;
                      case '4':
                          $answer="您已记录类型为电影电视";
                          break;
                      case '5':
                          $answer="您已记录类型为其他";
                          break;                                                                              
                      default:
                          break;
                  }
                $resultStr=sprintf($this->textTpl,$fromUsername,$toUsername,$time,$answer);
            }
            else if($eventKey[0]=="city")
            {
                $params['index']="user";
                $params['type']="weixin";
                $params['id']=$_id;
                $params['body']=array(
                    'doc'=>array(
                        'city'=>(string)$eventKey[1]));
                $client->update($params);
                $answer="您已记录城市为".$eventKey[1];
                $resultStr=sprintf($this->textTpl,$fromUsername,$toUsername,$time,$answer);
            }
            else if($eventKey[0]="menu")
            {
                switch($eventKey[1])
                {
                    case 'today':
                    $params['index']="user";
                    $params['type']="weixin";
                    $params['id']=$_id;
                    $params['body']=array(
                        'doc'=>array(
                            'operation'=>'today',
                            'page'=>0));
                    $client->update($params);
                    $result=self::getToday();
                    if(isset($result['error']))
                    {
                        $resultStr =  sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
                    }
                    else
                    {
                        $num=count($result['title']);
                        $resultItem="";
                        for($i=0;$i<$num;$i++)
                        {   
                            $url="http://wjbianjason.eicp.net/display.php?eventid=".$result['eventid'][$i]."&userid=".$userid;
                            $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
                        }
                        $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
                        $time,$num,$resultItem);
                    }
                    break;       
                    case 'save':
                    $result=self::getSave();
                    if(isset($result['error']))
                    {
                        $resultStr =  sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
                    }
                    else
                    {
                        $num=count($result['title']);
                        $resultItem="";
                        for($i=0;$i<$num;$i++)
                        {   
                            $url="http://wjbianjason.eicp.net/display.php?eventid=".$result['eventid'][$i]."&userid=".$userid;
                            $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
                        }
                        $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
                        $time,$num,$resultItem);
                    }
                    break;
                    case 'next':
                    $resultStr=self::nextPage($fromUsername,$toUsername,$time);
                    break;
                    case 'around':
                    $params['index']="user";
                    $params['type']="weixin";
                    $params['id']=$_id;
                    $params['body']=array(
                        'doc'=>array(
                            'operation'=>'around',
                            'page'=>0));
                    $client->update($params);
                    $result=self::getAround();
                    if(isset($result['error']))
                    {
                        $resultStr =  sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
                    }
                    else
                    {
                        $num=count($result['title']);
                        $resultItem="";
                        for($i=0;$i<$num;$i++)
                        {   
                            $url="http://wjbianjason.eicp.net/display.php?eventid=".$result['eventid'][$i]."&userid=".$userid;
                            $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
                        }
                        $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
                        $time,$num,$resultItem);
                    }
                    break;
                    case 'contact':

                    $params['index']="user";
                    $params['type']="weixin";
                    $params['id']=$_id;
                    $resultOpe=$client->get($params);
                    $ope=$resultOpe['_source']['operation'];
                    if($ope=="contact")
                    {
                        $params['body']=array(
                        'doc'=>array(
                            'operation'=>'search'));
                        $client->update($params);
                        $answer="您已取消对话模式";
                    }
                    else
                    {
                        $params['body']=array(
                        'doc'=>array(
                            'operation'=>'contact'));
                    $client->update($params);
                    $answer="您已记录为对话模式";
                    }
                    $resultStr=sprintf($this->textTpl,$fromUsername,$toUsername,$time,$answer);
                    default:
                    break;                                                                      
                }
             }
        }
        else if($eventType=="location_select")
        {
            $getX=$postObj->SendLocationInfo->Location_X;
            $getY=$postObj->SendLocationInfo->Location_Y;
            $getlocation=$postObj->SendLocationInfo->Label;
            self::setAddress($getlocation,$getX,$getY);
            $content="您好,在详细信息里您将获得位置服务!";
            $resultStr = sprintf($this->textTpl,$fromUsername,$toUsername,$time,$content);
        }
        return $resultStr;
    }
    /**
     * [responseMsg 处理来源信息并回复]
     * @return [type] [返回格式化的回复信息]
     */
    public function responseMsg() {
        global $client;
        global $userid;
        global $_id;
        global $page;
        global $flag;
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //获取POST数据
        $postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
        //---------- 接 收 数 据 ---------- //
        $fromUsername = $postObj->FromUserName;
        $createTime = $postObj->CreateTime;
        $userid=(string)$fromUsername;
        self::getUser();
        $toUsername = $postObj->ToUserName; //获取接收方账号
        $time = time(); //获取当前时间戳
        $msgtype=$postObj->MsgType;
        if($msgtype=="location")
        {
            $getX=(string)($postObj->Location_X);
            $getY=(string)($postObj->Location_Y);
            $getlocation=(string)($postObj->Label);
            self::setAddress($getlocation,$getX,$getY);
            $content="您好,在详细信息里您将获得位置服务!";
            $resultStr = sprintf($this->textTpl,$fromUsername,$toUsername,$time,$content);
        }
        else if($msgtype=="event")
        {
            $resultStr=self::responseEvent($postObj,$fromUsername,$toUsername,$time);//call back the function that deal with event
        }
        else if($msgtype=="text")
        {
            if($flag=1)
            {
                self::setfake($createTime);
            }
            $keyContent = trim($postObj->Content); //获取消息内容
/*            if($keyContent=='1')
            {
                    $result=self::getToday();
                    if(isset($result['error']))
                    {
                        $resultStr =  sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
                    }
                    else
                    {
                        $num=count($result['title']);
                        $resultItem="";
                        for($i=0;$i<$num;$i++)
                        {   
                            $url="http://wjbianjason.eicp.net/display.php?eventid=".$result['eventid'][$i]."&userid=".$userid;
                            $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
                        }
                        $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
                        $time,$num,$resultItem);
                    }
            }
            else if($keyContent=='2')
            {
                $resultStr=self::nextPage($fromUsername,$toUsername,$time);
            }
            else if($keyContent=='3')
            {
                    $params['index']="user";
                    $params['type']="weixin";
                    $params['id']=$_id;
                    $params['body']=array(
                        'doc'=>array(
                            'operation'=>'around',
                            'page'=>0));
                    $client->update($params);
                    $result=self::getAround();
                    if(isset($result['error']))
                    {
                        $resultStr =  sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
                    }
                    else
                    {
                        $num=count($result['title']);
                        $resultItem="";
                        for($i=0;$i<$num;$i++)
                        {   
                            $url="http://wjbianjason.eicp.net/display.php?eventid=".$result['eventid'][$i]."&userid=".$userid;
                            $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
                        }
                        $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
                        $time,$num,$resultItem);
                    }
            }*/
            $params['index']="user";
            $params['type']="weixin";
            $params['body']['query']['term']['userid']=$userid;
            $resultOpe=$client->search($params);
            $ope=$resultOpe['hits']['hits'][0]['_source']['operation'];
            if($ope=="contact")
            {
                $comunication=explode('@',$keyContent);
                if(!isset($comunication[1]))
                {
                    // $handle=fopen('error.txt','w');
                    // fwrite($handle,$comunication[0].$comunication[1]);
                    // fclose($handle);
                    $comunication[1]="";
                    self::getContact($comunication[1],$comunication[0],$createTime);
                }
                else
                self::getContact($comunication[0],$comunication[1],$createTime);
                $resultStr="";
            }
            else
            {
                $params['index']="user";
                $params['type']="weixin";
                $params['id']=$_id;
                $params['body']=array(
                    'doc'=>array(
                        'page'=>0,
                        'title'=>$keyContent,
                        'operation'=>'search'));
                $client->update($params);
                $params=array();
                $params['index']="user";
                $params['type']="weixin";
                $params['body']['query']['term']['userid']=$userid;
                $page_result=$client->search($params);
                $keyloc=$page_result['hits']['hits'][0]['_source']['city'];
                $keysort=$page_result['hits']['hits'][0]['_source']['sort'];
                $result=self::getBird($keyContent,$keyloc,$keysort);
                if(isset($result['error']))
                {
                    $resultStr=sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
                }
                else {  
                $num=4;
                $resultItem="";
                for($i=0;$i<4;$i++)
                {   
                    $url="http://wjbianjason.eicp.net/display.php?eventid=".$result['eventid'][$i]."&userid=".$userid;
                    $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
                }
                $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
                $time,$num,$resultItem);
            }
            // }
        }}
            echo $resultStr; //输出结果
        }
}
?>