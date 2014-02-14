AppRecorder
===========

Records running and active applications on OS X and saves the result in one or multiple CSV file(s).

Since AppleScript is used to detect the running applications it will only work on OS X.


Usage
-----

```bash
$ php apprecorder.php FILENAME_PATTERN [INTERVAL]
```

### Arguments
- Filename pattern (required): filenames are created based on this pattern; the following replacements are done:
    - `%Y` => current year
    - `%M` => current month
    - `%D` => current day
- Interval (optional): number of seconds between each recording; smaller intervals will record more accurate data but will also require more space. The default value is 5 seconds.

### Examples

The following example will save the CSV files in the current working directory and adds the year, month and day to the filename.

```bash
$ php apprecorder.php "%Y%M%D app records.csv"
```

You can also change the interval to 10 seconds.

```bash
$ php apprecorder.php "%Y%M%D app records.csv" 10
```


Installation
------------

It is useful to start the script automatically when your Mac is started.

1. Edit `com.florianeckerstorfer.apprecorder.plist`. `WorkingDirectory` must be the directory where `apprecorder.php` is located and the third argument of `ProgramArguments` must be the filename pattern including the path to the directory.
2. Copy `com.florianeckerstorfer.apprecorder.plist` into `~/Library/LaunchAgents` (if the directory does not exist, create it).

To remove autostart delete the plist file.


Todo
----

- Tools to visualise data
- Record when the computer is not used


Author
------

- [Florian Eckerstorfer](http://florian.ec) ([Twitter](http://twitter.com/Florian_))


License
-------

The MIT License (MIT)

Copyright (c) 2014 Florian Eckerstorfer

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
