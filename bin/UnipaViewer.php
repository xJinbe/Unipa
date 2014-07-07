<?php
namespace Unipa;

/**
 * Unipa - Universal Passport
 * Used and controled by Kinki University.
 * This system supposes be used under bost.jp, a flexible online service.
 * Extended for viewing unipa. Includes some convenient functions.
 * <THIS CLASS MUST EXTED Unipa CLASS AS PARENT CLASS>
 * 
 * @category   service
 * @package    Unipa
 * @author     Jinbe <my@wauke.org>
 * @copyright  2014 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.1.0
 * @since      Class available since Release 1.0.0
 */
 
class UnipaViewer extends Unipa {
    
    /* string  Information hash tag */
    protected $informationHashTag = "INFUNQID";
    
    /* int     Limit of pages */
    protected $syllabusSearchPagesLimit = 64;
    
    /* int     When began syllabus search, number of all pages are set here */
    public $syllabusSearchPages = 0;
    
    /* array   Syllabus labels */
    public $syllabusLabels = array(
        "curriculum_id" => "カリキュラムID",
        "syllabus_id" => "シラバスNO",
        "subject" => "科目名",
        "subject_en" => "英文科目名",
        "teachers" => "担当教員",
        "grade" => "開講年次",
        "credits" => "単位",
        "season" => "開講期",
        "field" => "分野",
        "category" => "科目区分",
        "type" => "必修選択の別",
        "comments" => "備考",
        "studies" => "学習・教育目標及び到達目標",
        "evaluation" => "成績評価方法および基準",
        "homeworks" => "授業時間外に必要な学修",
        "text" => "教科書",
        "references" => "参考文献",
        "related_subject" => "関連科目",
        "enquete" => "授業評価アンケート実施方法",
        "address" => "研究室・メールアドレス",
        "office_hours" => "オフィスアワー",
        "schedule" => "授業計画の項目・内容",
        "website" => "ホームページ",
        "year" => "開講年度",
        "day" => "開講日",
        "cycle" => "開講区分",
    );
    
    /* array   Front information section tags */
    public $frontInformationSections = array(
        array(
            "id" => 1,
            "tag" => "information",
            "title" => "連絡・お知らせ",
            "description" => "事務部からのお知らせです。",
        ),
        array(
            "id" => 2,
            "tag" => "rescheduled",
            "title" => "休講・補講情報",
            "description" => "休講や補講に関する情報です。",
        ),
        array(
            "id" => 3,
            "tag" => "curriculum",
            "title" => "授業・試験関連",
            "description" => "授業や試験に関する情報です。",
        ),
        array(
            "id" => 4,
            "tag" => "scholarship",
            "title" => "奨学金関連",
            "description" => "奨学金に関する情報です。",
        ),
    );
    
    
    /**
     * Convert dayname to index.
     * Support Japanese name and English name.
     * 
     * @param  string $dayname  Dayname
     * @return int              index number
     * @return bool             return false when failed converting
     */
    private function dayNameToIndex($dayname) {
        $table_ja = array("日", "月", "火", "水", "木", "金", "土");
        $table_en = array("sun", "mon", "tue", "wed", "thu", "fri", "sat");
        foreach($table_ja as $i => $name){
            if($dayname == $name) return $i;
        }
        foreach($table_en as $i => $name){
            if(strtolower($dayname) == $name) return $i;
        }
        return false;
    }
    
    
    /**
     * 
     */
    private function getFiscalYear($timestamp = 0) {
        $time = $timestamp ? $timestamp : time();
        return date("Y", $time - (31*2 + date("L", $time) ? 29 : 28) * 24 * 60 * 60);
    }
    

