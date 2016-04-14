<?php

namespace Bop\Exports;

class Users extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	protected $fillable = [];

	/**
	 * Données de tous les utilisateurs
	 *
	 * @param bool $export Données destinées à êtres exportées ?
	 * @param int $take Combien d'enregistrements par passes
	 * @param int $skip Combien d'enregistrements "skippés"
	 * @return mixed
	 */
	public static function getAll($export = false, $take = 0, $skip = 0)
	{
		if ($export){
			//$d = self::model;

			if ($take == 0) {
				$u = self::join('countries', 'users.id', '=', 'countries.user_id')->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.partner', 'users.source', 'users.sponsor', 'users.gender', 'users.browser_locale', 'users.used_locale', 'users.credentials_validated', 'users.ip', 'countries.postal_code', 'countries.city', 'countries.region_name', 'countries.country_name')->get();
			} else {
				$u = self::join('countries', 'users.id', '=', 'countries.user_id')->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.partner', 'users.source', 'users.sponsor', 'users.gender', 'users.browser_locale', 'users.used_locale', 'users.credentials_validated', 'users.ip', 'countries.postal_code', 'countries.city', 'countries.region_name', 'countries.country_name')->skip($skip)->take($take)->get();
			}
			return $u->toArray();
		} else {
			return self::all();
		}

	}

	/**
	 * Données des utilisateurs pour un partner particulier
	 *
	 * @param $partner string slug du partenaire
	 * @param bool $export Données destinées à êtres exportées ?
	 * @param int $take Combien d'enregistrements par passes
	 * @param int $skip Combien d'enregistrements "skippés"
	 * @return mixed
	 */
	public static function getAllForAPartner($partner, $export = false, $take = 0, $skip = 0)
	{
		if ($export){
			$d = self::where('partner', $partner);

			if ($take == 0) {
				$u = $d->join('countries', 'users.id', '=', 'countries.user_id')->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.partner', 'users.source', 'users.sponsor', 'users.gender', 'users.browser_locale', 'users.used_locale', 'users.credentials_validated', 'users.ip', 'countries.postal_code', 'countries.city', 'countries.region_name', 'countries.country_name')->get();
			} else {
				$u = $d->join('countries', 'users.id', '=', 'countries.user_id')->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.partner', 'users.source', 'users.sponsor', 'users.gender', 'users.browser_locale', 'users.used_locale', 'users.credentials_validated', 'users.ip', 'countries.postal_code', 'countries.city', 'countries.region_name', 'countries.country_name')->skip($skip)->take($take)->get();
			}
			return $u->toArray();
		} else {
			return self::all();
		}

	}
}
