<?
class SynoFileHostingUPTOBOX { 
	private $Url;
	private $Username;
	private $Password;
	private $HostInfo;
	private $UPTO_COOKIE_JAR = '/tmp/uptobox.cookie';
	private $LOGIN_URL = "http://uptobox.com/login.html";
	private $user_agent = 'Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1667.0 Safari/537.36';

	public function __construct($Url, $Username, $Password, $HostInfo) { 
		$this->Url = $Url; 
		$this->Username = $Username; 
		$this->Password = $Password; 
		$this->HostInfo = $HostInfo;
	}

	public function Verify($ClearCookie) {
		return $this->performLogin($ClearCookie);
	}

	public function GetDownloadInfo() {
		if($this->performLogin()==LOGIN_FAIL) {
			$DownloadInfo = array();
			return $DownloadInfo;
		}
		
		$ret = $this->getPremiumDownloadLink();
		return $ret;
	}

	private function performLogin($ClearCookie) {
		$ret = LOGIN_FAIL;
		//Save cookie file
		//op=login&redirect=http%3A%2F%2Fuptobox.com%2F&login=&password=&x=32&y=11
		$PostData = array('op'=>'login',
						'redirect'=>'http%3A%2F%2Fuptobox.com%2F',
						'login'=>$this->Username,
						'password'=>$this->Password,
						'x'=>'32',
						'y'=>'11'
		);
		$queryUrl = $this->LOGIN_URL;
		$PostData = http_build_query($PostData);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $PostData);
		curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->UPTO_COOKIE_JAR);
		curl_setopt($curl, CURLOPT_HEADER, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_URL, $queryUrl);
		$LoginInfo = curl_exec($curl);
		curl_close($curl);
		//xfss is uptobox logged in cookie value
		if (FALSE != $LoginInfo && file_exists($this->UPTO_COOKIE_JAR)) {
			$cookieData = file_get_contents ($this->UPTO_COOKIE_JAR);
			if(strpos($cookieData,'xfss') !== false) {
				$ret = USER_IS_PREMIUM;
			} else {
				$ret = LOGIN_FAIL;
			}
		}else{
			$ret = LOGIN_FAIL;	
		}
		if ($ClearCookie && file_exists($this->UPTO_COOKIE_JAR)) {
			unlink($this->UPTO_COOKIE_JAR);
		}
		return $ret;
	}
	private function getPremiumDownloadLink() {

		$ret = false;

		// STEP1
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($curl, CURLOPT_URL, $this->Url);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->UPTO_COOKIE_JAR);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->UPTO_COOKIE_JAR);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 

		curl_setopt($curl, CURLOPT_HEADER, true);
		//curl exec has to be called before getinfo
		$header = curl_exec($curl);
		preg_match('/<input type="hidden" name="rand" value="(.*)">/', $header, $rand); 
		
		curl_close($curl);
		
		//STEP 2
		$curl = curl_init();
		preg_match('/http:\/\/uptobox.com\/(.*)/',$this->Url,$id);
		$PostData = array(
				'op' => 'download2',
				'id' => $id[1],
				'rand' => $rand[1],
				'referer' => '',
				'method_free' => '',
				'method_premium' => '1',
				'down_direct' => '1'
                );
		$PostData = http_build_query($PostData);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $PostData);
		curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->UPTO_COOKIE_JAR);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->UPTO_COOKIE_JAR);
		curl_setopt($curl, CURLOPT_HEADER, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_URL, $this->Url);

                //curl exec has to be called before getinfo
        $header = curl_exec($curl);
        
		preg_match('/<a href="(.*)">Click here to start your download<\/a>/',$header,$download_url);
		
		curl_close($curl);
		// STEP3
        $DownloadInfo = array();
		$DownloadInfo[DOWNLOAD_URL] = trim($download_url[1]);
		return $DownloadInfo;


	} 

}
