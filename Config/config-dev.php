<?php

/* Config root mysql */
$DBROOT = "root";
$DBROOTPASS = "123456";

/*Configurando as constantes*/
$DATABASE = "development";
$DBUSER = "cabir";
$DBPASS = "961219810";
$DBHOST = "127.0.0.1";
$APIURL = "http://api.net";
$ADMINMAIL = "irabound@hotmail.com";
$DROPBOXAPPKEY = "p1mx-oNu0JAAAAAAAAAAD7QWiuIAcU4Bemar17JK8AJh8369rRqHqOddKKAq_t9B";

/* Criando as constantes */
$constantes = "<?php\n";
$constantes .= "namespace Apigility;\n";
$constantes .= "const DEV = true;\n";

$constantes .= "const DATABASE = '" . $DATABASE . "';\n";
$constantes .= "const DBUSER = '" . $DBUSER . "';\n";
$constantes .= "const DBPASS = '" . $DBPASS . "';\n";
$constantes .= "const DBHOST = '" . $DBHOST . "';\n";
$constantes .= "const APIURL = '" . $APIURL . "';\n";

$constanteFile = __DIR__ . "/constants.php";
if (!is_dir($constanteFile) && file_exists($constanteFile))
    @unlink($constanteFile);

file_put_contents($constanteFile, $constantes);

/* Cria o sql para criar as tabelas */
$banco = file_get_contents(__DIR__ . "/api.sql");

echo "\nConstantes prontas";

