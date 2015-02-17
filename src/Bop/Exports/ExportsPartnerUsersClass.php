<?php
/**
 * Created by PhpStorm.
 * User: olivier
 * Date: 13/02/2015
 * Time: 18:26
 */

namespace Bop\Exports;

use Log;
use File;
use Input;
use Queue;
use Config;
use Carbon\Carbon;



class ExportsPartnerUsersClass {

    public $exportPath;
    public $exportTake;
    public $senderEmail;
    public $emailTitle;
    public $usersPartnerFileName;

    function __construct(){
        ini_set('max_execution_time', Config::get('exports::maxExecutionTime'));
        ini_set('memory_limit',Config::get('exports::memoryLimit'));

        $this->path = Config::get('exports::path');
        $this->take = Config::get('exports::take') ;
        $this->senderEmail = Config::get('exports::senderEmail');
        $this->emailTitle = Config::get('exports::emailTitle');
        $this->usersPartnerFileName = Config::get('exports::usersPartnerFileName');
    }

    /**
     * Gère la demande effectuée par le gestionnaire de queue
     *
     * @param $job
     * @param $data
     */
    public function fire($job, $data)
    {
        if(! isset($data['skip'])) {$data['skip'] = 0; Log::debug('exports.job.start', $data);}
        if(! isset($data['fileName'])) {$data['fileName'] = $this->usersPartnerFileName . '_' . $data['partner'] . '_' . Carbon::now()->toDateString() . '_' . time() . '.csv';}

        try {
            $usersCounter = Users::where('partner', '=', $data['partner'])->count();
        } catch (Exception $e) {
            Log::error('exports.partnersUsers.counter', $e->getMessage());
        }

        try {
            $datasToStore = Users::getAllForAPartner($data['partner'], true, $this->take, $data['skip']);
        } catch (Exception $e) {
            Log::error('exports.partnersUsers.all', $e->getMessage());
        }

        try {
            $store = ExportsStoreClass::store($this->path, $data['fileName'], $datasToStore);
        } catch (Exception $e) {
            Log::error('exports.partnersUsers.store', $e->getMessage());
        }

        $toStore = $usersCounter - $data['skip'];

        if ($toStore >= 0){
            Log::debug('exports.partnersUsers.job.chunk', $data);

            Queue::push('Bop\Exports\ExportsPartnerUsersClass', array('partner' => $data['partner'], 'fileName' => $data['fileName'], 'take' => $this->take, 'skip' => $data['skip'] + $this->take, 'email' => $data['email']));
        } else {
            $job->delete();

            Queue::push('Bop\Exports\ExportsEmailSenderClass', array('fileName' => $data['fileName'], 'path' => $this->path, 'email' => $data['email'], 'senderEmail' => $this->senderEmail, 'emailTitle' => $this->emailTitle));

            Log::debug('exports.partnersUsers.job.end', $data);
        }

        if ($job->attempts() > 3)
        {
            Log::error('job', $data);
        }

        $job->delete();
    }

}