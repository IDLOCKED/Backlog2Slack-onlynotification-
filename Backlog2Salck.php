<?php
/*
 *
    バックログお知らせ取得してslackに流すやつ
 *
 */


//Backlogスペース名
define("Spaceid", "XXXXX");
//BacklogAPIKEY
define("BacklogApikey", "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
//ヘッダ
define("Headers", "Content-Type: application/x-www-form-urlencoded");
//slackFullToken
define("SlackToken", "XXXXX-XXXXXXXXX-XXXXXXXXX-XXXXXXXXX-XXXXXXXXX");
//通知送り先のチャンネルIDはログインしてここで調べてね→https://api.slack.com/methods/channels.list/test
define("SlackChannel", "XXXXXXXX"); 
//SlackBot名
define("Botname", "BacklogBot");
//SlackBotアイコン
define("Boticonurl", "http://nulab-inc.com/download/backlog/png/glyph.png");
//前回のcreatedを保存するやつのファイル名
define("Filename", "tmp.txt");

//使うAPI
$api_url = 'notifications/count';
//クエリパラメーター
$params = array(
    'alreadyRead' => 0,
);
//実行
$notification_cnt = json_decode(get_backlog_data($api_url,$params), true);

//前回のcreated取得
$last_created = file_get_contents(Filename);

//新着確認
if($notification_cnt['count'] !== 0)){
    $api_url = 'notifications';
    $params = array(
        'count' => 10,
    );
    //取ってきたデータをjsonから配列に
    $notifications = json_decode(get_backlog_data($api_url,$params), true);
    //未読の同一のお知らせを通知しない
    if($last_created !== $notifications[0]['created']){
        //新着分だけ成形
        for ($i=0; $i < $notification_cnt['count']; $i++) { 
            $slack_posts['name']    = $notifications[$i]['sender']['name'];
            $slack_posts['url']     = 'https://'.Spaceid.'.backlog.jp/view/'.$notifications[$i]['issue']['issueKey'].'#comment-'.$notifications[$i]['comment']['id'];
            $slack_posts['summary'] = $notifications[$i]["issue"]["summary"];
            //実行
            echo post_2_slack($slack_posts['name'].'さんから<'.$slack_posts['url'].'|'.$slack_posts['summary'].'>の件のお知らせが届きました。');
        }
        //created保存
        file_put_contents(Filename, $notifications[0]['created']);
    }
}


function get_backlog_data($api_url,$params) {
    $url = 'https://'.Spaceid.'.backlog.jp/api/v2/'.$api_url.'?apiKey='.BacklogApikey.'&'.http_build_query($params,'','&');
    $context = array(
        'http' => array(
        'method'=> 'GET',
        'header'=> Headers,
        'ignore_errors' => true,
      )
    );
    $response = file_get_contents($url, false, stream_context_create($context));
    return $response;
}

function post_2_slack($text) {
    $url = 'https://slack.com/api/chat.postMessage?token='.SlackToken.'&channel='.SlackChannel.'&text='.urlencode($text).'&as_user=false&username='.Botname.'&icon_url='.Boticonurl;
    $response = file_get_contents($url);
    return $response;
}