/* Criar Banco de Dados e Usuario */
try {
    $conn = new PDO("mysql:host=" . $DBHOST . ";", 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("DROP DATABASE IF EXISTS `" . $DATABASE . "`; CREATE DATABASE " . $DATABASE . ";");
    echo "\nBanco de dados " . $DATABASE . " criado com sucesso\n";
    $conn = null;

    $conn = new PDO("mysql:host=" . $DBHOST . ";", 'root', '123456');
    $user = $conn->prepare("SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = '$DBUSER')");
    if ($user->execute()):
        $conn = null;
        echo "\nUsuario ja Existe\n";
    else:
        $qr = "CREATE USER '$DBUSER'@'$DBHOST' IDENTIFIED BY '$DBPASS';
         GRANT ALL PRIVILEGES ON *.* TO '$DBUSER'@'$DBHOST' WITH GRANT OPTION;
         GRANT ALL PRIVILEGES ON *.* TO '$DBUSER'@'$DBHOST' REQUIRE NONE WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;
         FLUSH PRIVILEGES";
        $conn = new PDO("mysql:host=" . $DBHOST . ";", 'root', '123456');
        $conn->exec($qr);
        $conn = null;
        echo "\nUsuario $DBUSER criado com sucesso.\n";
    endif;

    # MySQL with PDO_MYSQL
    if ($banco !== ""):
        $conn = new PDO("mysql:host=" . $DBHOST . ";dbname=" . $DATABASE . "", $DBUSER, $DBPASS);
        $tabelas = $conn->prepare($banco);
        echo ($tabelas->execute()) ? "Tabelas criadas com sucesso" : "Criar tabelas Falhou";
    endif;

} catch (PDOException $e) {
    echo $e->getMessage() . " \n";
    die;
}

/* Configurando vHost para Nginx */
$rootPath = str_replace('\\', '/', realpath(__DIR__ . "../../../"));

$nginxConfigDev = "server {\n";
$nginxConfigDev .= "        listen      80;\n";
$nginxConfigDev .= "        server_name api.net;\n";
$nginxConfigDev .= "        root        $rootPath/public/;\n";
$nginxConfigDev .= "        index       index.php;\n";
$nginxConfigDev .= "        access_log  $rootPath/data/logs/api.access;\n";
$nginxConfigDev .= "        error_log   $rootPath/data/logs/api.error error;\n";
$nginxConfigDev .= "\n";
$nginxConfigDev .= "        location / {\n";
$nginxConfigDev .= "                try_files \$uri \$uri/ /index.php\$is_args\$args;\n";
$nginxConfigDev .= "        }\n";
$nginxConfigDev .= "\n";
$nginxConfigDev .= "        location ~ \.php$ {\n";
$nginxConfigDev .= "                root  $rootPath/public/;\n";
$nginxConfigDev .= "                fastcgi_split_path_info ^(.+\.php)(/.+)$;\n";
$nginxConfigDev .= "                fastcgi_read_timeout 300;\n";
$nginxConfigDev .= "                fastcgi_pass 127.0.0.1:9000;\n";
$nginxConfigDev .= "                fastcgi_index index.php;\n";
$nginxConfigDev .= "                fastcgi_param  SCRIPT_FILENAME \$request_filename;\n";
$nginxConfigDev .= "                include        fastcgi_params;\n";
$nginxConfigDev .= "        }\n";
$nginxConfigDev .= "\n";
$nginxConfigDev .= "}\n";

if (!is_dir($rootPath . "/conf") && !file_exists($rootPath . "/conf"))
    mkdir($rootPath . "/conf", 0777, true);

file_put_contents($rootPath . "/conf/nginx-dev.conf", $nginxConfigDev);

echo "\nNginx Configurado";

/* Configurando vHost para Nginx */
$vhostAdd = 'include "' . $rootPath . '/conf/nginx-dev.conf";';
$rootPathvHost = str_replace('\\', '/', realpath(__DIR__ . "../../../../"));

if (!file_exists($rootPathvHost . "/nginx-vhosts.conf")) {
    $vhosts = fopen($rootPathvHost . "/nginx-vhosts.conf", "a");
    fwrite($vhosts, "\n" . $vhostAdd);
    fclose($vhosts);
    echo "\nVhost criado para Nginx";
} else {
    $contents = file_get_contents($rootPathvHost . "/nginx-vhosts.conf");
    $pattern = preg_quote($vhostAdd, '/');
    $pattern = "/^.*$pattern.*\$/m";
    if (!preg_match_all($pattern, $contents, $matches)) {
        $vhosts = fopen($rootPathvHost . "/nginx-vhosts.conf", "a");
        fwrite($vhosts, "\n" . $vhostAdd);
        fclose($vhosts);
    }
    echo "\nVhost adicionado para Nginx";
}


/* Configurando o Nginx adicionando a lista de vhosts */
$rootNginxConfig = str_replace('\\', '/', realpath(__DIR__ . "../../../../../nginx/conf/nginx.conf"));
$linhaAdd = 'include "' . $rootPathvHost . '/nginx-vhosts.conf";';

$contents = file_get_contents($rootNginxConfig);
$pattern = preg_quote($linhaAdd, '/');
$pattern = "/^.*$pattern.*\$/m";
if (!preg_match_all($pattern, $contents, $matches)) {
    $rootConfigNginxDefault = str_replace('\\', '/', realpath(__DIR__ . "../../../../../nginx"));
    $configNginxDefault = "\n
worker_processes  auto;\n
error_log  \"" . $rootConfigNginxDefault . "/logs/error.log\";\n
pid        \"" . $rootConfigNginxDefault . "/logs/nginx.pid\";\n
events {\n
    worker_connections  1024;\n
}\n
http {\n
    include       mime.types;\n
    default_type  application/octet-stream;\n
    client_body_temp_path  \"" . $rootConfigNginxDefault . "/tmp/client_body\" 1 2;\n
    proxy_temp_path \"" . $rootConfigNginxDefault . "/tmp/proxy\" 1 2;\n
    fastcgi_temp_path \"" . $rootConfigNginxDefault . "/tmp/fastcgi\" 1 2;\n
    scgi_temp_path \"" . $rootConfigNginxDefault . "/tmp/scgi\" 1 2;\n
    uwsgi_temp_path \"" . $rootConfigNginxDefault . "/tmp/uwsgi\" 1 2;\n
    access_log  \"" . $rootConfigNginxDefault . "/logs/access.log\";\n
    sendfile        on;\n
    keepalive_timeout  65;\n
    gzip on;\n
    gzip_http_version 1.1;\n
    gzip_comp_level 2;\n
    gzip_proxied any;\n
    gzip_vary on;\n
    gzip_types text/plain\n
               text/xml\n
               text/css\n
               text/javascript\n
               application/json\n
               application/javascript\n
               application/x-javascript\n
               application/ecmascript\n
               application/xml\n
               application/rss+xml\n
               application/atom+xml\n
               application/rdf+xml\n
               application/xml+rss\n
               application/xhtml+xml\n
               application/x-font-ttf\n
               application/x-font-opentype\n
               application/vnd.ms-fontobject\n
               image/svg+xml\n
               image/x-icon\n
               application/atom_xml;\n
    gzip_buffers 16 8k;\n
    add_header X-Frame-Options SAMEORIGIN;\n
    ssl_prefer_server_ciphers  on;\n
    ssl_protocols TLSv1 TLSv1.1 TLSv1.2;\n
    ssl_ciphers ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:RSA+AESGCM:RSA+AES:!aNULL:!MD5:!DSS;\n
    include \"" . $rootConfigNginxDefault . "/conf/bitnami/bitnami.conf\";\n 
	" . $linhaAdd . "\n
}";

    file_put_contents($rootNginxConfig, $configNginxDefault);
    echo "\nConfiguração do nginx atualizada";
}

echo "\nConfigs prontas para desenvolvimento";

/*==========================================*/
/*============= Removendo Logs =============*/
/*==========================================*/
echo "\nRemovendo Cache e Logs";

function make_dir($a)
{
    if (!file_exists($a) && !is_dir($a)):
        $data = mkdir($a, 0777, true);
        chmod($a, 0777);
        echo ($data) ? "\nPasta data/" . (explode("/data/", $a))[1] . " criado com sucesso" : "\nError ao criar pasta data/" . (explode("/data/", $a))[1] . "";
    else:
        $files = glob($a);
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file);
        }
    endif;
}

