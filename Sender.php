<?php
/*

Copyright (c) 2011 Clearspring

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/
/* Require all of the other files, so this is the only one that needs to be included*/
require_once('Metric.php');
require_once('Counter.php');
require_once('Gauge.php');
require_once('Histogram.php');
require_once('Meter.php');
require_once('Timer.php');


/**
 * PHP class for sending stats to Metric
 */
class Phetric_Sender
{
    /**
     * @var Holds the instance of Phetric
     */
    private static $_instance;

    /**
     * The port and host we are going to send data to
     */
    private $_port = null;
    private $_host = null;

    /**
     * @var prepends to all metrics
     */
    private $_prepend = '';

    /**
     * @var autoflush all metrics to send immediately, instead of when the session ends
     */
    private $_autoflush = false;

    /**
     * @var the data we intend to send
     */
    private $_data = array( );



    /**
     * Define, identify and register the sending of our metrics.
     *
     * @param string $host The host where you will be sending metrics.
     * @param string $port The port to send our metrics to.
    */

    function __construct($host, $port, $prepend, $autoflush )
    {
            $this->_host = $host;
            $this->_port = $port;
            $this->_prepend = $prepend;
            $this->_autoflush = $autoflush;

            // Send our data on shutdown
            register_shutdown_function( array($this, 'sendMetrics') ); 

    }

    /**
     * Initialize an instance of Phetric
     *
     * @param string $host The host where you will be sending metrics.
     * @param string $port The port to send our metrics to.
     * @param string $prepend Optionally prepend something (like a server name) to all metrics
     */
    public static function init($host, $port, $prepend = '', $autoflush=false)
    {
        if (!isset(self::$_instance))
        {
            self::$_instance = new self($host, $port, $prepend, $autoflush);
        }
        else
        {
            self::$_instance->host = $host;
            self::$_instance->port = $port;
            self::$_instance->prepend = $prepend;
            self::$_instance->autoflush = $autoflush;
        }
        return self::$_instance;
    }


    public static function maybeCreatePhetric()
    {
        if (!isset(self::$_instance))
            self::$_instance = new self(null, null, '',false);
        
        return self::$_instance;
    }

    /**
     * Add a Gauge
     *
     * Gauges are instantaneous readings of values (e.g., a queue depth).
     *
     * @param string $name The Name you want to assign to your Metric
     * @param int $value The Value of the gauge
    */
    public static function gauge( $name, $value )
    {
        self::maybeCreatePhetric();
        self::$_instance->_addMetric( $name, $value, 'gauge' );
    }

    /**
     * Add a Counter value.
     *
     * Counters are 64-bit integers which can be incremented or decremented.
     *
     * @param string $name The Name you want to assign to your Metric
     * @param int $value The amount you want to increment or decrement.  Defaults to 1
    */
    public static function counter( $name, $value = 1 )
    {
        self::maybeCreatePhetric();
        self::$_instance->_addMetric( $name, $value, 'counter' );
    }

    /**
     * Add a Meter value.
     *
     * Meters are increment-only counters which keep track of the rate of
     * events. They provide mean rates, plus exponentially-weighted moving
     * averages which use the same formula that the UNIX 1-, 5-,
     * and 15-minute load averages use.
     *
     * @param string $name The Name you want to assign to your Metric
     * @param int $value The amount you want to increment or decrement.  Defaults to 1
    */
    public static function meter( $name, $value = 1 )
    {
        self::maybeCreatePhetric();
        self::$_instance->_addMetric( $name, $value, 'meter' );
    }

    /**
     * Add a histograming value.
     *
     * Histograms capture distribution measurements about a metric: the count,
     * maximum, minimum, mean, standard deviation, median, 75th percentile,
     * 95th percentile, 98th percentile, 99th percentile, and 99.9th percentile
     * of the recorded values.
     *
     * @param string $name The Name you want to assign to your Metric
     * @param int $value The amount you want to increment or decrement.  Defaults to 1
     * @param bool $bias if true, Exponential decay histogram (favor more recent)
    */
    public static function histogram( $name, $value = 1, $bias = TRUE )
    {
        self::maybeCreatePhetric();
        $type = ($bias) ? 'biased' : 'uniform' ;
        self::$_instance->_addMetric( $name, $value, $type );
    }

    /**
     * Add or Set a timer.
     *
     * Timers record the duration as well as the rate of events. In addition to the
     * rate information that meters provide, timers also provide the same metrics as
     * histograms about the recorded durations. (The samples that timers keep in order
     * to calculate percentiles and such are biased towards more recent data, since
     * you probably care more about how your application is doing now as opposed to
     * how it's done historically.)
     *
     * The first time you call a timer with each $name, the time will be recorded. On
     * Subsequint calls with that $name, the time elapsed since the first call will
     * be tracked for sending.
     *
     * @param string $name the name of the metric
     *
    */
    public static function timer($name, $value)
    {
        self::maybeCreatePhetric();
        self::$_instance->_addMetric( $name, $value, 'timer' );
    }

    /**
     *  Serialize our data and send it via udp to the host and port we defined earlier
    */
    public static function sendMetrics()
    {
        if ( empty(self::$_instance->_data) )
            return;

        foreach(self::$_instance->_data as $metric)
        {
            $metric->name = self::$_instance->_prepend . $metric->name;
            $metrics[] = $metric;
        }
        
        $data = json_encode($metrics);

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_sendto($socket, $data, strlen($data), 0, self::$_instance->_host, (int) self::$_instance->_port);
        socket_close($socket);

        self::$_instance->_data = array();
    }

    /**
     * Add a metric to our array
     *
     * This function is wrapped by all of our tracking function.  Not very complicated,
     * just builds our object that is added to the array of metrics to send.
     *
     * @param string $name  The name of the metric
     * @param string $value The value of the metric we are hoping to send
     * @param string $type  The type of metric.
    */
    private function _addMetric( $name, $value, $type )
    {
        $metric = new stdClass;
        $metric->name =  $name;
        $metric->value = $value;
        $metric->type = $type;
        $metric->timestamp = microtime(true);
        $this->_data[] = $metric;
        if ($this->_autoflush) {
            self::sendMetrics();
        }
    }

}
