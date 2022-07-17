FROM ubuntu:20.04

RUN apt-get update \
    && apt-get install -y locales \
    && locale-gen en_US.UTF-8

ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8
ENV DEBIAN_FRONTEND noninteractive
ENV TZ=Pacific/Auckland

# install deps, apache, php and php modules all in one run and clean up afterwards to reduce the snapshot size
RUN apt-get update \
    && apt-get install -y nginx curl ufw net-tools sudo zip unzip telnet vim nano \
    && apt-get install -y  git software-properties-common supervisor sqlite3 default-jre \
    && add-apt-repository -y ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y iputils-ping php8.0-fpm php8.0-cli php8.0-gd php8.0-mysql php8.0-bcmath \
    php8.0-intl php8.0-zip php8.0-sqlite3 php8.0-imap php-memcached php8.0-mbstring php8.0-xml php8.0-opcache \
    php8.0-curl php8.0-xdebug php8.0-pcov php8.0-tidy libxml2-dev php8.0-soap php8.0-imagick libmagickwand-dev \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && mkdir /run/php \
    && apt-get remove -y --purge software-properties-common \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && echo "daemon off;" >> /etc/nginx/nginx.conf \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

COPY conf/8.0/php-fpm.conf /etc/php/8.0/fpm/php-fpm.conf
COPY conf/xdebug.ini /etc/php/8.0/mods-available/xdebug.ini
COPY conf/8.0/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY conf/8.0/default.conf /etc/nginx/sites-available/default
COPY conf/8.0/www.conf /etc/php/8.0/fpm/pool.d/www.conf
COPY github-oauth.json /root/.composer/auth.json

# tweak php-fpm config
RUN sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/g' /etc/php/8.0/fpm/php.ini && \
    sed -i 's/post_max_size = 8M/post_max_size = 101M/g' /etc/php/8.0/fpm/php.ini && \
    sed -i 's/;date.timezone =/date.timezone = Pacific\/Auckland/g' /etc/php/8.0/fpm/php.ini && \
    sed -i 's/memory_limit = 128M/memory_limit = -1/g' /etc/php/8.0/fpm/php.ini && \
    sed -i 's/;extension=intl/extension=intl/g' /etc/php/8.0/fpm/php.ini && \
    sed -i 's/;extension=tidy/extension=tidy/g' /etc/php/8.0/fpm/php.ini

RUN echo 'alias build="vendor/bin/sake dev/build flush=all"' >> ~/.bashrc && \
    echo 'alias flush="rm -rf silverstripe-cache/* silverstripe-cachinclude/cache/* silverstripe-redirects/cache/*"' >> ~/.bashrc && \
    echo 'alias expose="composer vendor-expose"' >> ~/.bashrc && \
    echo 'alias autoload="composer dump-autoload"' >> ~/.bashrc && \
    echo 'alias compose="composer install"' >> ~/.bashrc  && \
    echo 'alias standards="composer silverstripe-standards"' >> ~/.bashrc  && \
    echo 'alias phpcs="composer phpcs-fix"' >> ~/.bashrc  && \
    echo 'alias test="vendor/bin/phpunit"' >> ~/.bashrc && \
    echo 'alias sake="vendor/bin/sake"' >> ~/.bashrc && \
    echo 'alias clearJobs="sake dev/tasks/Symbiote-QueuedJobs-Tasks-DeleteAllJobsTask confirm=1"' >> ~/.bashrc && \
    echo 'alias refresh="test vendor/silverstripe/framework/tests '' flush=1 "' >> ~/.bashrc && \
    echo 'alias ondebug="sudo phpenmod -vs cli xdebug"' >> ~/.bashrc  && \
    echo 'alias offdebug="sudo phpdismod -vs cli xdebug"' >> ~/.bashrc && \
    echo 'alias restart="sudo service php8.0-fpm restart"' >> ~/.bashrc && \
    echo 'alias compose1="composer self-update 1.10.17"' >> ~/.bashrc && \
    echo 'alias compose2="composer self-update --rollback"' >> ~/.bashrc && \
    echo 'alias qwerty="sake dev/tasks/ProcessJobQueueTask"' >> ~/.bashrc && \
	echo 'alias sconfig="sake dev/tasks/SearchConfigure"' >> ~/.bashrc && \
	echo 'alias reindex="sake dev/tasks/SearchReindex"' >> ~/.bashrc

RUN mkdir /var/www/html/silverstripe-cache &&  \
    sudo chmod -R ug+w /var/www/html/silverstripe-cache && \
    mkdir /var/www/html/logs && sudo chmod -R 777 /var/www/html/logs && \
    mkdir /var/www/html/logs/xdebug  && sudo chmod -R 777 /var/www/html/logs/xdebug && \
    touch /var/www/html/logs/xdebug/remote.log

RUN echo 'alias ondebug="sudo phpenmod -s cli xdebug"' >> ~/.bashrc  && \
	echo 'alias offdebug="sudo phpdismod -s cli xdebug"' >> ~/.bashrc && \
	echo 'alias compose1="composer self-update 1.10.17"' >> ~/.bashrc && \
	echo 'alias compose2="composer self-update --rollback"' >> ~/.bashrc && \
	echo 'alias qwerty="sake dev/tasks/ProcessJobQueueTask"' >> ~/.bashrc && \
	echo 'alias sconfig="sake dev/tasks/SearchConfigure"' >> ~/.bashrc && \
	echo 'alias reindex="sake dev/tasks/SearchReindex"' >> ~/.bashrc

# tweak php-fpm config for opcache
#RUN sed -i 's/opcache.enable=1/;opcache.enable=1/g' /etc/php/8.0/fpm/php.ini && \
#    sed -i 's/opcache.revalidate_freq=2/opcache.revalidate_freq=0/g' /etc/php/8.0/fpm/php.ini \


EXPOSE 80 443

CMD "/usr/bin/supervisord"

WORKDIR /var/www/html
