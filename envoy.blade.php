##--------------------------------------------------------------------------
# List of tasks, that you can run...
# e.g. envoy run hello
#--------------------------------------------------------------------------
#
# hello     Check ssh connection
# deploy    Publish new release
# list      Show list of releases
# checkout  Checkout to the given release (must provide --release=/path/to/release)
# prune     Purge old releases (must provide --keep=n, where n is a number)
#
#--------------------------------------------------------------------------
# Note that the server shoulbe be accessible through ssh with 'username' account
# $ ssh username@hostname
#--------------------------------------------------------------------------
##

@servers(['development' => ['tdi-dev2'],'stage' => [''],'production' => ['tdi-']])

@setup
  $environment = isset($env) ? $env : "development";
  $release_branch = isset($branch) ? $branch : "develop";

  $username = 'tdi';                       // username at the server
  $remote = 'git@github.com-curator9:nsmg-development/curator9-control.git';   // github repository to clone
  $base_dir = "/home/tdi/curator9-control";        // document that holds projects
  $project_name = "curator9-control";        // project name
  $project_root = "{$base_dir}/{$project_name}";     // project root
  $shared_dir = "{$base_dir}/shared";         // directory that will house shared dir/files
  $release_dir = "{$base_dir}/releases";      // release directory
  $distname = 'release_' . date('YmdHis');    // release name

  // ------------------------------------------------------------------
  // Leave the following as it is, if you don't know what they are for.
  // ------------------------------------------------------------------

  $required_dirs = [
    $shared_dir,
    $release_dir,
  ];

  $shared_item = [
    "{$shared_dir}/.env" => "{$release_dir}/{$distname}/.env",
    "{$shared_dir}/storage" => "{$release_dir}/{$distname}/storage",
    "{$shared_dir}/cache" => "{$release_dir}/{$distname}/bootstrap/cache",
  ];
@endsetup

@task('hello', ['on' => $environment,'parallel' => true])
  HOSTNAME=$(hostname);
  echo "Hello Envoy! Responding from $HOSTNAME";
@endtask


@task('deploy', ['on' => $environment,'parallel' => true])
  {{--Create directories if not exists--}}
  @foreach ($required_dirs as $dir)
    [ ! -d {{ $dir }} ] && mkdir -p {{ $dir }};
  @endforeach

  {{--Download book keeping officer--}}
  if [ ! -f {{ $base_dir }}/officer.php ]; then
    wget https://raw.githubusercontent.com/orgebattle/envoy-script/main/officer.php -O {{ $base_dir }}/officer.php;
  fi;

  {{--Clone code from git--}}
  cd {{ $release_dir }} && git clone -b {{ $release_branch }} {{ $remote }} {{ $distname }};

  {{--nginx vhost configuration--}}
{{--  cd {{ $release_dir }}/{{ $distname }} && sudo cp -f .env.nginx.{{ $environment }} /etc/nginx/sites-available/{{ $project_name }};--}}
{{--  if [ ! -f /etc/nginx/sites-enabled/{{ $project_name }} ]; then--}}
{{--    sudo ln -nfs /etc/nginx/sites-available/{{ $project_name }} /etc/nginx/sites-enabled/{{ $project_name }}--}}
{{--  fi;--}}

  sudo cp -f {{ $release_dir }}/{{ $distname }}/.env.{{ $environment }} {{ $shared_dir }}/.env;
  [ ! -d {{ $shared_dir }}/storage ] && \
  [ -d {{ $release_dir }}/{{ $distname }}/storage ] && \
  cp -R {{ $release_dir }}/{{ $distname }}/storage {{ $shared_dir }};
  [ ! -d {{ $shared_dir }}/cache ] && \
  [ -d {{ $release_dir }}/{{ $distname }}/bootstrap/cache ] && \
  cp -R {{ $release_dir }}/{{ $distname }}/bootstrap/cache {{ $shared_dir }};

  {{--Symlink shared directory to current release.--}}
  {{--e.g. storage, .env, user uploaded file storage, ...--}}
  @foreach($shared_item as $global => $local)
    [ -f {{ $local }} ] && rm {{ $local }};
    [ -d {{ $local }} ] && rm -rf {{ $local }};
    [ -f {{ $global }} ] && ln -nfs {{ $global }} {{ $local }};
    [ -d {{ $global }} ] && ln -nfs {{ $global }} {{ $local }};
  @endforeach


  {{--Run composer install--}}
  cd {{ $release_dir }}/{{ $distname }} && \
  [ -f ./composer.json ] && \
  composer install --prefer-dist --no-scripts --no-dev;

  {{--Any additional command here--}}
  {{--e.g. php artisan clear-compiled;--}}
  cd {{ $release_dir }}/{{ $distname }} && \
{{--  if [ ! -d ./node_modules ]; then--}}
{{--  npm ci--}}
{{--  else--}}
{{--  npm update--}}
{{--  fi;--}}