    /**
     * Launch timetable search engine at Unipa.
     * 
     * @param  int $fiscalYear  Target timetable year
     * @return string  Source of Unipa
     */
    protected function getTimetableSource($fiscalYear = 0, $season = 0, $format = 1) {
        
        // Update view ID and path before search
        $this->label("timetable_original");
        
        return $this->get(array(
            "form1:kaikoNendoInput" => $fiscalYear ? $fiscalYear : $this->getFiscalYear(),
            "form1:htmlGakki" => $season,
            "form1:HyojiKeishiki" => $format,
            "form1:search.x" => 0,
            "form1:search.y" => 0,
            "form1" => "form1",
        ));
    }
    
    
    /**
     * Launch timetable search engine at Unipa and Convert to array case.
     * Last comfirmed: April 14, 2014
     * 
     * @param  int $fiscalYear  Target timetable year
     * @return string  Source of Unipa
     */
    public function getTimetable($fiscalYear = 0) {
        $source = $this->getTimetableSource($fiscalYear);
        $result = array();
        // Timetable loop
        if(preg_match_all("/(?<=<table id=\"form1:standardJugyoTimeSchedule).*?(?=<\/table>)/s", $source, $m_table)){
            foreach($m_table[0] as $i => $e_table){
                // tbody
                if(preg_match_all("/(?<=<tbody>).*?(?=<\/tbody>)/s", $e_table, $m_tbody)){
                    // tr
                    if(preg_match_all("/(?<=<tr>).*?(?=<\/tr>)/s", $m_tbody[0][0], $m_tr)){
                        foreach($m_tr[0] as $j => $e_tr){
                            // td
                            $arr = array(
                                "season" => $i,
                                "day" => "",
                                "lecture" => "",
                                "curriculum_id" => "",
                                "subject" => "",
                                "teacher_name" => "",
                                "room_id" => "",
                                "credits" => "",
                                "error" => "",
                            );
                            if(preg_match("/<td class=\"jugyoKbn\"><span id=\"([0-9a-zA-Z-_:]+)\">(.+?)<\/span><\/td>/", $e_tr, $m_td)){
                                $e_daynumber = explode(" ", trim($m_td[2]), 2);
                                $arr["day"] = $this->dayNameToIndex($e_daynumber[0]);
                                $arr["lecture"] = $e_daynumber[1];
                                if($arr["day"] === false) $arr["day"] = null;
                            }
                            if(preg_match("/<td class=\"jugyoCd\"><span id=\"([0-9a-zA-Z-_:]+)\">(.+?)<\/span><\/td>/", $e_tr, $m_td)){
                                $arr["curriculum_id"] = trim($m_td[2]);
                            }
                            if(preg_match("/<a href=\"javascript:openSyllabus\(\'([0-9]+)\',\'([0-9a-zA-Z]+)\'\)\">(.+?)&nbsp;/i", $e_tr, $m_td)){
                                $arr["subject"] = trim($m_td[3]);
                            }
                            if(preg_match("/<td class=\"kyouinMei\"><span id=\"([0-9a-zA-Z-_:]+)\">(.+?)<\/span><\/td>/", $e_tr, $m_td)){
                                $arr["teacher_name"] = trim($m_td[2]);
                            }
                            if(preg_match("/<td class=\"kyositu\"><span id=\"([0-9a-zA-Z-_:]+)\">(.+?)<\/span><\/td>/", $e_tr, $m_td)){
                                $arr["room_id"] = trim($m_td[2]);
                            }
                            if(preg_match("/<td class=\"tani2\"><span id=\"([0-9a-zA-Z-_:]+)\">(.+?)<\/span><\/td>/", $e_tr, $m_td)){
                                $arr["credits"] = trim($m_td[2]);
                            }
                            if(preg_match("/<td class=\"erro\"><span id=\"([0-9a-zA-Z-_:]+)\">(.+?)<\/span><\/td>/", $e_tr, $m_td)){
                                $arr["error"] = trim($m_td[2]);
                            }
                            $result[] = $arr;
                        }
                    }
                }
            }
        }
        return $result;
    }

    
    /**
     * Download source of result of syllabus search.
     * This download session must be as series.
     * At first, set search options and send, this operation returns 0 page.
     * Second, send page number of results.
     * 
     * @param  int    $page        Page number od result
     * @param  int    $fiscalYear  Fiscal year
     * @return string              Source of search page.
     */
    public function getSyllabusListSource($page = 0, $fiscalYear = 0) {
        $result = "";
        if($this->currentRequestTag != "SyllabusSearch"){
            $this->label("syllabus");
            $result_first = $this->get(array(
                "form1:htmlNendo" => $fiscalYear ? $fiscalYear : $this->getFiscalYear(),
                "form1:htmlGakkiNo" => "",
                "form1:htmlKamokName" => "_",
                "form1:htmlKyoinSimei" => "",
                "form1:htmlGakka" => "11A",
                "form1:htmlGakunen" => "",
                "form1:htmlYobi" => "|all target|",
                "form1:htmlJigen" => "|all target|",
                "form1:search.x" => 0,
                "form1:search.y" => 0,
                "form1:htmlShikibetsuKbn" => 4,
                "form1:htmlKanriNo" => 5003287,
                "form1" => "form1",
            ), "SyllabusSearch");
            try{
                $this->syllabusSearchPages = $this->getSyllabusListAllPages($result_first);
            }
            catch(\Exception $err){
                $this->syllabusSearchPages = 0;
            }
            if(!$page) $result = $result_first;
        }
        
        if(!$result){
            $result = $this->get(array(
                "form1" => "form1",
                "form1:htmlKekkatable:web1__pagerWeb" => $page,
                "form1:htmlPage" => 0,
                "form1:_idcl:" => "",
            ), "SyllabusSearch");
        }
        
        return $result;
    }
    
    
    /**
     * Get all pages as int.
     * 
     * @param  string $source  Source of result pages.
     * @return int             Page nnumber
     */
    public function getSyllabusListAllPages($source) {
        if(preg_match("/1\/([0-9]+) ページ/", $source, $m) ||
           preg_match("/Page 1 of ([0-9]+)/i", $source, $m)){
            return $m[1];
        }
        else{
            throw new \Exception("Page_number_not_found");
        }
    }
    

