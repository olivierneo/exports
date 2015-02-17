<?php
/**
 * Created by PhpStorm.
 * User: olivier
 * Date: 13/02/2015
 * Time: 18:26
 */

namespace Bop\Exports;

use Log;
use Input;


class ExportsFactory {

    public static function fromSource($type)

    {
        $className = 'Bop\Exports\\Exports' . ucfirst($type) . 'Class';

        return new $className;

    }

}