{{--  npm run prod--}}

  {{--Symlink current release to service directory.--}}
  {{--e.g. storage, .env, user uploaded file storage, ...--}}
  ln -nfs {{ $release_dir }}/{{ $distname }} {{ $project_root }};

  {{--Set permission and change owner--}}
  [ -d {{ $shared_dir }}/storage ] && \
  sudo chmod -R 775 {{ $shared_dir }}/storage;
  [ -d {{ $shared_dir }}/cache ] && \
  sudo chmod -R 775 {{ $shared_dir }}/cache;
  sudo chgrp -h -R www-data {{ $release_dir }}/{{ $distname }};
  sudo chown -R {{ $username }}:www-data {{ $shared_dir }}

  {{--Book keeping--}}
  php {{ $base_dir }}/officer.php deploy {{ $release_dir }}/{{ $distname }};

  {{--Restart web server.--}}
{{--  sudo service nginx restart;--}}
{{--  sudo service php7.4-fpm restart;--}}
@endtask


@task('prune', ['on' => $environment,'parallel' => true])
if [ ! -f {{ $base_dir }}/officer.php ]; then
echo '"officer.php" script not found.';
echo '\$ envoy run hire_officer';
exit 1;
fi;

@if (isset($keep) and $keep > 0)
  php {{ $base_dir }}/officer.php prune {{ $keep }};
@else
  echo 'Must provide --keep=n, where n is a number.';
@endif
@endtask


@task('hire_officer', ['on' => $environment,'parallel' => true])
  {{--Download "officer.php" to the server--}}
  wget https://raw.githubusercontent.com/appkr/envoy/master/scripts/officer.php -O {{ $base_dir }}/officer.php;
  echo '"officer.php" is ready! Ready to roll master!';
@endtask


@task('list', ['on' => $environment,'parallel' => true])
  {{--Show the list of release--}}
  if [ ! -f {{ $base_dir }}/officer.php ]; then
    echo '"officer.php" script not found.';
    echo '\$ envoy run hire_officer';
    exit 1;
  fi;

  php {{ $base_dir }}/officer.php list;
@endtask


@task('checkout', ['on' => $environment,'parallel' => true])
  {{--checkout to the given release path--}}
  if [ ! -f {{ $base_dir }}/officer.php ]; then
    echo '"officer.php" script not found.';
    echo '\$ envoy run hire_officer';
    exit 1;
  fi;

  @if (isset($release))
    cd {{ $release }};

    {{--Symlink shared directory to the given release.--}}

    {{--Symlink the given release to service directory.--}}
    ln -nfs {{ $release }} {{ $project_root }};

    {{--Book keeping--}}
    php {{ $base_dir }}/officer.php checkout {{ $release }};

    {{--Restart web server.--}}
    sudo systemctl restart nginx;
    sudo systemctl restart php7.4-fpm;
  @else
    echo 'Must provide --release=/full/path/to/release.';
  @endif
@endtask
