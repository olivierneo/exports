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



class ExportsWinnersClass {

    public $exportPath;
    public $exportTake;
    public $senderEmail;
    public $emailTitle;
    public $winnersFileName;

    function __construct(){
        ini_set('max_execution_time', Config::get('exports::maxExecutionTime'));
        ini_set('memory_limit',Config::get('exports::memoryLimit'));

        $this->path = Config::get('exports::path');
        $this->take = Config::get('exports::take') ;
        $this->senderEmail = Config::get('exports::senderEmail');
        $this->emailTitle = Config::get('exports::emailTitle');
        $this->winnersFileName = Config::get('exports::winnersFileName');
    }

    /**
     * Gère la demande effectuée par le gestionnaire de queue
     *
     * @param $job
     * @param $data
     */
    public function fire($job, $data)
    {
        if(! isset($data['skip'])) {$data['skip'] = 0; Log::debug('exports.winners.job.start', $data);}
        if(! isset($data['fileName'])) {$data['fileName'] = $this->winnersFileName . '_' . Carbon::now()->toDateString() . '_' . time() . '.csv';}

        try {
            $usersCounter = Winners::all()->count();
        } catch (Exception $e) {
            Log::error('exports.winners.counter', $e->getMessage());
        }

        try {
            $datasToStore = Winners::getAll(true, $this->take, $data['skip']);
        } catch (Exception $e) {
            Log::error('exports.winners.all', $e->getMessage());
        }

        try {
            $store = ExportsStoreClass::store($this->path, $data['fileName'], $datasToStore);
        } catch (Exception $e) {
            Log::error('exports.winners.store', $e->getMessage());
        }

        $toStore = $usersCounter - $data['skip'];

        if ($toStore >= 0){
            Log::debug('exports.winners.job.chunk', $data);

            Queue::push('Bop\Exports\ExportsWinnersClass', array('fileName' => $data['fileName'], 'take' => $this->take, 'skip' => $data['skip'] + $this->take, 'email' => $data['email']));
        } else {
            $job->delete();

            Queue::push('Bop\Exports\ExportsEmailSenderClass', array('fileName' => $data['fileName'], 'path' => $this->path, 'email' => $data['email'], 'senderEmail' => $this->senderEmail, 'emailTitle' => $this->emailTitle));

            Log::debug('exports.winners.job.end', $data);
        }

        if ($job->attempts() > 3)
        {
            Log::error('job', $data);
        }

        $job->delete();
    }

}