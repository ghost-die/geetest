<?php

namespace Ghost\Geetest;

use Dcat\Admin\Extend\Setting as Form;

class Setting extends Form
{
	
	public function title()
	{
		return $this->trans('geetest.title');
	}
	
    public function form()
    {
        $this->text('geetest_id','Geetest ID')->required();
        $this->text('geetest_key','Geetest Key')->required();
    }
}
