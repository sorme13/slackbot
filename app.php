<?php
$http = new swoole_http_server("0.0.0.0", 9501);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "slack";

// Create connection
global $conn;
$conn = new mysqli($servername, $username, $password,$dbname);
$token='';
$client_id='';


function checkToken($client_id){


// Check connection
    if ($GLOBALS["conn"]->connect_error) {
        return ("Connection failed: " . $GLOBALS["conn"]->connect_error);
    }



    $sql = "SELECT * FROM info WHERE client_id=".$client_id;
    $result = $GLOBALS["conn"]->query($sql);


    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo 'nuuuuummmmmmmmmmm'.$row["token"].'--------'.$sql;
//        echo "id: " . $row["id"]. " - token: " . $row["token"]. "";
            global $token;
            $token=$row["token"];
            return $token;

        }
    } else {
        return "0 results";
    }


}

function route(swoole_http_request $request, swoole_http_response $response)
{

    if($GLOBALS['token']!=''){
        //------REQUEST FOR SCOPE PERMISSION -----------//
        if ($request->server['request_uri'] == '/reqscope') {






            $client_id= $GLOBALS["client_id"];
            $scope=$request->get["req"];
            $sopeArray= explode(",", $scope);
//             $response->end($sopeArray[0]);
            $response->redirect('https://slack.com/oauth/v2/authorize?scope='.$sopeArray[0].'&client_id='.$client_id);

        }
        //------HOME -----------//
        elseif ($request->server['request_uri'] == '/home') {
            $url = "https://slack.com/api/conversations.list";
            $arr = array("token" => $GLOBALS["token"]);

            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'get',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
            $decodded = json_decode($apiRes, true);
            if(isset($decodded['error'])  ){
                if( $decodded['error']=='missing_scope'){
                    $response->write('<h2>error :'.$apiRes.'</h2><br> 
       
            you have to ask for permission do you want?<br>
            <a style="display: inline-block" href="/reqscope?req='.$decodded['needed'].'"><button>ask permission</button> </a>
            <a style="display: inline-block" href="/home"><button> go back</button></a>
            

');
                }else{
                    $response->write($apiRes);
                }


            }else {
                $list = json_decode($apiRes, true);
                $response->write('<H2> here is list of channels of your account:</H2>');
                foreach ($list as $l) {
                    foreach ($l as $s) {
                        $response->write('
                              name:<span style="font-size: larger; font-weight: bold">' . $s['name'] . ' </span>  --- id:<span style="font-size: larger ; font-weight: bold"">' . $s['id'] . '</span><br>
                            <br>
                            <form  style="display: inline-block" method="get" action="/history">
                            <input name="id" type="hidden" value="' . $s['id'] . '">
                            <input name="name" type="hidden" value="' . $s['name'] . '">
                            <button type="submit"> history</button>
                            </form>
                            <form style="display: inline-block" method="post" action="/channelinfo">
                            <input name="channel" type="hidden" value="' . $s['id'] . '">
                            <button type="submit"> info</button>
                            </form>
                            <form style="display: inline-block" method="get" action="/joinchannel">
                            <input name="channel" type="hidden" value="' . $s['id'] . '">
                            <button type="submit"> join</button>
                            </form>
                             <form  style="display: inline-block" method="post" action="/channeleave">
                            <input name="channel" type="hidden" value="' . $s['id'] . '">
                            <button type="submit"> leave channel</button></form>  
                            <form  style="display: inline-block" method="post" action="/sendmsg">
                            <input name="channel" type="hidden" value="' . $s['id'] . '">
                            text: <input name="text" type="text" >
                            <button type="submit"> send message</button></form><br>');
                    }
                }
            }

        }
        //------GET CODE-----------//
        elseif ($request->server['request_uri'] == '/code') {
            if(isset($request->get['code'])){
                $response->write('
             <form method="post" action="/auth">
             <input type="text" name="code" value="'.$request->get['code'].'">
             client_id:<input type="text" name="client_id">
             client_secret:<input type="text" name="client_secret" >
             <input  type="hidden" name="redirect_uri" value="http://localhost:9501/code">
             <button type="submit"> get token</button>
            </form>
             ');

            }else{
                $response->write('no code result from slack');
            }

        }
        //------ AUTH -----------//
        elseif ($request->server['request_uri'] == '/auth') {
            $url = "https://slack.com/api/oauth.v2.access";
            $arr = array("client_id" => $request->post['client_id'], "code" => $request->post['code'], "client_secret" => $request->post['client_secret'],"redirect_uri"=>$request->post['redirect_uri']);


            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
//

            $decoded = json_decode($apiRes, true);

            if(isset($decoded['error']) ){
                $response->write('<h2>error :'.$decoded['error'].'</h2>');
            }
            else{
                $tokenRes=checkToken($request->post['client_id']);
                global $token;
                $token=$decoded['access_token'];
                if($tokenRes=='0 results'){

                    $client_id=$request->post['client_id'];


                    $sql="INSERT INTO info (token, client_id) VALUES ('$token', '$client_id')";
                    $result = $GLOBALS["conn"]->query($sql);
                }

                $response->write($apiRes.'<a href="/home"><button>home</button></a>');


            }

        }
        // ---PERMISSION INFO----//
        elseif ($request->server['request_uri'] == '/permissioninfo') {

            $url = "https://slack.com/api/apps.permissions.users.list";
            $arr = array("token" => $GLOBALS["token"]);


            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
            $response->end($apiRes);
            $channelList = json_decode($apiRes, true);
        }
        // -----OPTIONAL sSEND MESSAGE ----//
        elseif ($request->server['request_uri'] == '/send') {

            $url = "https://slack.com/api/conversations.list";
            $arr = array("token" => $GLOBALS["token"]);


            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);

            $channelList = json_decode($apiRes, true);

            $response->write(
                '
                     <html>
                    <title>HTML with PHP</title>
                    <body>
                    <h1>My Example</h1>
                    <form method="post" action="/sendmsg">
                    text<input type="text" name="text">
                    channel
                    <select type="text" name="channel">');
            foreach ($channelList as $channel) {

                foreach ($channel as $ch) {

                    $response->write(
                        '<option value="' . $ch["id"] . '">' . $ch["name"] . '</option>'
                    );
                }

            }

            $response->write('     
                    </select>
                    as user<select type="text" name="type_user">
                    <option value="app">app</option>
                    <option value="user">user</option>
                    </select>
                    <input type="submit">
                    </form>
                    </body>
                    </html>
           ');


        }
        //-----SEND MESSAGE-------//
        elseif ($request->server['request_uri'] == '/sendmsg') {

            if (!empty($request->post)) {

                if ($request->post['type_user'] == 'app') {
                    $url = 'https://slack.com/api/chat.meMessage';
                    $arr = array("token" => $GLOBALS["token"], "channel" => $request->post['channel'], "text" => $request->post['text']);
                } else {
                    $url = 'https://slack.com/api/chat.postMessage';
                    $arr = array("token"=> $GLOBALS["token"], "channel" => $request->post['channel'], "text" => $request->post['text'], "as_user" => "true", "username" => "swoole module", "icon_emoji" => ":chart_with_upwards_trend:");

                }

                $options = array(
                    'http' => array(
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method' => 'POST',
                        'content' => http_build_query($arr)
                    )
                );
                $context = stream_context_create($options);
                $apiRes = file_get_contents($url, true, $context);
                $decodded = json_decode($apiRes, true);

                if(isset($decodded['error']) ){
                    if($decodded['error']=='not_in_channel'){
                        $response->write('you are not a member of this channel, do you want to join?
            <br> <br>
            <a style="display: inline-block"  href="/send"> <button>go back</button></a> <form  style="display: inline-block"  action="/joinchannel" method="get"> <input type="hidden" name="channel" value="'.$request->post['channel'].'"><button type="submit">join</button> </form>' );
                    }else{
                        $response->write($apiRes );
                    }

                }else{
                    $response->write($apiRes.'<br>
             <a href="/home"><button>back</button></a>');

                }


            }

        }
        //------HISTORY OF A CHANNEL------//
        elseif ($request->server['request_uri'] == '/history') {

            $channelId = $request->get['id'];
            $url = "https://slack.com/api/conversations.history";
            $arr = array("token" => $GLOBALS["token"], "channel" => $channelId);


            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
            $decodded = json_decode($apiRes, true);




            if(isset($decodded['error'])  ){
                if($decodded['error']=='not_in_channel' ){
                    $response->write('you are not a member of this channel, do you want to join?
            <br> <br>
            <form style="display: inline-block"  method="post" action="/home"><input  name="token" type="hidden" value="'.$GLOBALS["token"].'"> <button type="submit"> no</button></form> <form  style="display: inline-block"  action="/joinchannel" method="get"><input type="hidden" name="token" value="'.$GLOBALS["token"].'"><input type="hidden" name="channel" value="'.$channelId.'"><button type="submit">join</button> </form>' );
                }elseif($decodded['error']='missing_scope'){
                    $response->write('<h2>error :'.$apiRes.'</h2><br> 
       
            you have to ask for permission do you want?<br>
            <a style="display: inline-block" href="/reqscope?req='.$decodded['needed'].'"><button> ask permission</button></a>
            <a style="display: inline-block" href="/home"> <button>go back</button></a>
            

');

                }else{
                    $response->write($apiRes);
                }

            }else{
                foreach ($decodded as $d) {
                    foreach ($d as $s) {
                        $response->write('text:<span style="font-size: larger; font-weight: bold">' . $s['text'] . '</span> <br>
                           <br> <form style="display: inline-block" method="post" action="/deletemsg">
                            <input type="hidden"  name="channel" value="' . $channelId . '">
                            <input type="hidden"  name="timestamp" value="' . $s['ts'] . '">
                            <button type="submit" >delete</button>
                            </form>
                            <form style="display: inline-block" method="post" action="/showreplies">
                            <input type="hidden"  name="channel" value="' . $channelId . '">
                            <input type="hidden"  name="timestamp" value="' . $s['ts'] . '">
                            <button type="submit" >show replies</button>
                            </form>
               
                            <form  style="display: inline-block"method="post" action="/getreaction">
                            <input type="hidden"  name="channel" value="' . $channelId . '">
                            <input type="hidden"  name="timestamp" value="' . $s['ts'] . '">
                            <button type="submit" >show reactions</button>
                            </form>
                            <form style="display: inline-block" method="post" action="/reaction">
                            <input type="hidden"  name="channel" value="' . $channelId . '">
                            <input type="hidden"  name="timestamp" value="' . $s['ts'] . '">
                            emoji name:<input  name="name"> 
                            <button type="submit" >send reaction</button>
                            </form><br>');
                    }

                }
            }


        }
        //------JOIN A CHANNEL-----------//
        elseif ($request->server['request_uri'] == '/joinchannel') {

            $url = "https://slack.com/api/conversations.join";
            $arr = array("token" => $GLOBALS["token"],"channel"=>$request->get['channel']);

            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
            $decodded = json_decode($apiRes, true);
            if(isset($decodded['error'])  ){

                if($decodded['error']=='missing_scope'){
                    $response->write('<h2>error :'.$apiRes.'</h2><br>    
                        you have to ask for permission do you want?<br>
                        <a style="display: inline-block" href="/reqscope?req='.$decodded['needed'].'"><button>ask permission</button> </a>
                        <a style="display: inline-block" href="/home"><button> go back</button></a>
                        ');
                }
                else{
                    $response->write('<h2>error :'.$apiRes.'</h2>');
                }
            }else{

                $response->write('you have joined channel <br>');
            }


        }
        //------DELETE MSG-----------//
        elseif ($request->server['request_uri'] == '/deletemsg') {

            $url = "https://slack.com/api/chat.delete";
            $arr = array("token" => $GLOBALS['token'], "channel" => $request->post['channel'], "ts" => $request->post['timestamp'], "as_suer" => "true");
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
//        $response->write($response1);
            $decodded = json_decode($apiRes, true);
            if(isset($decodded['error']) ){
                if($decodded['error']=='missing_scope'){
                    $response->write('<h2>error :'.$apiRes.'</h2><br>    
                        you have to ask for permission do you want?<br>
                        <a style="display: inline-block" href="/reqscope?req='.$decodded['needed'].'"><button>ask permission</button> </a>
                        <a style="display: inline-block" href="/home"><button> go back</button></a>
                        ');
                }
                else{
                    $response->write('<h2>error :'.$apiRes.'</h2>');
                }

            }else{
                $response->redirect('/history?id=' . $request->post['channel']);
            }

        }
        //-----------REPLY CONVERSATION-----------//
        elseif ($request->server['request_uri'] == '/showreplies'){



            $url = "https://slack.com/api/conversations.replies";
            $arr =  array("token" => $GLOBALS['token'], "channel" => $request->post['channel'],"ts"=>$request->post['timestamp']);
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
            $decodded = json_decode($apiRes, true);
            if(isset($decodded['error']) ){
                $response->write('<h2>error :'.$decodded['error'].'</h2>');
            }else{

                if(isset($decodded['messages'])){
                    if(count($decodded['messages'])<=1){
                        $response->write('no replies');
                    }else{
                        foreach ($decodded['messages'] as $key=>$reply){
                            if($key !=0){
                                $response->write('<ul>text :'.$reply['text'].'<li>user:'.$reply['user'].'</li> </ul><br>');
                            }
                        }
                    }

                }
            }

        }
        //-----------REACTION EMOJI-----------//
        elseif ($request->server['request_uri'] == '/reaction'){



            $url = "https://slack.com/api/reactions.add";
            $arr =  array("token" => $GLOBALS['token'], "channel" => $request->post['channel'],"timestamp"=>$request->post['timestamp'],"name"=>$request->post['name']);
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
            $decodded = json_decode($apiRes, true);
            if(isset($decodded['error']) ){
                if($decodded['error']=='missing_scope'){
                    $response->write('<h2>error :'.$apiRes.'</h2><br> 
       
            you have to ask for permission do you want?<br>
            <a style="display: inline-block" href="/reqscope?req='.$decodded['needed'].'"><button>ask permission</button> </a>
            <a style="display: inline-block" href="/home"><button> go back</button></a>
            

');
                }else{
                    $response->write('<h2>error :'.$apiRes.'</h2>');
                }

            }else{
                $response->end($apiRes);
                $response->redirect('/history?id=' . $request->post['channel']);
            }

        }
        //-----------CHANNEL INFO----------//
        elseif ($request->server['request_uri'] == '/channelinfo'){
            $url = "https://slack.com/api/conversations.info";
            $arr =  array("token" => $GLOBALS['token'], "channel" => $request->post['channel'],"include_num_members"=>true);
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
            $decodded = json_decode($apiRes, true);
            if(isset($decodded['error']) ){
                if($decodded['error']=='missing_scope'){
                    $response->write('<h2>error :'.$apiRes.'</h2><br>    
                        you have to ask for permission do you want?<br>
                        <a style="display: inline-block" href="/reqscope?req='.$decodded['needed'].'"><button>ask permission</button> </a>
                        <a style="display: inline-block" href="/home"><button> go back</button></a>
                        ');
                }else{
                    $response->write('<h2>error :'.$apiRes.'</h2>');
                }

            }else{
                $response->end($apiRes);
            }

        }
        //-----------LEAVE A CHANNEL----------//
        elseif ($request->server['request_uri'] == '/channeleave'){
            $url = "https://slack.com/api/conversations.leave";
            $arr =  array("token" => $GLOBALS['token'], "channel" => $request->post['channel']);
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
            $decodded = json_decode($apiRes, true);
            if(isset($decodded['error']) ){
                if($decodded['error']=='missing_scope'){
                    $response->write('<h2>error :'.$apiRes.'</h2><br>    
                        you have to ask for permission do you want?<br>
                        <a style="display: inline-block" href="/reqscope?req='.$decodded['needed'].'"><button>ask permission</button> </a>
                        <a style="display: inline-block" href="/home"><button> go back</button></a>
                        ');
                }else{
                    $response->write('<h2>error :'.$apiRes.'</h2>');
                }

            }else{
                $response->end($apiRes);
            }

        }
        //-----------REMOVE REACTION----------//
        elseif ($request->server['request_uri'] == '/removereaction'){

            $url = "https://slack.com/api/reactions.remove";
            $arr =  array("token" => $GLOBALS['token'], "channel" => $request->post['channel'],"timestamp"=>$request->post['timestamp'],"name"=>$request->post['name']);
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
            $decodded = json_decode($apiRes, true);
            if(isset($decodded['error']) ){
                if($decodded['error']=='missing_scope'){
                    $response->write('<h2>error :'.$apiRes.'</h2><br>    
                        you have to ask for permission do you want?<br>
                        <a style="display: inline-block" href="/reqscope?req='.$decodded['needed'].'"><button>ask permission</button> </a>
                        <a style="display: inline-block" href="/home"><button> go back</button></a>
                        ');
                }else{
                    $response->write('<h2>error :'.$apiRes.'</h2>');
                }

            }else{
                $response->end($apiRes);
            }

        }
        //-----------GET REACTIONs OF A MESSAGE----------//
        elseif ($request->server['request_uri'] == '/getreaction'){

            $url = "https://slack.com/api/reactions.get";
            $arr =  array("token" => $GLOBALS['token'], "channel" => $request->post['channel'],"timestamp"=>$request->post['timestamp']);
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
            $decodded = json_decode($apiRes, true);
            if(isset($decodded['error']) ){
                if($decodded['error']=='missing_scope'){
                    $response->write('<h2>error :'.$apiRes.'</h2><br>    
                        you have to ask for permission do you want?<br>
                        <a style="display: inline-block" href="/reqscope?req='.$decodded['needed'].'"><button>ask permission</button> </a>
                        <a style="display: inline-block" href="/home"><button> go back</button></a>
                        ');
                }else{
                    $response->write('<h2>error :'.$apiRes.'</h2>');
                }

            }else{
                if(isset($decodded['message']['reactions'])){
                    foreach ($decodded['message']['reactions'] as $r) {
                        $response->write('<ul>reaction name :'.$r['name']);
                        foreach ($r['users'] as $u){
                            $response->write('<li>----user :'.$u.'</li>');
                        }
                        $response->write('<form method="post" action="/removereaction">
                    <input type="hidden" name="timestamp" value="">
                    <input type="hidden" name="channel" value="'. $request->post['channel'].'">
                    <input type="hidden" name="name" value="'. $r['name'].'">
                    <input type="hidden" name="timestamp" value="'.$request->post['timestamp'].'">
                    <button  type="submit" > delete reaction</button>
                    </form></ul><br>');
                    }

                }else{
                    $response->write('no reactions');
                }

            }

        }


    }
    else{
        if ($request->server['request_uri'] == '/redirect') {
            $client_id=$request->post["client_id"];
            $scope=$request->post["scope"];
            $response->redirect('https://slack.com/oauth/v2/authorize?scope='.$scope.'&client_id='.$client_id);

        }

        elseif ($request->server['request_uri'] == '/code') {

            $response->write('
             <form method="post" action="/auth">
             <input type="text" name="code" value="'.$request->get['code'].'">
             client_id:<input type="text" name="client_id">
             client_secret:<input type="text" name="client_secret" >
             <input  type="hidden" name="redirect_uri" value="http://localhost:9501/code">
             <button type="submit"> get token</button>
</form>
             ');

        }

        elseif ($request->server['request_uri'] == '/auth') {
            $url = "https://slack.com/api/oauth.v2.access";
            $arr = array("client_id" => $request->post['client_id'], "code" => $request->post['code'], "client_secret" => $request->post['client_secret'],"redirect_uri"=>$request->post['redirect_uri']);


            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($arr)
                )
            );
            $context = stream_context_create($options);
            $apiRes = file_get_contents($url, true, $context);
//

            $decoded = json_decode($apiRes, true);

            if(isset($decoded['error']) ){
                $response->write('<h2>error :'.$decoded['error'].'</h2>');
            }
            else{
                $tokenRes=checkToken($request->post['client_id']);
                global $token;
                $token=$decoded['access_token'];
                if($tokenRes=='0 results'){

                    $client_id=$request->post['client_id'];


                    $sql="INSERT INTO info (token, client_id) VALUES ('$token', '$client_id')";
                    $result = $GLOBALS["conn"]->query($sql);
                }

                $response->write($apiRes.'<a href="/home"><button>home</button></a>');


            }

        }

        else{
            $response->redirect('/');
        }

    }

}


$http->on('request', function ($req, $resp) {
    if ($req->server['request_uri'] == '/') {

        if(isset($req->post['client_id']) ){
            global $client_id;
            $client_id =$req->post['client_id'];

            global $token;
            $token ='';
            header("Refresh:0");
            $tokenRes=checkToken($client_id);

//            $resp->end($client_id);
            if($tokenRes!='0 results'){
                global $token;
                $token=$tokenRes;
                $resp->redirect('/home');
            }else{
                $resp->write('
               You are not authorized please send a request to get aoutorized below:
                <form method="post" action="/redirect"> client id: <input name="client_id" type="text" value="'.$GLOBALS['client_id'].'"> scope:<input name="scope" type="text"><button type="submit"> get codde</button></form>
          ');
            }
        }else{
            $resp->write('
             
               please enter your client_id:<br>
                <form method="post" action="/"> client id: <input name="client_id" type="text"> <button type="submit">enter</button></form>
          ');
        }





    }else{
        route($req, $resp);
    }


});

$http->on('close', function () {
    echo "on close\n";
});

$http->start();
