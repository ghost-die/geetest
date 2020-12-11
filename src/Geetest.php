<?php


namespace Ghost\Geetest;


class Geetest
{

	public const API_URL = "http://api.geetest.com";
	public const REGISTER_URL = "/register.php";
	public const VALIDATE_URL = "/validate.php";
	public const JSON_FORMAT = "1";
	public const NEW_CAPTCHA = true;
	public const HTTP_TIMEOUT_DEFAULT = 5; // 单位：秒
	public const VERSION = "php-laravel:3.1.0";
	public const GEETEST_CHALLENGE = "geetest_challenge"; // 极验二次验证表单传参字段 chllenge
	public const GEETEST_VALIDATE = "geetest_validate"; // 极验二次验证表单传参字段 validate
	public const GEETEST_SECCODE = "geetest_seccode"; // 极验二次验证表单传参字段 seccode
	public const GEETEST_SERVER_STATUS_SESSION_KEY = "gt_server_status"; // 极验验证API服务状态Session Key
	
	/**
	 * 成功失败的标识码，1表示成功，0表示失败
	 */
	private $status = 0;
	
	/**
	 * 返回数据，json格式
	 */
	private $data = "";
	
	/**
	 * 备注信息，如异常信息等
	 */
	private $msg = "";
	private $url = 'register';
	
	public function __construct()
	{
		
		$this->geetest_id = GeetestServiceProvider::setting('geetest_id');
		$this->geetest_key = GeetestServiceProvider::setting('geetest_key');
		
	}
	
	
	/**
	 * 验证初始化
	 */
	public function register($digestmod, $params)
	{
		$origin_challenge = $this->requestRegister($params);
		$this->buildRegisterResult($origin_challenge, $digestmod);
		return $this;
	}
	
	/**
	 * 向极验发送验证初始化的请求，GET方式
	 */
	private function requestRegister($params)
	{
		$params = array_merge($params, ["gt" => $this->geetest_id, "sdk" => self::VERSION, "json_format" => self::JSON_FORMAT]);
		$register_url = self::API_URL . self::REGISTER_URL;

		$origin_challenge = null;
		try {
			$resBody = $this->httpGet($register_url, $params);
			$res_array = json_decode($resBody, true);
			$origin_challenge = $res_array["challenge"];
		} catch (\Throwable $t) {
			$origin_challenge = "";
		}
		return $origin_challenge;
	}
	
	/**
	 * 构建验证初始化返回数据
	 * @throws \JsonException
	 */
	private function buildRegisterResult($origin_challenge, $digestmod)
	{
		// origin_challenge为空或者值为0代表失败
		if (empty($origin_challenge)) {
			// 本地随机生成32位字符串
			$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
			$challenge = '';
			for ($i = 0; $i < 32; $i++) {
				$challenge .= $characters[random_int(0, strlen($characters) - 1)];
			}
			$this->setAll(
				0,
				json_encode( [
					"success" => 0 ,
					"gt" => $this->geetest_id ,
					"challenge" => $challenge ,
					"new_captcha" => self::NEW_CAPTCHA
				] , JSON_THROW_ON_ERROR ) ,
				"请求极验register接口失败，后续流程走宕机模式"
			);
		} else {
			$challenge = null;
			if ($digestmod === "md5") {
				$challenge = $this->md5_encode($origin_challenge . $this->geetest_key);
			} elseif ($digestmod === "sha256") {
				$challenge = $this->sha256_encode($origin_challenge . $this->geetest_key);
			} elseif ($digestmod === "hmac-sha256") {
				$challenge = $this->hmac_sha256_encode($origin_challenge, $this->geetest_key);
			} else {
				$challenge = $this->md5_encode($origin_challenge . $this->geetest_key);
			}
			$this->setAll(
				1,
				json_encode( [
					"success" => 1 ,
					"gt" => $this->geetest_id ,
					"challenge" => $challenge ,
					"new_captcha" => self::NEW_CAPTCHA
				] , JSON_THROW_ON_ERROR ) ,
				""
			);
		}
	}
	
	/**
	 * 正常流程下（即验证初始化成功），二次验证
	 */
	public function successValidate($challenge, $validate, $seccode, $params)
	{
		if (!$this->checkParam($challenge, $validate, $seccode)) {
			$this->setAll(0, "", "正常模式，本地校验，参数challenge、validate、seccode不可为空");
		} else {
			$response_seccode = $this->requestValidate($challenge, $validate, $seccode, $params);
			if (empty($response_seccode)) {
				$this->setAll(0, "", "请求极验validate接口失败");
			} elseif ($response_seccode === "false") {
				$this->setAll(0, "", "极验二次验证不通过");
			} else {
				$this->setAll(1, "", "");
			}
		}
		return $this;
	}
	
