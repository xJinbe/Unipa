<?php
namespace Unipa;

/**
 * Unipa - Universal Passport
 * Used and controled by Kinki University.
 * This system supposes be used under bost.jp, a flexible online service.
 * 
 * @category   service
 * @package    Unipa
 * @author     Jinbe <my@wauke.org>
 * @copyright  2014 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.1.0
 * @since      Class available since Release 1.0.0
 */

class Unipa {
    
    /* String: Define base url to access Unipa */
    private $baseURL = "https://waka-unipa.itp.kindai.ac.jp/up/faces";
    
    /* String: Define login path */
    private $loginPath = "/login/Com00505A.jsp";
    
    /* String: Common user-agent */
    private $commonUserAgent = "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.154 Safari/537.36";
    
    /* String: Define session id name */
    protected $sessionIdName = "JSESSIONID";
    
    /* String: Common referer */
    private $commonReferer = "";
    
    /* Bool:   Turn on debug */
    protected $_isDebug = false;
    
    /* Array:  Latest HTTP header: substituted when sent HTTP request */
    protected $latestHttpHeader = array();
    
    /* String: RequestHash */
    protected $latestRequestHash = "";
    
    /* String: Latest view ID of Unipa */
    protected $latestViewId = "";
    
    /* String: Latest view path of Unipa */
    protected $latestViewPath = "";
    
    /* String: Current request tag */
    protected $currentRequestTag = "";
    
    /* Array:  Define simple labels */
    protected $viewLabels = array(
        "home" => array(
            "header:form1:home" => "ホーム",
        ),
        "school_register" => array(
            "header:form1:htmlMenuItemButton" => "実行",
            "header:form1:hiddenMenuNo" => 101,
        ),
        "timetable_original" => array(
            "header:form1:htmlMenuItemButton" => "実行",
            "header:form1:hiddenMenuNo" => 201,
        ),
        "timetable_completable" => array(
            "header:form1:htmlMenuItemButton" => "実行",
            "header:form1:hiddenMenuNo" => 202,
        ),
        "syllabus" => array(
            "header:form1:htmlMenuItemButton" => "実行",
            "header:form1:hiddenMenuNo" => 301,
        ),
        "grades" => array(
            "header:form1:htmlMenuItemButton" => "実行",
            "header:form1:hiddenMenuNo" => 401,
        ),
        "timetable_registration" => array(
            "header:form1:htmlMenuItemButton" => "実行",
            "header:form1:hiddenMenuNo" => 5,
        ),
    );
    
    /* int: Login time stamp */
    public static $constructedTime = 0;
    
    /* String: User ID */
    public static $userId = "";
    
    /* String: Session ID */
    public static $sessionId = "";
    
    
    /** 
     * Constructor
     * 
     * @return void
     */
    public function __construct() {
        $this->commonReferer = $this->baseURL . $this->loginPath;
        $this->constructedTime = time();
        date_default_timezone_set("Asia/Tokyo");
        return;
    }
    
    
    /**
     * HTML Special characters
     * 
     * @param  string $str
     * @return string
     */
    function h($str) {
        return htmlspecialchars($str);
    }
    
    
    /**
     * Turn debug mode
     * 
     * @param  bool $flag
     * @return void
     */
    function debug($flag) {
        $this->_isDebug = $flag ? true : false;
        return;
    }
    
    
    /** 
     * Login to Unipa
     * 
     * @param  string $userid  User ID of Unipa.
     * @param  string $password  Password of Unipa.
     * @return bool
     * @throws \Exception Cannnot_get_source, Empty_view_id
     */
    public function login($userid, $password) {
        // Already logged in
        if($this->userId) return false;
        
        // Empty case
        if(!$userid || !$password) return false;
        
        // Access to get session ID
        $result = $this->postContents($this->loginPath);
        if(!$result){
            throw new \Exception("Cannnot_get_source");
            return;
        }
        
        // Get cookie params by HTTP header
        $cookies = $this->getCookieParams($this->latestHttpHeader);
        
        if(isset($cookies[$this->sessionIdName]) && $cookies[$this->sessionIdName]){
            $this->userId = $userid;
            $this->sessionId = $cookies[$this->sessionIdName];
            
            // Get front page
            $result = $this->postContents($this->loginPath, array(
                "form1:htmlUserId" => $userid,
                "form1:htmlPassword" => $password,
                "form1:login.x" => 0,
                "form1:login.y" => 0,
                "com.sun.faces.VIEW" => $this->latestViewId,
                "form1" => "form1",
            ), array($this->sessionIdName => $this->sessionId));
            
            // Check if error ocurred
            if(strpos($result, "ユーザＩＤまたはパスワードが正しくありません。") !== false){
                return false;
            }
            
            // Finally, set User-ID and Session-ID
            $this->userId = $userid;
            $this->sessionId = $cookies[$this->sessionIdName];
            $this->currentRequestTag = "logged_in";
            return true;
        }
        return false;
    }
    
    
    /** 
     * Logout from Unipa
     * Only clear user ID and session ID.
     * This function cannot destroy login status in Unipa.
     * 
     * @return bool
     */
    public function logout() {
        if(!$this->userId) return false;
        $this->userId = "";
        $this->sessionId = "";
        return true;
    }


