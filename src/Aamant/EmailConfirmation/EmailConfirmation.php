<?php namespace Aamant\EmailConfirmation;

use Carbon\Carbon;
use Illuminate\Auth\Reminders\RemindableInterface;

class EmailConfirmation
{
	protected $hashKey = null;

	protected $table = 'email_confirmation';

	const ERROR_MAILLER = 'error-mailler';
	const ERROR_DB = 'error-db';
	const ERROR_UNKNOWN = 'error-unknown';
	const SENDING_OK = "sending-ok";

	const CONFIRMED = 'confirmed';
	const EMAIL_NOT_FOUND = "email-not-found";
	const TOKEN_NOT_FOUND = "token-not-found";

	public function __construct($hashKey)
	{
		$this->hashKey = $hashKey;
	}

	public function send(RemindableInterface $user)
	{
		$token = $this->createToken($user);
		$email = $user->getReminderEmail();

		try {

			\DB::table($this->table)->insert(
				array('token' => $token, 'email' => $email, 'created_at' => new Carbon)
			);

			\Mail::send('email-confirmation::confirmation', compact('token', 'user'), function($m) use ($user) {
				$m->to($user->email, $user->firstname);
				$m->subject(\Lang::get('email-confirmation::email.title'));
			});

			return static::SENDING_OK;
		}
		catch (\Swift_SwiftException $e) {
			return static::ERROR_MAILLER;
		}
		catch (\PDOException $e) {
			return static::ERROR_DB;
		}
		catch (\Exception $e){
			return static::ERROR_UNKNOWN;
		}
	}

	public function check($token)
	{
		try {
			// Clean table
			\DB::table($this->table)
				->where('created_at', '<', Carbon::now()->subDays(8))
				->delete();

			// Get token row
			$row = \DB::table($this->table)
				->where('token', '=', $token)
				->first();

			if ($row) {

				$user = \User::where('email', '=', $row->email)->first();
				if(!$user){
					return static::EMAIL_NOT_FOUND;
				}
				$user->update(array('confirmed' => 1));

				\DB::table($this->table)->where('token', '=', $token)->delete();

				if (\Config::get('email-confirmation::autoconnect')){
					\Auth::login($user);
				}

				return static::CONFIRMED;
			}

			return static::TOKEN_NOT_FOUND;
		}
		catch (\PDOException $e) {
			return static::ERROR_DB;
		}
		catch (\Exception $e){
			return static::ERROR_UNKNOWN;
		}
	}

	public function createToken(RemindableInterface $user)
	{
		$email = $user->getReminderEmail();

		$value = str_shuffle(sha1($email.spl_object_hash($this).microtime(true)));

		return hash_hmac('sha1', $value, $this->hashKey);
	}
}