	/**
	 * 异常流程下（即验证初始化失败，宕机模式），二次验证
	 * 注意：由于是宕机模式，初衷是保证验证业务不会中断正常业务，所以此处只作简单的参数校验，可自行设计逻辑。
	 */
	public function failValidate($challenge, $validate, $seccode)
	{
		
		if (!$this->checkParam($challenge, $validate, $seccode)) {
			$this->setAll(0, "", "宕机模式，本地校验，参数challenge、validate、seccode不可为空.");
		} else {
			$this->setAll(1, "", "");
		}
		return $this;
	}
	
	/**
	 * 向极验发送二次验证的请求，POST方式
	 * @param $challenge
	 * @param $validate
	 * @param $seccode
	 * @param $params
	 * @return string
	 */
	private function requestValidate($challenge, $validate, $seccode, $params) : string
	{
		$params = array_merge(
			$params,
			[
				"seccode" => $seccode,
				"json_format" => self::JSON_FORMAT,
				"challenge" => $challenge,
				"sdk" => self::VERSION,
				"captchaid" => $this->geetest_id
			]
		);
		$validate_url = self::API_URL . self::VALIDATE_URL;
		
		
		$response_seccode = null;
		try {
			$resBody = $this->httpPost($validate_url, $params);
			$res_array = json_decode( $resBody , true , 512 , JSON_THROW_ON_ERROR );
			$response_seccode = $res_array["seccode"];
		} catch (\Throwable $t) {
			$response_seccode = "";
		}
		return $response_seccode;
	}
	
	/**
	 * 校验二次验证的三个参数，校验通过返回true，校验失败返回false
	 * @param $challenge
	 * @param $validate
	 * @param $seccode
	 * @return bool
	 */
	private function checkParam($challenge, $validate, $seccode) : bool
	{
		return !(empty($challenge) || ctype_space($challenge) || empty($validate) || ctype_space(
				$validate
			) || empty($seccode) || ctype_space($seccode));
	}
	
	/**
	 * 发送GET请求，获取服务器返回结果
	 * @param $url
	 * @param $params
	 * @return bool|string
	 */
	private function httpGet($url, $params)
	{
		$url .= "?" . http_build_query($params);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::HTTP_TIMEOUT_DEFAULT); // 设置连接主机超时（单位：秒）
		curl_setopt($ch, CURLOPT_TIMEOUT, self::HTTP_TIMEOUT_DEFAULT); // 允许 cURL 函数执行的最长秒数（单位：秒）
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$res = curl_exec($ch);
		curl_close($ch);
		return $res;
	}
	
	/**
	 * 发送POST请求，获取服务器返回结果
	 * @param $url
	 * @param $param
	 * @return bool|string
	 */
	private function httpPost($url, $param)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::HTTP_TIMEOUT_DEFAULT); // 设置连接主机超时（单位：秒）
		curl_setopt($ch, CURLOPT_TIMEOUT, self::HTTP_TIMEOUT_DEFAULT); // 允许 cURL 函数执行的最长秒数（单位：秒）
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type:application/x-www-form-urlencoded"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$res = curl_exec($ch);
		curl_close($ch);
		return $res;
	}
	
	/**
	 * md5 加密
	 * @param $value
	 * @return string
	 */
	private function md5_encode($value) : string
	{
		return hash("md5", $value);
	}
	
	/**
	 * sha256加密
	 * @param $value
	 * @return string
	 */
	public function sha256_encode($value) : string
	{
		return hash("sha256", $value);
	}
	
	/**
	 * hmac-sha256 加密
	 * @param $value
	 * @param $key
	 * @return string
	 */
	private function hmac_sha256_encode($value, $key) : string
	{
		return hash_hmac('sha256', $value, $key);
	}
	
	
	
	public function getStatus() : int
	{
		return $this->status;
	}
	
	public function setStatus($status) : void
	{
		$this->status = $status;
	}
	
	public function getData() : string
	{
		return $this->data;
	}
	
	public function setData($data) : void
	{
		$this->data = $data;
	}
	
	public function getMsg() : string
	{
		return $this->msg;
	}
	
	public function setMsg($msg) : void
	{
		$this->msg = $msg;
	}
	
	public function setAll($status, $data, $msg)
	{
		$this->setStatus($status);
		$this->setData($data);
		$this->setMsg($msg);
	}
	
	public function __toString() : string
	{
		return sprintf("Geetest{status=%s, data=%s, msg=%s}", $this->status, $this->data, $this->msg);
	}
	
	
	/**
	 * @param string $product
	 * @param string $captchaId
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
	 */
	public function render($product = 'float', $captchaId = 'captcha_id')
	{
		return view('ghost.geetest::geetest', [
			'captcha_id' => $captchaId,
			'product' => $product,
			'url' => $this->url
		]);
	}
}