    /**
     * Create post request with page id
     * 
     * @param  array  $options  Options include page ID, Unipa has original page idenditifier, so it's difficult to get any page simply.
     * @param  string $tag  Request tag; this tag should be used for series of request, for eaxample, Syllabus search.
     * @return string  Result of $this->postContents()
     */
    public function get($options, $tag = "", $viewId = "", $viewPath = "") {
        $params = array(
            "header:form1:hiddenMenuNo" => "",
            "header:form1:hiddenFuncRowId" => 0,
            "header:form1" => "header:form1",
        );
        
        // overrdide options for parameters
        foreach($options as $key => $val){
            $params[$key] = $val;
        }
        
        // update request hash
        $this->latestRequestHash = sha1(serialize($params));
        
        // update current tag
        $this->currentRequestTag = $tag;
        
        // get latest view ID
        $params["com.sun.faces.VIEW"] = $viewId ? $viewId : $this->latestViewId;
        
        // execute.
        return $this->postContents(
            $viewPath ? $viewPath : $this->latestViewPath,      // Path of unipa 
            $params,                                            // Post Parameters
            array($this->sessionIdName => $this->sessionId)     // Cookies
        );
    }
    
    
    /**
     * Calcurate request hash from options.
     * 
     * @param  array  $options  Request options same as $this->get()
     * @return atring           Sha1 hashed string
     */
    public function getRequestHash($options) {
        $params = array(
            "header:form1:hiddenMenuNo" => "",
            "header:form1:hiddenFuncRowId" => 0,
            "header:form1" => "header:form1",
        );
        foreach($options as $key => $val){
            $params[$key] = $val;
        }
        return sha1(serialize($params));
    }
    
    
    /**
     * Easing to access some page.
     * This function can convert the label name to get options.
     * 
     * @param  string $label  Label name
     * @return string   Result of $this->get()
     * @throws \Exception Undefined_label
     */
    public function label($label) {
        if(!isset($this->viewLabels[$label])){
            throw new \Exception("Undefined_label");
            return;
        }
        $this->currentRequestTag = $label;
        return $this->get($this->viewLabels[$label], $label);
    }
    
    
    /**
     * Convert HTTP headers to cookie parameters
     * 
     * @param  array $headers  Array of HTTP headers that can be got by $http_response_header
     * @return array  Array of cookie key-value groups
     */
    protected function getCookieParams($headers) {
        $cookies = array();
        foreach($headers as $key => $val){
            if(strpos($val, "Set-Cookie:") === 0){
                foreach(explode(";", substr($val, 11)) as $i => $elem){
                    $e = explode("=", $elem, 2);
                    $cookies[trim($e[0])] = trim($e[1]);
                }
            }
        }
        return $cookies;
    }
    
    
    /**
     * Convert cookie array to string
     * 
     * @param  array      $cookieGroup  Array of cookie group as key-value format
     * @return string                   Cookie string like HTTP header
     * @throws \Exception                createCookieString:Type_error
     */
    protected function createCookieString($cookieGroup) {
        if(!is_array($cookieGroup)){
            throw new \Exception("createCookieString:Type_error");
            return;
        }
        $container = array();
        foreach($cookieGroup as $key => $val){
            $container[] = "{$key}={$val}";
        }
        return join("; ", $container);
    }
     
    
    /**
     * Execute post request
     * 
     * @param  string     $path              The path of unipa after base url
     * @param  array      $params            HTTP Post parameters as key-value groups
     * @param  array      $cookies           Using cookie groups
     * @param  bool       $postDirect        If you need to use GET method, set true.
     * @param  bool       $isConvertCharset  if you need to get SJIS-converted response, set true.
     * @return string                        HTTP body. You can get HTTP headers to call $this->latestHttpHeader
     * @throws \Exception                     Empty_url, Under_maintenance 
     */
    protected function postContents($path = "", $params = array(), $cookies = array(), $postDirect = false, $isConvertCharset = false) {
        if(!$path){
            throw new \Exception("Empty_url");
            return;
        }
        
        // common options
        $options = array(
            "http" => array(
                "method" => $postDirect ? "GET" : "POST",
            )
        );
        
        // common header
        $headers = array(
            "Accept" => "*/*",
            "Accept-Encoding" => "gzip,deflate,sdch",
            "Accept-Language" => "en-US,en;q=0.8,ja;q=0.6",
            "Referer" => $this->commonReferer,
            "User-Agent" => $this->commonUserAgent,
            "Host" => "waka-unipa.itp.kindai.ac.jp",
            "Cookie" => is_array($cookies) ? $this->createCookieString($cookies) : $cookies,
            "Connection" => "keep-alive",
            //"Content-Length" => 213,
        );
        
        // GET or POST
        if($postDirect){
            $path .= $params ? "?".http_build_query($params) : "";
        }
        else{
            $options["http"]["content"] = $params ? http_build_query($params) : "";
            $headers["Origin"] = "https://waka-unipa.itp.kindai.ac.jp";
            $headers["Content-Type"] = "application/x-www-form-urlencoded";
            $headers["Cache-Control"] = "max-age=0";
        }
        
        // build header
        $options["http"]["header"] = $this->createHttpHeader($headers);
        
        // debug
        if($this->_isDebug){
            print "<pre>\n";
            print "Path: ".$path."\n";
            print "Request header: \n";
            print_r($options);
            print "Request contents: \n";
            print_r($params);
        }
        
        // Execute
        $context = stream_context_create($options);
        stream_context_set_option($context, "http", "ignore_errors", true);
        $result = @file_get_contents($this->baseURL.$path, false, $context);
        
        // Convert charset from sjis to utf-8
        if($isConvertCharset) $result = mb_convert_encoding($result, "utf-8", "sjis");
        
        // Save header
        $this->latestHttpHeader = $http_response_header;

        // debug
        if($this->_isDebug){
            print "Response header: \n";
            foreach(explode("\n", print_r($http_response_header, true)) as $i => $line){
                print "                {$line}\n";
            }
            print "\n\n\n\n\n\n\n\n\n</pre>";
        }

        // When under maintenance
        if($this->isUnderMaintenance($result)){
            throw new \Exception("Under_maintenance");
            return;
        }
            
        // View ID
        $viewId = $this->getViewId($result);
        if($viewId){
            $this->latestViewId = $viewId;
        }
        
        // View Path
        $viewPath = $this->getViewPath($result);
        if($viewPath){
            $this->latestViewPath = $viewPath;
        }
            
        return $result;
    }
    
    
    /**
     * Create HTTP Header from array
     * 
     * @param  array $headerArr  Groups of header key and value.
     * @return string
     */
    protected function createHttpHeader($headerArr) {
        if(!is_array($headerArr)) return false;
        $headerArrResult = array();
        foreach($headerArr as $key => $val){
            $headerArrResult[] = "{$key}: {$val}";
        }
        return join("\r\n", $headerArrResult);
    }
    
    
    /**
     * Check if under maintenance.
     * This function needs latest HTTP header to check status code.
     * 
     * @param  string @source  Source of unipa
     * @return bool
     */
    protected function isUnderMaintenance($source) {
        return strpos($this->latestHttpHeader[0], "404 Not Found") !== false && 
               strpos($source, "<title>Sorry!｜UNIPA</title>") !== false;
    }
    
    
    /**
     * Convert source to view ID
     * 
     * @param  string $source  HTML source of Unipa
     * @return string  View ID
     */
    protected function getViewId($source) {
        if(preg_match_all("/(?<=id=\"com\.sun\.faces\.VIEW\" value=\").*?(?=\")/s", $source, $m)){
            return $m[0][0];
        }
        return "";
    }
    
    
    /**
     * Convert source to view Path
     * 
     * @param  string $source  HTML source of Unipa
     * @return string  View Path replaced without base-path
     */
    protected function getViewPath($source) {
        if(preg_match_all("/(?<=<form id=\"header:form1\" method=\"post\" action=\").*?(?=\")/s", $source, $m)){
            return str_replace("/up/faces", "", $m[0][0]);
        }
        return "";
    }
}

?>