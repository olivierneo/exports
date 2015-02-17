<?php
/**
 * Created by PhpStorm.
 * User: olivier
 * Date: 13/02/2015
 * Time: 18:30
 */

namespace Bop\Exports\Facades;

use Illuminate\Support\Facades\Facade;

class Exports extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'exports'; }

}