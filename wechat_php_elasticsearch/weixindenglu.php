<?
require_once ('class_weixin.php');
require_once('search/vendor/autoload.php');
ini_set('max_execution_time','50000');
header("Content-Type:text/html;charset=utf8");
$client=new Elasticsearch\Client();
$wxlogin = new WX_Remote_Opera();
$wxlogin->init('username','password');//填入账号密码
$flag=$_GET['flag'];
$id=$_GET['id'];
$createTime=$_GET['createTime'];
if($flag==1)
{
    $message=$wxlogin->getmsglist();
    $timestamp=$message[0]['date_time'];
    if($timestamp==$createTime)
    {
        $params['index']='user';
        $params['type']='weixin';
        $params['id']=$id;
        $params['body']=array(
            'doc'=>array(
                'fakeid'=>$message[0]['fakeid'],
                'username'=>$message[0]['nick_name']));
        $client->update($params);
    }
}
else
{
    $message=$_GET['message'];
    $params['index']='user';
    $params['type']="weixin";
    $params['id']=$id;
    $result=$client->get($params);
    $fromuser=$result['_source']['username'];
    $fromid=$result['_source']['fakeid'];
    $friendid="";
    if(isset($_GET['friend']))
    {
        $friend=$_GET['friend'];
        $sResult = $wxlogin->getsumcontactlist();
        $sum = 0;
        for($i = 0;$i < count($sResult);$i++){
            $sum  =$sum + $sResult[$i]['cnt'];
        }
        $page = ceil($sum/10);
        for($i = 0;$i < $page;$i++){
        $grest = $wxlogin->getcontactlist(10,$i);
            for($m = 0;$m < count($grest);$m++){
                if($friend==$grest[$m]['nick_name'])
                {
                    $friendid=$grest[$m]['id'];
                    goto a;
                }
            }
        }
        a:
        $params=array();
        $params['index']='user';
        $params['type']="weixin";
        $params['id']=$id;
        $params['body']=array(
            'doc'=>array(
                'friendid'=>$friendid));
        $client->update($params);
        $params=array();
        $params['index']='user';
        $params['type']="weixin";
        $params['body']['query']['term']['fakeid']=$friendid;
        $resultfriend=$client->search($params);
        $params=array();
        $params['index']='user';
        $params['type']="weixin";
        $params['id']=$resultfriend['hits']['hits'][0]['_id'];
        $params['body']=array(
            'doc'=>array(
                'friendid'=>$fromid));
        $client->update($params);
    }
    else
    {
        $friendid=$result['_source']['friendid'];
    }
    $wxlogin->sendmsg($fromuser.":".$message,$friendid,"");
}
    

?>