$dirs = [
    "dataPath" => __DIR__ . "/../../data/",
    "backupPath" => __DIR__ . "/../../data/backup/",
    "logsPath" => __DIR__ . "/../../data/logs/",
    "cachePath" => __DIR__ . "/../../data/cache/",
    "doctrinePath" => __DIR__ . "/../../data/DoctrineORMModule/",
    "doctrineCachePath" => __DIR__ . "/../../data/DoctrineORMModule/Proxy/"
];
array_map("make_dir", $dirs);
echo "\nCache Doctrine Logs Removidos";

/*==========================================*/
/*============= Install Backup =============*/
/*==========================================*/
$phpbu = __DIR__ . "/../../phpbu.phar";
if (!file_exists($phpbu)):
    copy('https://phar.phpbu.de/phpbu.phar', $phpbu);
    chmod($phpbu, 0777);
else:
    if ((substr(decoct(fileperms($phpbu)), (strlen(decoct(fileperms($phpbu))) - 3))) !== 777)
        @chmod($phpbu, 0777);
endif;
echo "\n";
echo system("php phpbu.phar --version");
echo "\nPHPBU Instalado com sucesso!";

/*==============================================*/
/*============= Configurando Backup ============*/
$phpbuadapter = "<?php
return [
    'dirBackup' => [
        'dir' => '/backups/' . date(\"Y\") . '/' . date(\"m\") . '/' . date(\"d\") . ''
    ]
];
";
file_put_contents(__DIR__ . "/../../phpbuAdapter.php", $phpbuadapter);
echo "\nPhP Adapter Criado com sucesso!";
/*==============================================*/

$phpbuArr = [
    "verbose" => true,
    "logging" => [
        [
            "type" => "json",
            "target" => "data/logs/backup.json"
        ]
    ],
    "adapters" => [
        [
            "type" => "array",
            "name" => "cabir",
            "options" => [
                "file" => "phpbuAdapter.php"
            ]
        ]
    ],
    "backups" => [
        [
            "name" => "DataBase",
            "source" => [
                "type" => "mysqldump",
                "options" => [
                    "databases" => "$DATABASE",
                    "user" => "$DBUSER",
                    "password" => "$DBPASS",
                    "pathToMysqldump" => __DIR__ . "/../../../../mysql/bin"
                ]
            ],
            "target" => [
                "dirname" => "data/backup",
                "filename" => "mysql-%Y%m%d-%H%i.sql",
                "compress" => "bzip2"
            ],
            "cleanup" => [
                "type" => "Capacity",
                "options" => [
                    "size" => "1000M"
                ],
            ],
        ],
    ],
];
file_put_contents(__DIR__ . "/../../phpbu.json", json_encode($phpbuArr));
echo "\nPHPBU JSON Gerado com sucesso!";
