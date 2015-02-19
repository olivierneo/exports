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
use Zipper;



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
        $job->delete();

        if ($job->attempts() > 3)
        {
            Log::error('exports.winners.job.attempts.max', ['data' => $data, 'job_id' => $job->getJobId()]);
            $job->delete();
        } else {
            Log::debug('exports.winners.job.attempts.' . $job->attempts(), ['data' => $data, 'job_id' => $job->getJobId()]);
        }

        $columns = [
            'id' => 'id',
            'user_id' => 'user_id',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email' => 'email',
            'gender' => 'gender',
            'used_locale' => 'used_locale',
            'partner' => 'partner',
            'voucher_id' => 'voucher_id',
            'gift_partner' => 'gift_partner',
            'gift_name' => 'gift_name',
            //'ip' => 'ip'
        ];

        if(! isset($data['skip'])) {$data['skip'] = 0; Log::debug('exports.winners.job.start', ['data' => $data, 'job_id' => $job->getJobId()]);}
        if(! isset($data['fileName'])) {$data['fileName'] = $this->winnersFileName . '_' . Carbon::now()->toDateString() . '_' . time() . '.csv';}

        try {
            $usersCounter = intval(Winners::orderby('created_at', 'desc')->select('id')->first()->id);
        } catch (Exception $e) {
            Log::error('exports.winners.counter', $e->getMessage());
        }

        try {
            $datasToStore = Winners::getAll(true, $this->take, $data['skip']);
        } catch (Exception $e) {
            Log::error('exports.winners.all', $e->getMessage());
        }

        if ($data['skip'] == 0) {
            array_unshift($datasToStore, $columns);
        }

        try {
            $store = ExportsStoreClass::store($this->path, $data['fileName'], $datasToStore);
        } catch (Exception $e) {
            Log::error('exports.winners.store', $e->getMessage());
        }

        $toStore = $usersCounter - $data['skip'];

        if ($toStore >= 0){
            Log::debug('exports.winners.job.chunk', ['data' => $data, 'job_id' => $job->getJobId()]);

            Queue::push('Bop\Exports\ExportsWinnersClass', array('fileName' => $data['fileName'], 'take' => $this->take, 'skip' => $data['skip'] + $this->take, 'email' => $data['email']));
        } else {
            $job->delete();

            Queue::push('Bop\Exports\ExportsWinnersClass@ziptheFile', array('fileName' => $data['fileName'], 'path' => $this->path, 'email' => $data['email'], 'senderEmail' => $this->senderEmail, 'emailTitle' => $this->emailTitle));

            //Log::debug('exports.partnerUsers.job.end', ['data' => $data, 'job_id' => $job->getJobId()]);
        }

        $job->delete();
    }

    public function zipTheFile($job, $data){

        $file = storage_path($this->path . '/' . $data['fileName']);

        try {
            $zip = Zipper::make(storage_path($this->path . '/' . $data['fileName']) . '.zip')->add($file);
        } catch (Exception $e) {
            Log::error('exports.winners.job.zip', $e->getMessage());
        }

        Log::debug('exports.winners.job.zip.zipped', ['data' => $data, 'job_id' => $job->getJobId()]);

        $job->delete();

        Queue::push('Bop\Exports\ExportsEmailSenderClass', array('fileName' => $data['fileName'] . '.zip' , 'path' => $this->path, 'email' => $data['email'], 'senderEmail' => $this->senderEmail, 'emailTitle' => $this->emailTitle));

        Log::debug('exports.winners.job.end', ['data' => $data, 'job_id' => $job->getJobId()]);
    }
}