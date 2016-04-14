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
        $job->delete();

        if ($job->attempts() > 3)
        {
            Log::error('exports.partnerUsers.job.attempts.max', ['data' => $data, 'job_id' => $job->getJobId()]);
            $job->delete();
        } else {
            Log::debug('exports.partnerUsers.job.attempts.' . $job->attempts(), ['data' => $data, 'job_id' => $job->getJobId()]);
        }

        $columns = [
            'id' => 'id',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email' => 'email',
            'partner' => 'partner',
            'source' => 'source',
            'sponsor' => 'sponsor',
            'gender' => 'gender',
            'browser_locale' => 'browser_locale',
            'used_locale' => 'used_locale',
            'credentials_validated' => 'credentials_validated',
            'ip' => 'ip',
            'countries.postal_code' => 'postal_code',
            'countries.city' => 'city',
            'countries.region_name' => 'region_name',
            'countries.country_name' => 'country_name',
        ];

        if(! isset($data['skip'])) {$data['skip'] = 0; Log::debug('exports.job.start', ['data' => $data, 'job_id' => $job->getJobId()]);}
        if(! isset($data['fileName'])) {$data['fileName'] = $this->usersPartnerFileName . '_' . $data['partner'] . '_' . Carbon::now()->toDateString() . '_' . time() . '.csv';}

        try {
            $usersCounter = Users::where('partner', '=', $data['partner'])->count();
        } catch (Exception $e) {
            Log::error('exports.partnerUsers.counter', $e->getMessage());
        }

        try {
            $datasToStore = Users::getAllForAPartner($data['partner'], true, $this->take, $data['skip']);
        } catch (Exception $e) {
            Log::error('exports.partnerUsers.all', $e->getMessage());
        }

        if ($data['skip'] == 0) {
            array_unshift($datasToStore, $columns);
        }

        try {
            $store = ExportsStoreClass::store($this->path, $data['fileName'], $datasToStore);
        } catch (Exception $e) {
            Log::error('exports.partnerUsers.store', $e->getMessage());
        }

        $toStore = $usersCounter - $data['skip'];

        if ($toStore >= 0){
            Log::debug('exports.partnerUsers.job.chunk', ['data' => $data, 'job_id' => $job->getJobId()]);

            Queue::push('Bop\Exports\ExportsPartnerUsersClass', array('partner' => $data['partner'], 'fileName' => $data['fileName'], 'take' => $this->take, 'skip' => $data['skip'] + $this->take, 'email' => $data['email']));
        } else {
            $job->delete();

            Queue::push('Bop\Exports\ExportsPartnerUsersClass@ziptheFile', array('fileName' => $data['fileName'], 'path' => $this->path, 'email' => $data['email'], 'senderEmail' => $this->senderEmail, 'emailTitle' => $this->emailTitle));

            //Log::debug('exports.partnerUsers.job.end', ['data' => $data, 'job_id' => $job->getJobId()]);
        }

        $job->delete();
    }

    public function zipTheFile($job, $data){

        $file = storage_path($this->path . '/' . $data['fileName']);

        try {
            $zip = Zipper::make(storage_path($this->path . '/' . $data['fileName']) . '.zip')->add($file);
        } catch (Exception $e) {
            Log::error('exports.partnerUsers.job.zip', $e->getMessage());
        }

        Log::debug('exports.partnerUsers.job.zip.zipped', ['data' => $data, 'job_id' => $job->getJobId()]);

        $job->delete();

        Queue::push('Bop\Exports\ExportsEmailSenderClass', array('fileName' => $data['fileName'] . '.zip' , 'path' => $this->path, 'email' => $data['email'], 'senderEmail' => $this->senderEmail, 'emailTitle' => $this->emailTitle));

        Log::debug('exports.partnerUsers.job.end', ['data' => $data, 'job_id' => $job->getJobId()]);
    }

}
