### Structure ###

There are two parts of the application:
 * web-interface
 * daemon

### Installation ###

One will probably want to install them in two separate locations, but they can
reside in the same directory.

## Configuration ##

Before installing files, copy `config.php.sample` to `config.php` and fill in
all the values of according to comments there.  This file holds configuration of
both web and daemon parts and should be kept in sync between the two parts.

## Installation ##

Run the `install` script to perform the installation:

    install <web-path> <daemon-path>

Start the daemon and it will create database, web-interface will create it too,
but only if it has corresponding permissions.

### Managing builders ###

Builders are just scripts in `<web-path>/builders` directory.  Names are those
scripts are names of builders.  Script exit code should indicate result of a
build.  When script is run:

 * `$FRAGILE_REPO` environment variable points to location of checked out
   repository;
 * current directory is build directory.

Use dummy builders from `builders/` directory during setup to check
web-interface isolated from actual build process.

### Scheduling a build ###

Run `new.php` passing it revision ID to work with.

### Installing the daemon ###

Example of init-script for `/etc/init.d/` is at `other/rc.fragile`.

### Automating builds ###

One way to do this is to create bare repository for pushes.  Assuming that the
repository is where daemon gets installed, just copy `other/post-update` to
hooks (this is for Git, other VCSs might differ).
