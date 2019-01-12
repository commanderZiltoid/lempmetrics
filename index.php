<?php require __DIR__ . '/vendor/autoload.php';

use CommanderZiltoid\lempmetrics\Metrics;
$metrics = new Metrics();


if(isset($_GET['access_logs_select']) && $_GET['access_logs_select'] != ''){
    
    $metrics->setLogs($_GET['access_logs_select']);
    
    if(isset($_GET['request_contains']) && $_GET['request_contains'] != ''){
        $metrics->request($_GET['request_contains']);
    }
    if(isset($_GET['referrer_contains']) && $_GET['referrer_contains'] != ''){
        $metrics->referrer($_GET['referrer_contains']);
    }
    if(isset($_GET['agent_contains']) && $_GET['agent_contains'] != ''){
        $metrics->userAgent($_GET['agent_contains']);
    }
    if(isset($_GET['response_time']) && $_GET['response_time'] != ''){
        $metrics->requestTime((double)$_GET['response_time']);
    }
    if(isset($_GET['status']) && $_GET['status'] != ''){
        $metrics->status($_GET['status']);
    }
    if(isset($_GET['address']) && $_GET['address'] != ''){
        $metrics->address($_GET['address']);
    }
    
    $results = $metrics->get();

    
    //echo '<pre>';
    //echo var_export($metrics->read()->all()->get(), true);
    //echo var_export($metrics->read()->status(500)->get(), true);
    //echo var_export($metrics->read()->address('192.168.56.1')->get(), true);
    //echo var_export($metrics->read()->referrer('agrimissouri.com')->get(), true);
    //echo var_export($metrics->read()->userAgent('Trident')->get(), true);
    //echo var_export($metrics->read()->userAgent('Trident')->count(), true);
    //echo var_export($metrics->read()->requestTime(1)->get(), true);
    //echo '</pre>';
    //die;
    
    
}

?>


<!DOCTYPE html>
<html>
    <head>
        <title>LEMPMetrics</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.js"></script>
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.css" />
        
        <style>
            .dont-break-out { /*https://css-tricks.com/snippets/css/prevent-long-urls-from-breaking-out-of-container/*/

            /* These are technically the same, but use both */
            overflow-wrap: break-word;
            word-wrap: break-word;

            -ms-word-break: break-all;
            /* This is the dangerous one in WebKit, as it breaks things wherever */
            word-break: break-all;
            /* Instead use this non-standard one: */
            word-break: break-word;

            /* Adds a hyphen where the word breaks, if supported (No Blink) */
            -ms-hyphens: auto;
            -moz-hyphens: auto;
            -webkit-hyphens: auto;
            hyphens: auto;

          }
        </style>
        
        <script>
            $(document).ready(function(){
                
                var $cpu_usage = $('#cpu_usage');
                var $mem_usage = $('#mem_usage');
                var $disk_usage = $('#disk_usage');
                
                setInterval(function(){
                    $.ajax({
                        url: '/async.php?livecpu=1&livememory=1&livedisk=1',
                        dataType: 'JSON',
                        type: 'GET',
                        success: function(data){

                            $cpu_usage.html(data.livecpu + '%');
                            $mem_usage.html(data.livememory.buf.free);
                            $disk_usage.html(data.livedisk[0].available);

                        }
                    });
                }, 2000);

            });
        </script>
        
    </head>
    <body>
        <div class="container">
            
            
            <div class="row">
                <div class="col-md-4 col-md-offset-4">
                    
                    <div class="well">
                        
                        <div class="alert alert-info">
                            <table class="table">
                                <tr>
                                    <td>
                                        <strong>CPU</strong>
                                    </td>
                                    <td>
                                        <span id="cpu_usage"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Available Memory</strong>
                                    </td>
                                    <td>
                                        <span id="mem_usage"></span>MB
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Available Disk</strong>
                                    </td>
                                    <td>
                                        <span id="disk_usage"></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <form action="" method="get">
                            <div class="form-group">
                                <label for="access_logs_select" class="control-label">Access Log/s</label>
                                <select id="access_logs_select" name="access_logs_select[]" multiple="multiple" class="form-control">
                                    <?php foreach($metrics->getAccessLogs() as $log): ?>
                                    <option <?php echo (isset($_GET['access_logs_select']) && 
                                            is_array($_GET['access_logs_select'])) && 
                                            in_array($log, $_GET['access_logs_select']) ? 'selected' : '' ?>>
                                        <?php echo $log; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="request_contains" class="control-label">Request Contains</label>
                                <input id="request_contains" name="request_contains" class="form-control" 
                                       value="<?php echo isset($_GET['request_contains']) ? $_GET['request_contains'] : ''; ?>"/>

                            </div>
                            <div class="form-group">
                                <label for="referrer_contains" class="control-label">Referrer Contains</label>
                                <input id="referrer_contains" name="referrer_contains" class="form-control" 
                                       value="<?php echo isset($_GET['referrer_contains']) ? $_GET['referrer_contains'] : ''; ?>"/>

                            </div>
                            <div class="form-group">
                                <label for="agent_contains" class="control-label">Agent Contains</label>
                                <input id="agent_contains" name="agent_contains" class="form-control" 
                                       value="<?php echo isset($_GET['agent_contains']) ? $_GET['agent_contains'] : ''; ?>"/>

                            </div>
                            <div class="form-group">
                                <label for="response_time" class="control-label">Response Time Longer Than (seconds)</label>
                                <input id="response_time" name="response_time" class="form-control" 
                                       value="<?php echo isset($_GET['response_time']) ? $_GET['response_time'] : ''; ?>"/>

                            </div>
                            <div class="form-group">
                                <label for="status" class="control-label">HTTP Status Code</label>
                                <input id="status" name="status" class="form-control" 
                                       value="<?php echo isset($_GET['status']) ? $_GET['status'] : ''; ?>"/>

                            </div>
                            <div class="form-group">
                                <label for="address" class="control-label">IP</label>
                                <input id="address" name="address" class="form-control" 
                                       value="<?php echo isset($_GET['address']) ? $_GET['address'] : ''; ?>"/>

                            </div>
                            <button type="submit" class="btn btn-primary btn-group-justified">Search</button>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <br/>
        <br/>
             
        <?php if(isset($results)): ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-xs-12 text-center">
                    <div class="well">
                        <strong>Results:<?php echo count($results); ?></strong>
                        <br/>
                        <br/>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="text-center">IP</th>
                                        <th class="text-center">Time</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">R.Time</th>
                                        <th class="text-center">Bytes Sent</th>
                                        <th class="text-center">Request</th>
                                        <th class="text-center">Referrer</th>
                                        <th class="text-center">Agent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($results as $r): ?>
                                    <tr>
                                        <td width="12%"><?php echo $r->address; ?></td>
                                        <td width="12%"><?php echo $r->requested_at; ?></td>
                                        <td width="6%"><?php echo $r->status; ?></td>
                                        <td width="6%"><?php echo $r->request_time; ?></td>
                                        <td width="6%"><?php echo $r->bytes_sent; ?></td>
                                        <td class="dont-break-out" width="23%">
                                            <?php echo $r->request;?>
                                        </td>
                                        <td class="dont-break-out" width="23%">
                                            <?php echo $r->referrer; ?>
                                        </td>
                                        <td width="12%"><?php echo $r->user_agent; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

            
            
        
    </body>
</html>