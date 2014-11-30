<?php
require_once('vendor/autoload.php');
header("Content-Type:text/html;charset=utf8");
function search(){
	$client =new Elasticsearch\Client();

	// $params['index']="user";
	$params['index']="user";
	// $client->indices()->delete($params);
	$params['type']="weixin";
	// $params['id']="arf62F8NRyOaS-6WV9a8-g";
	// $params['body']=array('userid'=>'1234','latitude'=>'01','longitude'=>'02','address'=>"北师",'save'=>'1234|2345','page'=>0,'title'=>'互联网','city'=>"北京",'usercity'=>'北京','area'=>"海淀区",'sort'=>"0",'operation'=>'search','fakeid'=>0,'friendid'=>0,'username'=>'张三');
	// $params['body']=array('area'=>'');
	$params['id']="PH-jM46LQWCF4i2e2yC4Jg";
	print_r($client->get($params));
	// $params['body'][]=array(                
	// 	'doc'=>array(
	// 		'save'=>'5678'));	
	// // $params['body']['from']=1;
	// $userid=1234;
	// $params['body']['query']['']['userid']=$userid;
	// $rtn =$client->search($params);
	// if($rtn['hits']['total']==0)
	// 	{
	// 		$params['body']=array('userid'=>$userid);
	// 		$rtn=$client->index($params);
	// 	}
	// print_r($rtn);
	// echo $rtn['hits']["hits"][1]["_source"]["title"];
	// $params['index'] = 'birds';
	// // $params['body']['from']=10;
	// // $params['body']['size']=10;
	// $params['body']['query']['bool']['must']['bool']['should'][]['match']['info']="足球";
	// $params['body']['query']['bool']['must'][]['match']['city']="北京";
	// // $query['bool']['should'][]['match_phrase']['title']="足球";
	// // $query['bool']['should'][]['match_phrase']['info']="足球";
	// // $filter['term']['city']="北京";
	// // $params['body']['query']['filtered']=array(
	// // 	'filter'=>$filter,
	// // 	'query'=>$query);
	// $rtn =$client->search($params);
	// print $rtn['hits']["hits"][0]["_source"]["title"]."\n";
	// print $rtn['hits']['total'];
	// print_r($rtn);
}
search();