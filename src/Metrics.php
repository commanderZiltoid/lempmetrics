<?php

namespace CommanderZiltoid\lempmetrics;

class Metrics {
    
    private $_access_log_path;
    private $_log_lines = [];
    private $_logs_to_read;
    private $_access_log = [];
    private $_callbacks = [];
    
    public function __construct($path='') {
        
        // If no path given, assume default nginx access.log path
        if($path === ''){
            $this->_access_log_path = '/var/log/nginx/';
        } else {
            $this->_access_log_path = $path;
        }
        
        // Will assume standard nginx log naming convention is followed
        // * Most current: access.log
        // * Previous: access.log.1
        // * Archived: access.log.{number}.gz
        
    }
    
    /***************************************************************************
    ****************************************************************************
    ************************** SERVER STATS METHODS ****************************
    ****************************************************************************
    ***************************************************************************/
    // These methods are useful when you are on a single dedicated server,
    // however, if your backend architecture consists of several load balanced
    // servers, functionality will need to be added to handle this (for example,
    // do this logic but once for each server in pool)
    
    
    /* Calculate CPU usage percentage since boot
    ***************************************************************************/
    public static function getCPUUsageSinceBoot(){
        
        $cpu = self::_parseProcStatFile();
        
        $total_time_since_boot = $cpu[0] + $cpu[1] + $cpu[2] + 
                $cpu[3] + $cpu[4] + $cpu[5] + $cpu[6] + $cpu[7];
        $total_idle_time_since_boot = $cpu[3] + $cpu[4];
        $total_usage_time_since_boot = $total_time_since_boot - $total_idle_time_since_boot;
        $total_usage_percentage_since_boot = ($total_usage_time_since_boot / $total_time_since_boot) * 100;

        return round($total_usage_percentage_since_boot, 2);
        
    }
    
    
    /* Calculate CPU usage percentage realtime
    ***************************************************************************/
    public static function getCPUUsageLive(){
        
        $last_idle = 0;
        $last_total = 0;
        $return_val = 0;
        
        for($i = 0; $i < 2; $i++){
            $cpu = self::_parseProcStatFile();
            $idle = $cpu[3];
            $total = array_sum($cpu);
            $idle_delta = $idle - $last_idle;
            $total_delta = $total - $last_total;
            $last_idle = $idle;
            $last_total = $total;
            $utilization = 100.0 * (1.0 - $idle_delta / $total_delta);
            if($i == 1){
                $return_val = $utilization;
                break;
            }
            sleep(1);
        }
        
        return round($return_val, 2);
        
    }
    
    /* Obtain current disk usages
    ***************************************************************************/
    public static function getDiskUsageLive(){
        
        $tmp = shell_exec('df -h');
        $tmp2 = explode("\n", $tmp);
        unset($tmp2[count($tmp2) - 1]); // remove empty element
        unset($tmp2[0]); // remove column names row
        $tmp2 = array_merge($tmp2);

        $return = [];
        foreach($tmp2 as $k => $v){
            $tmp3 = explode(" ", $v);
            foreach($tmp3 as $k2 => $v2){
                if($v2 == ''){
                    unset($tmp3[$k2]);
                }
            }
            $tmp3 = array_merge($tmp3);
            $return[] = [
                'fs'        => $tmp3[0],
                'size'      => $tmp3[1],
                'used'      => $tmp3[2],
                'available' => $tmp3[3],
                'use'       => $tmp3[4],
                'mounted'   => $tmp3[5]
            ];
        }
        
        return $return;
        
    }
    
    
    /* Obtain current free memory and memory being used
    ***************************************************************************/
    public static function getMemoryUsageLive(){
        
        $o = explode("\n", shell_exec('free -m'));
        $mem = explode(" ", $o[1]);
        foreach($mem as $k => $v){
            if($v == 'Mem:' || $v == ''){
                unset($mem[$k]);
            }
        }
        $mem = array_merge($mem);

        $buf = explode(" ", $o[2]);
        foreach($buf as $k => $v){
            if($v == '-/+' || $v == 'buffers/cache:' || $v == ''){
                unset($buf[$k]);
            }
        }
        $buf = array_merge($buf);

        $swap = explode(" ", $o[3]);
        foreach($swap as $k => $v){
            if($v == 'Swap:' || $v == ''){
                unset($swap[$k]);
            }
        }
        $swap = array_merge($swap);

        $return = [
            'mem' => [
                'total'     => $mem[0],
                'used'      => $mem[1],
                'free'      => $mem[2],
                'shared'    => $mem[3],
                'buffers'   => $mem[4],
                'cached'    => $mem[5]
            ],
            'buf'  => [
                'used'      => $buf[0],
                'free'      => $buf[1]
            ],
            'swap' => [
                'total'     => $swap[0],
                'used'      => $swap[1],
                'free'      => $swap[2]
            ]
        ];
     
        
        return $return;
        
    }
    
    
    /* Calculate CPU usage percentage since boot
     * $cpu[0] - user
     * $cpu[1] - nice
     * $cpu[2] - system
     * $cpu[3] - idle
     * $cpu[4] - iowait
     * $cpu[5] - irq
     * $cpu[6] - softirq
     * $cpu[7] - steal
    ***************************************************************************/
    private static function _parseProcStatFile(){
        
        return explode(" ", str_replace("cpu  ", "", 
                explode("\n", shell_exec('cat /proc/stat'))[0]));
        
    }
    
    
    
