# Racknews

An attempt at a RackTables API and reports tool.

## How to Use

### Installation

To install, simply clone this repository to RackTables' `wwwroot`.

### Authentication

Currently Racknews simply uses the existing authentication of RackTables. If you
are prompted for your username or password, simply use your RackTables credentials.

If using curl, you may also pass your username and password in the URL, for example
`http://admin:foobar@racktables.dev/racknews/objects` or use a `~/.netrc` file:

    machine racktables.dev
      login admin
      password foobar
      
and use curl's `-n` option.

`TODO`
