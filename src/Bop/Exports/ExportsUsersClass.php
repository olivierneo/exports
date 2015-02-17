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



class ExportsUsersClass {

    public $exportPath;
    public $exportTake;
    public $senderEmail;
    public $emailTitle;
    public $usersFileName;

    function __construct(){
        ini_set('max_execution_time', Config::get('exports::maxExecutionTime'));
        ini_set('memory_limit',Config::get('exports::memoryLimit'));

        $this->path = Config::get('exports::path');
        $this->take = Config::get('exports::take') ;
        $this->senderEmail = Config::get('exports::senderEmail');
        $this->emailTitle = Config::get('exports::emailTitle');
        $this->usersFileName = Config::get('exports::usersFileName');
    }

    /**
     * Gère la demande effectuée par le gestionnaire de queue
     *
     * @param $job
     * @param $data
     */
    public function fire($job, $data)
    {
        if(! isset($data['skip'])) {$data['skip'] = 0; Log::debug('exports.users.job.start', $data);}
        if(! isset($data['fileName'])) {$data['fileName'] = $this->usersFileName . '_' . Carbon::now()->toDateString() . '_' . time() . '.csv';}

        try {
            $usersCounter = Users::all()->count();
        } catch (Exception $e) {
            Log::error('exports.users.counter', $e->getMessage());
        }

        try {
            $datasToStore = Users::getAll(true, $this->take, $data['skip']);
        } catch (Exception $e) {
            Log::error('exports.users.all', $e->getMessage());
        }

        try {
            $store = ExportsStoreClass::store($this->path, $data['fileName'], $datasToStore);
        } catch (Exception $e) {
            Log::error('exports.users.store', $e->getMessage());
        }

        $toStore = $usersCounter - $data['skip'];

        if ($toStore >= 0){
            Log::debug('exports.users.job.chunk', $data);

            Queue::push('Bop\Exports\ExportsUsersClass', array('fileName' => $data['fileName'], 'take' => $this->take, 'skip' => $data['skip'] + $this->take, 'email' => $data['email']));
        } else {
            $job->delete();

            Queue::push('Bop\Exports\ExportsEmailSenderClass', array('fileName' => $data['fileName'], 'path' => $this->path, 'email' => $data['email'], 'senderEmail' => $this->senderEmail, 'emailTitle' => $this->emailTitle));

            Log::debug('exports.users.job.end', $data);
        }

        if ($job->attempts() > 3)
        {
            Log::error('job', $data);
        }

        $job->delete();
    }

}