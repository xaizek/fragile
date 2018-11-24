## Structure ##

There are two parts of the application:
 * web-interface
 * daemon

### Installation ###

One will probably want to install them in two separate locations, but they can
reside in the same directory.

## Configuration ##

Before installing files, copy `config.php.sample` to `config.php` and fill in
all the values according to comments there.  This file holds configuration of
both web and daemon parts and should be kept in sync between the two parts.

## Controlling ##

Some commands can be send simply by pushing branches which are named according
to the following pattern:
```
fragile-do/<command><optional %-seperated arguments>
```

Where `<command>` can be:
 - `clean` – remove all build directories
 - `repeat%<build id>` – restart specified build

Examples:

```
# remove buildbox
git push --force ci HEAD:fragile-do/clean
# schedule all builders for build #945 as a new build
git push --force ci HEAD^:fragile-do/repeat%945
```

## Installation ##

Run the `install` script to perform the installation:

    install <web-path> <daemon-path>

Start the daemon and it will create database, web-interface will create it too,
but only if it has necessary permissions.

### Managing builders ###

Builders are just scripts in `<web-path>/builders` directory.  Names of those
scripts are names of builders.  Script exit code should indicate result of a
build.  When script is run:

 * `$FRAGILE_REPO` environment variable points to location of checked out
   repository;
 * `$FRAGILE_REF` environment variable is set to the name of reference of VCS;
 * current directory is build directory.

Use dummy builders from `builders/` directory during setup to check
web-interface isolated from actual build process.

#### Conditional builders ####

In addition to `<web-path>/builders` directory builders from
`<web-path>/builders/<name>` (where `<name>` is branch name probably) are also
scheduled, if that directory exists.  This can be used to run some builders only
for specific branches.

### Scheduling a build ###

Run `new.php` passing it refname and revision ID (in this order) to work with.

### Installing the daemon ###

Example of init-script for `/etc/init.d/` is at `other/rc.fragile`.

### Automating builds ###

One way to do this is to create bare repository for pushes.  Assuming that the
repository is where daemon gets installed, just copy `other/post-update` to
hooks (this is for Git, other VCSs might differ).

### Updates ###

Database is upgraded in place automatically, consider backing it up if
necessary.  It can be upgraded on any access to it, but normally only daemon
will have write permissions on it.
