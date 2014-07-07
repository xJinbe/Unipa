<?
namespace Example{
    use \Unipa;
    require_once "../bin/Unipa.php";
    require_once "../bin/UnipaViewer.php";
    require_once "../bin/UnipaUtils.php";
    
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");
    
    try{
        /* インスタンス化 */
        $unipa = new Unipa\UnipaViewer();
        $unipa->debug(false);
        
        /* ログイン */
        if(!$unipa->login("Your ID", "Your Password")){
            print "<b>ログイン失敗</b> <br>";
            die();
        }
        else{
            print "<b>ログイン成功</b> <br>";
        }
        
        /* 明日の時間割を取得 */
        $picked = array();
        $tomorrow = $unipa->getTimetableByDay(date("Y-m-d", time() + 60*60*24*1));
        foreach($tomorrow as $i => $e){
            if($e["supplemented"]){
                send("明日".$e["lecture"]."限に「".$e["subject"]."」の補講があります。");
            }
            if($e["cancelled"]){
                send("明日".$e["lecture"]."限の「".$e["subject"]."」は休講になりました。");
            }
        }

    }catch(Exception $e){
        print $e->getMessage();
    }
    
    function send($body) {
        return mb_send_mail(
            "example@example.com",   // 宛先
            "Unipaからのお知らせ",   // 件名
            $body,                   // 本文
            "From: hoge@example.com" // 送信元（なんでもいい）
        );
    }
}
?>