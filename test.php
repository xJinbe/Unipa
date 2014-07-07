<?
namespace Example{
    use \Unipa;
    require_once "./1.1/Unipa.php";
    require_once "./1.1/UnipaViewer.php";
    require_once "./1.1/UnipaUtils.php";
    header("Content-Type: text/html; charset=UTF-8");
    
    try{
        /* インスタンス化 */
        $unipa = new Unipa\UnipaViewer();
        $unipa->debug(false);
        
        /* ログイン */
?>
        <h1>ログイン</h1>
<?
        if(!$unipa->login("userid", "password")){
            print "<b>ログイン失敗</b> <br>";
            die();
        }
        else{
            print "<b>ログイン成功</b> <br>";
        }
        
        
        /* 時間割を取得 */
        $timetable = $unipa->getTimetable();
?>
        <h1>時間割を取得</h1>
        <table border="1">
            <tr>
                <th>学期</th>
                <th>曜日</th>
                <th>時限</th>
                <th>科目ID</th>
                <th>科目名</th>
                <th>講師</th>
                <th>教室</th>
                <th>単位</th>
                <th>エラー</th>
            </tr>
<?
        foreach($timetable as $i => $tr){
            print "<tr>";
            foreach($tr as $j => $td){
                print "<td>".$td."</td>";
            }
            print "</tr>";
        }
?>
        </table>
<?

        /* お知らせリスト取得 */
?>
        <h1>お知らせリスト取得</h1>
        <table border="1">
            <tr>
                <th>カテゴリ</th>
                <th>タイトル</th>
                <th>発信元</th>
                <th>発信日</th>
                <th>識別ID</th>
            </tr>
<?
        $unipa->getLatestInformationList("information", function($arr){
                print "<tr>";
                foreach($arr as $i => $td){
                    print "<td>".$td."</td>";
                }
                print "</tr>";
        }, true);
?>
        </table>
<?
        
        /* 授業情報取得 */
        $tomorrow = $unipa->getTimetableByDay(date("Y-m-d", time() + 60*60*24*1));
?>
        <h1><?=date("Y年m月d日", time() + 60*60*24*1)?>の授業情報取得</h1>
        <table border="1">
            <tr>
                <th>時限</th>
                <th>科目ID</th>
                <th>科目名</th>
                <th>講師</th>
                <th>教室名</th>
                <th>休講</th>
                <th>補講</th>
                <th>通年</th>
            </tr>
<?
        foreach($tomorrow as $i => $tr){
            print "<tr>";
            foreach($tr as $j => $td){
                if(is_bool($td)) $td = $td ? "true" : "false";
                print "<td>".$td."</td>";
            }
            print "</tr>";
        }
?>
        </table>
<?


    }catch(Exception $e){
        switch($message = $e->getMessage()){
            case "Under_maintenance": 
                print "メンテナンス中です。 <br>"; 
                break;
            default: 
                print "不明な例外: " . $message;
        }
    }
}
?>