    /**
     * Get search result as array.
     * You can select type of result, only primary or full version.
     * 
     * @param  function $callback          Callback function; called per topic.
     * @param  int      $maxPageIndex      Page limit. 0 = unlimited.
     * @param  bool     $primaryLabelOnly  Flag of primary only
     * @return array                       Syllabus contents
     */
    public function getSyllabusList($callback = null, $maxPageIndex = 0, $fiscalYear = 0, $primaryLabelOnly = false) {
        $fiscalYear = $fiscalYear ? $fiscalYear : $this->getFiscalYear();
        $resultPageIndex = 0;
        for($i = 0; $i < $this->syllabusSearchPagesLimit; $i++){
            $source = $this->getSyllabusListSource($i, $fiscalYear);
            if(preg_match_all("/(?<=<tr class=\"rowClass1\">).*?(?=<\/tr>)/s", $source, $m_tr)){
                foreach($m_tr[0] as $j => $e_tr){
                    // Available parameters
                    $arr = array();
                    foreach($this->syllabusLabels as $k => $label_ja) $arr[$k] = "";
                    
                    // Get fiscal year; 
                    $arr["year"] = $fiscalYear;
                    
                    // Get curriculum ID
                    if(preg_match("/class=\"outputText\" title=\"([0-9a-zA-Z]{6})/", $e_tr, $m)){
                        $arr["curriculum_id"] = $m[1];
                    }
                    
                    // Get day and time
                    if(preg_match("/htmlKaikoYobiCol\" class=\"outputText\">(.+?)<\/span>/", $e_tr, $m)){
                        $arr["day"] = trim(strip_tags($m[1]));
                    }
                    
                    // Get cycle
                    if(preg_match("/htmlJyugyoKbnCol\" class=\"outputText\">(.+?)<\/span>/", $e_tr, $m)){
                        $arr["cycle"] = trim(strip_tags($m[1]));
                    }
                    
                    // primary only: TODO
                    if($primaryLabelOnly){
                        die("TODO");
                    }
                    
                    // or full
                    else{
                        $syllabusContents = $this->get(array(
                            "form1:htmlKekkatable:web1__pagerWeb" => $i,
                            "form1:htmlPage:" => 0,
                            "form1" => "form1",
                            "form1:_idcl" => "form1:htmlKekkatable:{$resultPageIndex}:edit",
                        ));
                        foreach($this->parseSyllabusContents($syllabusContents) as $k => $e){
                            if(!isset($arr[$k]) || !$arr[$k]) $arr[$k] = $e;
                        }
                        
                        // Reset view ID and path, so needs two times processing.
                        $this->get(array(
                            "form1:backhidden" => "戻る",
                            "form1:htmlJugyoCd" => $arr["id"],
                            "form1:htmlNendo" => $fiscalYear,
                            "form1:htmlSanshoTblFlg" => 1,
                            "form1" => "form1",
                        ), "SyllabusSearch");
                    }
                    
                    // Generate unique ID
                    $arr["id"] = $arr["year"].$arr["curriculum_id"];
                    
                    // callback
                    if(UnipaUtils::is_function($callback)){
                        $callback($arr);
                    }

                    // update index
                    $resultPageIndex++;
                    
                    // check limit
                    if($maxPageIndex && $resultPageIndex >= $maxPageIndex){
                        break 2;
                    }
                }
            }
            
            // stop
            if(!$i && $i + 1 >= $this->syllabusSearchPages){
                break;
            }
        }
    }

    
    /**
     * Parse Syllabus contents source as array.
     * 
     * @param  string $source  Source of syllabus information.
     * @return array           Syllabus detail
     */
    public function parseSyllabusContents($source) {
        $th_label = "";
        $result = array();
        if(preg_match_all("/(?<=<TABLE class = \"gyoTable listTable\">).*?(?=<\/TABLE>)/s", $source, $m_table)){
            if(preg_match_all("/<(th|td)([0-9a-zA-Z_\-\"\':;= ]*)>(.+?)<\/(th|td)>/i", $m_table[0][0], $m_cell)){
                foreach($m_cell[0] as $i => $val){
                    
                    // Label
                    if(strtolower($m_cell[1][$i]) == "th"){
                        if(preg_match("/<div class=\"left\">(.+?)<\/div>/i", $val, $m_title)){
                            $th_label_ja = trim($m_title[1]);
                            $th_label = "unknown";
                            foreach($this->syllabusLabels as $j => $label_ja){
                                if($th_label_ja == $label_ja){
                                    $th_label = $j;
                                    break;
                                }
                            }
                        }
                        else{
                            $th_label = "";
                        }
                    }
                    
                    // Contents
                    else if($th_label){
                        $result[$th_label] = UnipaUtils::space_trim(strip_tags(str_ireplace(array("<br>", "&nbsp;"), array("\n", " "), $val)));
                        $th_label = "";
                    }
                }
            }
        }
        return $result;
    }


    
    /**
     * Get user's timetable by selecting day.
     * This information may changed everyday, and including rescheduled or postponed classes.
     * 
     * @param  mixed $year   Year as int or Y-m-d format as string.
     * @param  int   $month  Month
     * @param  int   $day    Day
     * @return array         Result of day's simple timetable    
     */
    public function getTimetableByDay($year, $month = 0, $day = 0) {
        if($this->currentRequestTag != "home" && $this->currentRequestTag != "logged_in"){
            $this->label("home");
        }
        $result = array();
        $date_label_count = 0;
        $source = $this->get(array(
            "form1:Poa00101A:htmlCurDate" => date("Y-m-d"),
            "form1:Poa00101A:htmlHidden_selectDay" => is_string($year) ? $year : sprintf("%4d-%2d-%2d", $year, $month, $day),
            "form1:hidden1:" => "",
            "form1:Poa00401A:selectJugyo:" => "",
            "form1:Poa00301A:htmlPrjTable:0:htmlLinkUrl" => "http://www.kindai.ac.jp/",
            "form1:Poa00301A:htmlPrjTable:0:htmlLinkPrm" => "",
            "form1:Poa00301A:htmlPrjTable:0:htmlLinkMtd" => "POST",
            "form1:Poa00301A:htmlPrjTable:1:htmlLinkUrl" => "http://www.waka.kindai.ac.jp/",
            "form1:Poa00301A:htmlPrjTable:1:htmlLinkPrm" => "",
            "form1:Poa00301A:htmlPrjTable:1:htmlLinkMtd" => "POST",
            "form1:Poa00301A:htmlPrjTable:2:htmlLinkUrl" => "https://unipa.itp.kindai.ac.jp/up/faces/login/Com00501A.jsp",
            "form1:Poa00301A:htmlPrjTable:2:htmlLinkPrm" => "",
            "form1:Poa00301A:htmlPrjTable:2:htmlLinkMtd" => "POST",
            "form1:htmlKeijiSearchOpenFlg" => "0",
            "form1" => "form1",
        ), "home");
        if(preg_match("/<table id=\"form1:Poa00401A:htmlTodayJikanTable\"(.+?)<\/table>/is", $source, $m_table)){
            if(preg_match_all("/(?<=<tr>).*?(?=<\/tr>)/s", $m_table[1], $m_tr)){
                foreach($m_tr[0] as $i => $val){
                    if(strpos($val, "htmlTitleDate") !== false){
                        if($date_label_count) break;
                        $date_label_count++;
                    }
                    $arr = array(
                        "lecture" => "",
                        "curriculum_id" => "",
                        "subject" => "",
                        "teacher_name" => "",
                        "room_name" => "",
                        "cancelled" => false,
                        "supplemented" => false,
                        "allyear" => false,
                    );
                    if(preg_match("/\"jyugyoMark\"><span>([0-9])限目/i", $val, $m)){
                        $arr["lecture"] = $m[1];
                    }
                    if(preg_match("/selectClassProfile\(\'([0-9a-zA-Z]+)\'/", $val, $m)){
                        $arr["curriculum_id"] = $m[1];
                    }
                    if(preg_match("/selectClassProfile\(\'([0-9a-zA-Z]+)\'\);\">(.+?)<\/a>/i", $val, $m)){
                        $arr["subject"] = $m[2];
                    }
                    if(preg_match("/class=\"kyoin\">(.+?)<br>/i", $val, $m)){
                        $arr["teacher_name"] = $m[1];
                    }
                    if(preg_match("/class=\"kyositu\">(.+?)<br>/i", $val, $m)){
                        $arr["room_name"] = $m[1];
                    }
                    if(strpos($val, "kyuko_red.gif") !== false){
                        $arr["cancelled"] = true;
                    }
                    if(strpos($val, "hoko.gif") !== false){
                        $arr["supplemented"] = true;
                    }
                    if(strpos($val, "tunen_1.gif") !== false){
                        $arr["allyear"] = true;
                    }
                    $result[] = $arr;
                }
            }
        }
        return $result;
    }


