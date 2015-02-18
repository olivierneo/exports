<?php

namespace Bop\Exports;

Use DBconfig;

class Winners extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'winners';

	protected $fillable = [];

	/**
	 * Exporte les gagnants et croise les données pour générer un export csv ou xls
	 *
	 * @param bool $export
	 * @return mixed
	 */
	public static function getAll($export = false, $take = 0, $skip = 0)
	{
		if ($export){

			if ($take == 0) {
				$d = self::select('id', 'user_id', 'voucher_id', 'gift_id', 'time')->get();
			} else {
				$d = self::select('id', 'user_id', 'voucher_id', 'gift_id', 'time')->skip($skip)->take($take)->get();
			}

			$d->each(function($d)
			{

				$user = Users::where('id', '=', $d->user_id)->first();

				$d->first_name = $user['first_name'];
				$d->last_name = $user['last_name'];
				$d->email = $user['email'];
				$d->gender = $user['gender'];

				if ($user['used_locale']){
					$d->used_locale = $user['used_locale'];
				} else {
					$d->used_locale = $user['browser_locale'];
				}
				$d->user_partner = $user['partner'];

				$voucher = Vouchers::where('id', '=', $d->voucher_id)->first();

				$d->voucher_code = $voucher['code'];

				$gift = DBconfig::get('bop.gifts');

				$d->gift_partner = $gift[$d->gift_id]['partner'];
				$d->gift_description = $gift[$d->gift_id]['public_description_single']['fr'];

				//$d->ip = $user['ip'];

				unset ($d->time);
				unset ($d->created_at);
				unset ($d->updated_at);
				unset ($d->sent_at);
				unset ($d->voucher_id);
				unset ($d->gift_id);
			});

			return $d->toArray();
		} else {
			return self::all();
		}

	}
}