<?php


namespace Ghost\Geetest;


use Illuminate\Support\Facades\Facade;

/**
 * @method static register( string $string , array $data )
 * @method static successValidate( mixed $geetest_challenge , mixed $geetest_validate , mixed $geetest_seccode , array $data )
 * @method static failValidate( mixed $geetest_challenge , mixed $geetest_validate , mixed $geetest_seccode )
 * @method static render()
 */
class GeetestFacade extends Facade
{
	/**
	 * Get the binding in the IoC container
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'geetest';
	}
	
}