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
				$u = self::select('id', 'first_name', 'last_name', 'email', 'partner', 'source', 'sponsor', 'gender', 'browser_locale', 'used_locale', 'credentials_validated', 'ip')->get();
			} else {
				$u = self::select('id', 'first_name', 'last_name', 'email', 'partner', 'source', 'sponsor', 'gender', 'browser_locale', 'used_locale', 'credentials_validated', 'ip')->skip($skip)->take($take)->get();
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
				$u = $d->select('id', 'first_name', 'last_name', 'email', 'partner', 'source', 'sponsor', 'gender', 'browser_locale', 'used_locale', 'credentials_validated', 'ip')->get();
			} else {
				$u = $d->select('id', 'first_name', 'last_name', 'email', 'partner', 'source', 'sponsor', 'gender', 'browser_locale', 'used_locale', 'credentials_validated', 'ip')->skip($skip)->take($take)->get();
			}
			return $u->toArray();
		} else {
			return self::all();
		}

	}
}