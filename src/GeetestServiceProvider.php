<?php

namespace Ghost\Geetest;

use Dcat\Admin\Extend\ServiceProvider;
use Dcat\Admin\Admin;
use Ghost\Geetest\Http\Controllers\GeetestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

class GeetestServiceProvider extends ServiceProvider
{
	
	
	protected $exceptRoutes = [
		'auth'=>[
			'auth/register'
		]
	];
	public function register()
	{
		$this->app->singleton('geetest', function () {
			return $this->app->make(Geetest::class);
		});
	}

	public function init()
	{
		
		parent::init();
		
		
		Validator::extend('geetest', function () {
			[$geetest_challenge, $geetest_validate, $geetest_seccode] = array_values(\request()->only(
				Geetest::GEETEST_CHALLENGE, Geetest::GEETEST_VALIDATE, Geetest::GEETEST_SECCODE));
			$data = [
				'user_id' => Auth::user()? Auth::user()->id:'UnLoginUser',
				'client_type' => 'web',
				'ip_address' => \request()->ip()
			];
			$status = session()->get(Geetest::GEETEST_SERVER_STATUS_SESSION_KEY, null);
			
			if ($status === 1) {
				
				$result = GeetestFacade::successValidate($geetest_challenge, $geetest_validate, $geetest_seccode, $data);
				
			} else {
				$result = GeetestFacade::failValidate($geetest_challenge, $geetest_validate, $geetest_seccode);
			}
			
			return $result->getStatus() === 1;
		});
	
	}

	public function settingForm() : Setting
	{
		return new Setting($this);
	}
}
