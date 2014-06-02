<?php namespace Aamant\EmailConfirmation\Controller;

class ConfirmationController extends \BaseController
{
	public function getSend()
	{
		$proccess = \App::make('email-confirmation');

		if (! \Auth::check()){
			return \Redirect::to('/')->with('errors', \Lang::get('email-confirmation::messages.auth'));
		}

		$response = $proccess->send(\Auth::user());

		switch ($response) {
			case $proccess::SENDING_OK:
				return \Redirect::to('/')->with('success', \Lang::get('email-confirmation::messages.success'));
			case $proccess::ERROR_DB:
			case $proccess::ERROR_MAILLER:
			case $proccess::ERROR:
			default:
				return \Redirect::to('/')->with('danger', \Lang::get('email-confirmation::messages.error'));
		}
	}

	public function getConfirmation($token)
	{
		$proccess = \App::make('email-confirmation');
		$response = $proccess->check($token);

		switch ($response) {
			case $proccess::CONFIRMED:
				return \Redirect::to(\Config::get('email-confirmation::redirect-after-confirm'))->with('success', \Lang::get('email-confirmation::messages.confirmed'));

			case $proccess::EMAIL_NOT_FOUND:
			case $proccess::TOKEN_NOT_FOUND:
				return \Redirect::to('/')->with('danger', \Lang::get('email-confirmation::messages.' . $response));

			case $proccess::ERROR_DB:
			case $proccess::ERROR:
			default:
				return \Redirect::to('/')->with('danger', \Lang::get('email-confirmation::messages.error'));
		}
	}
}