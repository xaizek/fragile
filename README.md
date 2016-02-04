_fragile_
_2015 - 2016_

**Last updated**: 04 February, 2016
**Version**: 0.4

### Brief Description ###

This is a simple, minimal and straightforward CI to compensate complexity of
many CIs out there among which the author of this one couldn't pick (although he
tried really hard to omit creating one more CI).

### Dependencies ###

* PHP
* SQLite
* Unix-like system with bash
* Git (by default, but can be changed by editing `vcs/*`)

### Small Features (because there are no big ones) ###

* No "hard to get working" dependencies
* No special configuration interface (adding a builder is just adding a script
  to a directory)
* No parallel execution
* No external builders
* Automatic errors/warnings discovery and highlighting
* HTML, there is no useless animations and such via any scripts

### Screenshot ###

![Dashboard](other/fragile.png)

### Demo ###

One can see it being used [here](http://ci.vifm.info/).

### License ###

GNU Affero General Public License, version 3 or later.
