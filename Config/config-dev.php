<?php

/* Config root mysql */
$DBROOT = "root";
$DBROOTPASS = "123456";

/*Configurando as constantes*/
$DATABASE = "l2jdb";
$DBUSER = "cabir";
$DBPASS = "l2jcabir";
$DBHOST = "127.0.0.1";

/* DAtabase */
$pathToMysqldump = "";

/* Cria o sql para criar as tabelas */
$banco = file_get_contents(__DIR__ . "/api.sql");

echo "\nConstantes prontas";

/* Criar Banco de Dados e Usuario */
try {
    $conn = new PDO("mysql:host=" . $DBHOST . ";", $DBROOT, $DBROOTPASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("DROP DATABASE IF EXISTS `" . $DATABASE . "`; CREATE DATABASE " . $DATABASE . ";");
    echo "\nBanco de dados " . $DATABASE . " criado com sucesso\n";
    $conn = null;

    $conn = new PDO("mysql:host=" . $DBHOST . ";", $DBROOT, $DBROOTPASS);
    $user = $conn->prepare("SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = '$DBUSER')");
    if ($user->execute()):
        $conn = null;
        echo "\nUsuario ja Existe\n";
    else:
        $qr = "CREATE USER '$DBUSER'@'$DBHOST' IDENTIFIED BY '$DBPASS';
         GRANT ALL PRIVILEGES ON *.* TO '$DBUSER'@'$DBHOST' WITH GRANT OPTION;
         GRANT ALL PRIVILEGES ON *.* TO '$DBUSER'@'$DBHOST' REQUIRE NONE WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;
         FLUSH PRIVILEGES";
        $conn = new PDO("mysql:host=" . $DBHOST . ";", $DBROOT, $DBROOTPASS);
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

echo "\nConfigs prontas para desenvolvimento";

$dirs = [
    "dataPath" => __DIR__ . "/data",
    "backupPath" => __DIR__ . "/data/backup/",
    "logsPath" => __DIR__ . "/data/logs/"
];
array_map("make_dir", $dirs);
echo "\nCache Doctrine Logs Removidos";

/*==========================================*/
/*============= Install Backup =============*/
/*==========================================*/
$phpbu = __DIR__ . "/phpbu.phar";
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
file_put_contents(__DIR__ . "/phpbuAdapter.php", $phpbuadapter);
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
file_put_contents(__DIR__ . "/phpbu.json", json_encode($phpbuArr));
echo "\nPHPBU JSON Gerado com sucesso!";