    /***************************************************************************
    ****************************************************************************
    *************************** ACCESS LOG METHODS *****************************
    ****************************************************************************
    ***************************************************************************/
    
    public function getAccessLogs(){
        
        $files = scandir($this->_access_log_path);
        
        foreach($files as $file){
            if(strpos($file, 'access.log') === false){
                $key = array_search($file, $files);
                unset($files[$key]);
            }
        }
        
        return $files;
        
    }
    
    /* Set which access.log file or files to read
    ***************************************************************************/
    public function setLogs($logs){
        
        $this->_logs_to_read = $logs;
        $this->_readLogs();
        
    }

    /* Obtain all requests containing the given substring
    ***************************************************************************/
    public function request($request){
        
        $this->_callbacks[] = function($r) use ($request){
            if(strpos($r->request, $request) !== false){
                return true;
            }
            return false;
        };
        
        return $this;
        
    }
    
    /* Obtain all requests containing the given status code
    ***************************************************************************/
    public function status($code){
        
        $this->_callbacks[] = function($r) use ($code){
            if($r->status == $code){
                return true;
            }
            return false;
        };
        
        return $this;
        
    }
    
    /* Obtain all requests made by the given ip address
    ***************************************************************************/
    public function address($address){
        
        $this->_callbacks[] = function($r) use ($address){
            if($r->address == $address){
                return true;
            }
            return false;
        };
        
        return $this;
        
    }
    
    /* Obtain all requests coming from a specific page...yeah I'm spelling 
     * referrer correctly...it's confusing but the other way just bothers me.
    ***************************************************************************/
    public function referrer($referrer){
        
        $this->_callbacks[] = function($r) use ($referrer){
            if(strpos($r->referrer, $referrer) !== false){
                return true;
            }
            return false;
        };
        
        return $this;
        
    }
    
    /* Obtain all requests with a user_agent string containing $agent
    ***************************************************************************/
    public function userAgent($agent){
        
        $this->_callbacks[] = function($r) use ($agent){
            if(strpos($r->user_agent, $agent) !== false){
                return true;
            }
            return false;
        };
        
        return $this;
        
    }
    
    /* Obtain all requests taking longer than $time to complete
    ***************************************************************************/
    public function requestTime($time){
        
        $this->_callbacks[] = function($r) use ($time){
            if((double)$r->request_time > $time){
                return true;
            }
            return false;
        };
        
        return $this;
        
    }
    
    /* Return the _access_log array
    --------------------------------------------------------------------------*/
    public function get(){
        $this->_build_access_log();
        return $this->_access_log;
    }
    
    /* Return number of elements in _access_log array
    --------------------------------------------------------------------------*/
    public function count(){
        $this->_build_access_log();
        return count($this->_access_log);
    }
    
    
    
    /* Read log file/s into memory
    --------------------------------------------------------------------------*/
    private function _readLogs(){
        
        foreach($this->_logs_to_read as $log){
            if(strpos($log, 'access.log') !== false){
                $file_contents = file_get_contents($this->_access_log_path . $log);
                if(strpos($log, '.gz') !== false){
                    $file_contents = gzdecode($file_contents);
                }
                $this->_log_lines = array_merge($this->_log_lines, explode("\n", $file_contents));
            }
        }
        
        return $this;
        
    }
    
    
    /* Insure _access_log is empty. Loop through each element in _log_lines and 
     * determine if line should be added to _access_log using array of callbacks
    --------------------------------------------------------------------------*/
    private function _build_access_log(){
        
        $this->_clear_access_log();
        
        foreach($this->_log_lines as $line){
            $r = new LogEntry($line);
            if(($r != '')){
                $tmp = [];
                foreach($this->_callbacks as $cb){
                    $tmp[] = $cb($r);
                }
                //echo var_export($tmp) . '<br/>';
                if(!in_array(false, $tmp, true)){
                    $this->_add_to_access_log_array($r);
                }
            }
        }
        
        $this->_sort_access_log();
        
    }
    
    /* Set _access_log equal to an empty array
    ***************************************************************************/
    private function _clear_access_log(){
        $this->_access_log = array();
    }
    
    private function _sort_access_log(){
        
        if(count($this->_logs_to_read) > 1){
            usort($this->_access_log, function($a, $b){
                $ats = strtotime($a->requested_at);
                $bts = strtotime($b->requested_at);
                if($ats > $bts){
                    return -1;
                } else if($ats < $bts){
                    return 1;
                } else if($ats == $bts){
                    return 0;
                }
            });
        } else {
            $this->_access_log = array_reverse($this->_access_log);
        }
        
    }
    
    private function _add_to_access_log_array($r){
        $r->requested_at = $this->_format_log_time($r->requested_at);
        $this->_access_log[] = $r;
    }
    
    private function _format_log_time($t){
        $e1 = explode('/', $t);
        $e2 = explode(':', $e1[2]);
        $year   = $e2[0];
        $month  = $e1[1];
        $day    = $e1[0];
        $hour   = $e2[1];
        $minute = $e2[2];
        $second = explode(' ', $e2[3])[0];
        
        return date('m/d/Y h:i:s a', strtotime("$month $day $year $hour:$minute:$second"));
    }
    


    
    
    
/* END OF CLASS
--------------------------------------------------------------------------*/
}