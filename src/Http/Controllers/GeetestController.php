<?php

namespace Ghost\Geetest\Http\Controllers;

use Dcat\Admin\Http\Controllers\AuthController;
use Dcat\Admin\Layout\Content;
use Ghost\Geetest\GeetestCaptcha;
use Ghost\Geetest\GeetestServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeetestController extends AuthController
{
	use GeetestCaptcha;
	protected $view = 'ghost.geetest::login';

	
	public function postLogin(Request $request)
	{
		
		$credentials = $request->only([$this->username(), 'password','geetest_challenge']);
		$remember = (bool) $request->input('remember', false);
		

		/** @var \Illuminate\Validation\Validator $validator */
		$validator = Validator::make($credentials, [
			$this->username()   => 'required',
			'password'          => 'required',
			'geetest_challenge' => 'geetest'
		],[
			
			"geetest_challenge.geetest"=>GeetestServiceProvider::trans('geetest.server_fail_alert')
		]);

		if ($validator->fails()) {
			return $this->validationErrorsResponse($validator);
		}

		unset($credentials['geetest_challenge']);
		if ($this->guard()->attempt($credentials, $remember)) {
			return $this->sendLoginResponse($request);
		}

		return $this->validationErrorsResponse([
			$this->username() => $this->getFailedLoginMessage(),
		]);
	}
}