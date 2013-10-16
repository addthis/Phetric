# Phetric

Phetric is a library that allow you to send PHP application-level metrics to a
catcher (such as [MetricCatcher](https://github.com/addthis/MetricCatcher))
that then makes them available in a fun and interesting way.

## License

Licensed under the MIT licenses.

Copyright (c) 2013 AddThis 

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions: The above copyright notice and this
permission notice shall be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


## How to Use Phetric

If you are not using an autoloader, you need to include the Sender (which will
pull in everything else):

```php
require_once('phetric/Sender.php');
```

Next up, you need to tell Phetric where it should send stats once it has them.
When we init the sender, we need to say where we want our metrics, what port we
want them sent to, and an optional string that will be prepended to all the
stats.  This is usefull for identifying the application that is sending the
request.

```php
Phetric_Sender::init( 'localhost', '1420', $prepend );
```

Finally, we can send our metrics.  Phetric implements Gauges, Counters, Meters,
Histograms and Timers.  When you create a new metric, you need to create it with
a name and for everything other than timers, mark our value.


### Gauges

```php
// Create a metric and mark some data
$gauge = Phetric_Gauge::create('label');
$gauge->mark(42414);
```

### Timers

Timers count in microseconds the time betweent when they are started and
stopped.

```php
// Create a Timer and call it
$blue = Phetric_Timer::create('blue');
$blue->start();

// Do something

// Stop our Timer
$blue->stop();
```

### Counter

Counters default to 1 if you don't specifify a number when you mark it

```php
// One line example
Phetric_Counter::create('orange')->mark(123);
```

### Meter

A meter measures the rate of events over time (e.g., “requests per
second”). In addition to the mean rate, meters also track 1-, 5-, and 15-minute
moving averages.

```php
Phetric_Meter::create('red')->mark(123);
```

### Histogram

A histogram measures the statistical distribution of values. In
addition to minimum, maximum, mean, etc., it also measures median, 75th, 90th,
95th, 98th, 99th, and 99.9th percentiles.

```php
$yellow = Phetric_Histogram::create('yellow');
$yellow->mark(13);
```

## Checking the data you are sending

If you don't want run a local copy of MetricCatcher, this bash function will use netcat to listen on 1420 and output anything that arrives on UDP.   
```
catcher(){
    while true;
    do
        nc -w 1 -l -u 1420;
    done;
}
```

# Administrivia

## Author

Phetric was written by [Aaron Jorbin](http://aaron.jorb.in)
<aaron@jorb.in> while at [AddThis](http://addthis.com).

## Using Phetric with Composer
Phetric is available on [packagist](https://packagist.org/packages/clearspring/phetric) thanks to https://github.com/ammmze

## Bugs & so forth

Please reqport bugs or request new features at the GitHub page for
Phetric: http://github.com/addthis/Phetric

## Jobs

When this was written, AddThis was hiring; even if the blame on this line is
from long ago, they probably still are.  Check out http://www.addthis.com/careers if
you're intersted in doing webapps, working with Big Data, and like smart, fun
coworkers.  AddThis is based just outside of Washington, DC (Tysons Corner)
and has offices in New York, Los Angeles, and beyond.

