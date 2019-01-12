<?php


class LogEntry {

    public $address;
    public $requested_at;
    public $request;
    public $status;
    public $request_time;
    public $bytes_sent;
    public $referrer;
    public $user_agent;

    public function __construct($l)
    {
        $l = json_decode($l, true);
        $this->address = $l[0];
        $this->requested_at = $l[1];
        $this->request = $l[2];
        $this->status = $l[3];
        $this->request_time = $l[4];
        $this->bytes_sent = $l[5];
        $this->referrer = $l[6];
        $this->user_agent = $l[7];
    }

}