# How to:

1. Set DOTENV file. Copy it from example: `cp .env.docker.dist .env`.

2) Make cache directory: `mkdir silverstripe-cache`.

3) Modify host address list by using `hosts` command. Add `127.0.0.1 <SITE_URL>`

   To force your changes to take effect, flush the DNS cache by entering the following command:

   `dscacheutil -flushcache`

4) Run Docker: `dstart` or `docker-compose up -d`

5) SSH into docker container: `dcli` or `docker-compose exec fenz002-www bash`

# Host alias setup
Add the following to your host bash profile:

```bash
alias dstart='docker-compose -f "docker-compose.yml" up -d --build'
alias dstop='docker-compose -f "docker-compose.yml" down'

function dex-fn {
  www=$(docker container ls --quiet --filter "name=www")
  docker exec -it "$www" bash
}

alias dcli=dex-fn
```

# Helpful commands (host only)
- `dstart` - boot docker 
- `dstop` - tear down docker
- `dcli` - ssh to bash on this container

# Helpful Commands (in Docker)
- `build` - runs dev/build with flush.
- `flush` - removes all silverstripe-cache folders on this container.
- `expose` - runs vendor-expose
- `autoload` - runs dump-autoload
- `compose` runs composer install 
- `standards` runs composer silverstripe-standards 
- `phpcs` - runs composer phpcs 
- `test` - runs vendor/bin/phpunit  
- `clearJobs` - runs Symbiote-QueuedJobs-Tasks-DeleteAllJobsTask confirm=1  
- `ondebug` - enable xdebug 
- `offdebug` - disable xdebug 
- `restart` - restarts PHP 
- `compose1` - set composer to v1 
- `compose2` - set composer to v2 
- `qwerty` - runs ProcessJobQueueTask 
- `sconfig` - runs Elastic SearchConfigure task
- `reindex` - runs Elastic SearchReindex task
- `sstmpdbclear` - runs CleanupTestDatabasesTask

# Documentation
For more detailed documentation on how to use and run bespoke docker containers
read [unified docker dev](https://silverstripe.atlassian.net/wiki/spaces/DEV/pages/2743074877/Unified+Bespoke+Docker+dev) confluence page.

