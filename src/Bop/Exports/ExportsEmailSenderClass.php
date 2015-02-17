<?php
/**
 * Created by PhpStorm.
 * User: olivier
 * Date: 17/02/2015
 * Time: 09:51
 */

namespace Bop\Exports;

use Mail;
use Log;
use File;


class ExportsEmailSenderClass {

    private $datas;

    /**
     * @param $job
     * @param $data
     */
    public function fire($job, $data){

        $this->datas['emailTitle'] = $data['emailTitle'] . ' ' . $data['fileName'];
        $this->datas['email'] = $data['email'];
        $this->datas['senderEmail'] = $data['senderEmail'];
        $this->datas['path'] = $data['path'];
        $this->datas['fileName'] = $data['fileName'];

            Mail::send('exports::emails.exports', ['file' => storage_path($this->datas['path'] . '/' . $this->datas['fileName'])], function ($message) {
                $message->to($this->datas['email'], 'Contact')->subject($this->datas['emailTitle']);
                $message->from($this->datas['senderEmail'], 'Contact');
                $message->attach(storage_path($this->datas['path'] . '/' . $this->datas['fileName']));
            });

            Log::debug('exports.email.send', ['title' => $this->datas['emailTitle'], 'email' => $this->datas['email']]);


        $job->delete();

    }

}