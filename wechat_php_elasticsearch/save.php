<?php
require_once('search/vendor/autoload.php');
$type=$_POST['type'];
$eventid=$_POST['eventid'];
$eventid=$eventid."|";
$userid=$_POST['userid'];
$client = new Elasticsearch\Client();
$params['index']="user";
$params['type']="weixin";
$params['body']['query']['term']['userid']=$userid;
$rtn=$client->search($params);
if(isset($rtn['hits']["hits"][0]["_source"]["save"]))
{
	$save=$rtn['hits']["hits"][0]["_source"]["save"];
}
else
$save="";
$_id=$rtn['hits']['hits'][0]['_id'];
$params['id']=$_id;
if($type=='0')
{
	$save=str_replace($eventid,"",$save);
	$params['body']=array(
		'doc'=>array(
			'save'=>$save));
	$client->update($params);
}
else
{
	$save=$save.$eventid;
	$params['body']=array(
		'doc'=>array(
			'save'=>$save));
	$client->update($params);
}
        ?>