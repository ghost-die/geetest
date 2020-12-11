<?php

namespace Ghost\Geetest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


trait GeetestCaptcha
{
	/**
	 * Get geetest.
	 * @param Request $request
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
	 */
	public function register(Request $request)
	{
		$userId = Auth::user()? Auth::user()->id:'UnLoginUser';
		$digestmod = "md5";
		
		$params = [
			"digestmod" => $digestmod,
			'user_id' => $userId,
			"client_type" => "web",
			"ip_address" =>  $request->ip()
		];
		$result = GeetestFacade::register($digestmod, $params);
		
		$request->session()->put(Geetest::GEETEST_SERVER_STATUS_SESSION_KEY, $result->getStatus());
		$request->session()->put("userId", $userId);
		return response($result->getData())->header('Content-Type', "application/json;charset=UTF-8");

	}
}