    /**
     * Download latest information from unipa.
     * This method 
     * 
     * @param  string    $section   Section ID
     * @param  bool      $onlyHead  If you need only title, from and data cell, set true.
     * @param  function  $callback  Callback function; called per topic.
     * @return void
     * @throws \Exception Unknown_section_name, Unavailable_section_title
     */
    public function getLatestInformationList($section, $callback = null, $onlyHead = false) {
        // update view ID and path.
        if($this->currentRequestTag != "home" && $this->currentRequestTag != "logged_in"){
            $this->label("home");
        }
        $date = date("Y-m-d");
        
        // resolve section code
        $sectionCode = null;
        $sectionTitle = null;
        foreach($this->frontInformationSections as $i => $e){
            if($e["tag"] == $section){
                $sectionCode = $e["id"];
                $sectionTitle = $e["title"];
                break;
            }
        }
        //$sectionCode = 2; //debug
        if($sectionCode === null) throw new \Exception("Unknown_section_name");
        
        // get list page
        $source = $this->get(array(
            "form1:Poa00101A:htmlCurDate" => $date,
            "form1:Poa00101A:htmlHidden_selectDay" => $date,
            "form1:hidden1" => "",
            "form1:Poa00401A:selectJugyo" => "",
            "form1:Poa00301A:htmlPrjTable:0:htmlLinkUrl" => "http://www.kindai.ac.jp/",
            "form1:Poa00301A:htmlPrjTable:0:htmlLinkPrm" => "",
            "form1:Poa00301A:htmlPrjTable:0:htmlLinkMtd" => "POST",
            "form1:Poa00301A:htmlPrjTable:1:htmlLinkUrl" => "http://www.waka.kindai.ac.jp/",
            "form1:Poa00301A:htmlPrjTable:1:htmlLinkPrm" => "",
            "form1:Poa00301A:htmlPrjTable:1:htmlLinkMtd" => "POST",
            "form1:Poa00301A:htmlPrjTable:2:htmlLinkUrl" => "https://unipa.itp.kindai.ac.jp/up/faces/login/Com00501A.jsp",
            "form1:Poa00301A:htmlPrjTable:2:htmlLinkPrm" => "",
            "form1:Poa00301A:htmlPrjTable:2:htmlLinkMtd" => "POST",
            "form1:Poa00201A:htmlParentTable:{$sectionCode}:htmlDisplayOfAll:0:allInfoLinkCommand" => "",
            "form1:htmlKeijiSearchOpenFlg" => "0",
            "form1" => "form1",
        ));
        
        // check title
        $cnt = substr_count($source, "<td width=\"700px\" height=\"35px\" style");
        if($cnt > 1){
            // TODO
            die("TODO: Unsupported showing only category.");
            return;
        }
        else if(!$cnt){
            throw new \Exception("Unavailable_section_title");
        }
        
        // resolve
        if(preg_match_all("/(?<=<tr class=\"rowHeight\">).*?(?=<\/tr>)/s", $source, $m_tr)){
            foreach($m_tr[0] as $i => $e_tr){
                $arr = array(
                    "section" => $section,
                );

                // common section
                if(preg_match("/<span id=\"(.*?)\" title=\"(.*?)\">(.*?)<\/span>/s", $e_tr, $m)){
                    $arr["title"] = trim($m[3]);
                }
                else{
                    continue;
                }
                if(preg_match("/<span class=\"from\" title=\"(.*?)\">/s", $e_tr, $m)){
                    $arr["from"] = trim($m[1]);
                }
                else{
                    continue;
                }
                if(preg_match("/class=\"insDate\">(.*?)<\/span>/s", $e_tr, $m)){
                    $arr["date"] = str_replace(array("&nbsp;", "[", "]", "/"), array("", "", "", "-"), trim($m[1]));
                }
                else{
                    continue;
                }

                // create unique ID
                $arr["id"] = $this->getInformationUniqueId($arr);
                                
                if(!$onlyHead){
                    // remove session
                    $this->postContents(
                        "/ajax/up/co/RemoveSessionAjax",
                        array(
                            "target" => "null",
                            "windowName" => "Poa00201A",
                            "pcClass" => "com.jast.gakuen.up.po.PPoa0202A",
                        ),
                        array($this->sessionIdName => $this->sessionId),
                        true
                    );
                    
                    // get sub window contents
                    $subWindowContents = $this->postContents(
                        "/up/po/pPoa0202A.jsp",
                        array("fieldId" => "form1:Poa00201A:htmlParentTable:{$sectionCode}:htmlDetailTbl2:{$i}:linkEx2"),
                        array($this->sessionIdName => $this->sessionId),
                        true
                    );
                    
                    // resolve body contents
                    if(preg_match_all("/(?<=<td class=\"mainTextScroll\">).*?(?=<\/td>)/s", $subWindowContents, $m)){
                        $arr["body"] = trim(strip_tags(str_replace(array("<br>", "&nbsp;"), array("\n", " "), $m[0][0])));
                    }
                    
                    // resolve attachment contents
                    if(preg_match_all("/(?<=<table id=\"form1:htmlFileTable\").*?(?=<\/table>)/s", $subWindowContents, $m_table)){
                        if(preg_match_all("/(?<=<tbody>).*?(?=<\/tbody>)/s", $m_table[0][0], $m_tbody)){
                            if(preg_match_all("/(?<=<tr>).*?(?=<\/tr>)/s", $m_tbody[0][0], $m_tr)){
                                $arr["attachments"] = array();
                                foreach($m_tr[0] as $j => $e_tr){
                                    $arr_attachment = array();
                                    if(preg_match("/class=\"outputText\" title=\"(.*?)\"/", $e_tr, $m)){
                                        $arr_attachment["filename"] = UnipaUtils::convertUnicodePointsToString(trim($m[1]));
                                    }
                                    if(preg_match("/labelFileSize\" class=\"outputText\">(.*?)<\/span>/", $e_tr, $m)){
                                        $arr_attachment["size"] = trim($m[1]);
                                    }
                                    $arr_attachment["extension"] = substr($arr_attachment["filename"], strrpos($arr_attachment["filename"], '.') + 1);
                                    $arr_attachment["fileid"] = $arr["id"]."_".$j.".".$arr_attachment["extension"];
                                    $attachment = $this->get(array(
                                        "form1:htmlFileTable:0:downLoadButton.x" => 9,
                                        "form1:htmlFileTable:0:downLoadButton.y" => 8,
                                        "form1:htmlParentFormId" => "",
                                        "form1:htmlDelMark" => "",
                                        "form1:htmlRowKeep" => "",
                                        "form1" => "form1",
                                    ), null, null, "/up/po/pPoa0202A.jsp");
                                    $arr["attachments"][] = $arr_attachment;
                                }
                            }
                        }
                    }
                }
                                
                // callback
                if(UnipaUtils::is_function($callback)){
                    $callback($arr);
                }
            }
        }
    }
    
    
    /**
     * Create Unique ID from information options.
     * This is should be used for saving to database table.
     * Hash algorithm: sha1
     * Hash algorithm strings: hashkey + section + title + form + data
     * 
     * @param  array  $options  Information options: any properties may not be empty.
     * @return string           Created unique ID; 40 chars
     */
    protected function getInformationUniqueId($options) {
        return sha1($this->informationHashTag.$options["section"].$options["title"].$options["from"].$options["data"]);
    }
}

?>