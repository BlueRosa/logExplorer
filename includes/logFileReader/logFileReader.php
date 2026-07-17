<?php
/**
 * Import de toutes les fonctions de lecture de log
 */
include("specialisation/logFileReaderApplications.php");
include("specialisation/logFileReaderAuthentificationReseau.php");
include("specialisation/logFileReaderBdd.php");
include("specialisation/logFileReaderCacheServices.php");
include("specialisation/logFileReaderConteneurCloud.php");
include("specialisation/logFileReaderMail.php");
include("specialisation/logFileReaderPareFeu.php");
include("specialisation/logFileReaderReseauConstructeur.php");
include("specialisation/logFileReaderSecurite.php");
include("specialisation/logFileReaderServiceReseau.php");
include("specialisation/logFileReaderSupervision.php");
include("specialisation/logFileReaderSyslog.php");
include("specialisation/logFileReaderTelephonie.php");
include("specialisation/logFileReaderVPN.php");
include("specialisation/logFileReaderWeb.php");
include("specialisation/csvFileReader.php");


/**
 * @var array $listeTypes liste des trypes de logs acceptés et de leur nom de fonction de lecture
 * format : $listeTypes[catégorie][0 : format ou 1 : fonction]
 */
$listeTypes = [
    "Web" => [
        ["apache_access", "lireApacheAccess"],
        ["apache_error", "lireApacheError"],
        ["apache_full_access", "lireApacheFullAccess"],
        ["nginx_access", "lireNginxAccess"],
        ["nginx_error", "lireNginxError"],
        ["php_error", "lirePhpError"],
    ],

    "Syslog" => [
        ["syslog_rfc3164", "lireSyslogRFC3164"],
        ["syslog_rfc5424", "lireSyslogRFC5424"],
        ["linux_syslog", "lireLinuxSyslog"],
    ],

    "Réseau constructeur" => [
        ["cisco_ios", "lireCiscoIOS"],
        ["juniper", "lireJuniper"],
    ],

    "Firewall" => [
        ["fortigate", "lireFortigate"],
        ["paloalto", "lirePaloAlto"],
    ],

    "Services réseau" => [
        ["dhcp", "lireDHCP"],
        ["dns", "lireDNSBind"],
    ],

    "Authentification réseau" => [
        ["radius", "lireRadius"],
        ["freeradius", "lireRadius"],
    ],

    "VPN" => [
        ["openvpn", "lireOpenVPN"],
        ["wireguard", "lireWireguard"],
    ],

    "Téléphonie" => [
        ["sip", "lireSIP"],
        ["cdr", "lireCDR"],
    ],

    "Supervision" => [
        ["snmp_trap", "lireSNMPTrap"],
        ["nagios", "lireSupervision"],
        ["zabbix", "lireSupervision"],
        ["centreon", "lireSupervision"],
    ],

    "Conteneurs / Cloud" => [
        ["kubernetes", "lireKubernetesLog"],
        ["docker", "lireDockerLog"],
    ],

    "Sécurité" => [
        ["modsecurity", "lireModSecurity"],
        ["fail2ban", "lireFail2ban"],
    ],

    "Bases de données" => [
        ["mysql", "lireMysqlError"],
        ["postgresql", "lirePostgresql"],
        ["sqlserver_error", "lireSqlServerError"],
        ["sqlserver_agent", "lireSqlServerAgent"],
    ],

    "Linux mail" => [
        ["postfix", "lirePostfix"],
        ["dovecot", "lireDovecot"],
        ["exim", "lireExim"],
        ["sendmail", "lireSendmail"],
    ],

    "Sécurité mail" => [
        ["amavis", "lireAmavis"],
        ["spamassassin", "lireSpamAssassin"],
        ["clamav", "lireClamAV"],
    ],

    "Zimbra" => [
        ["zimbra", "lireZimbra"],
        ["zimbra_auth", "lireZimbraAuth"],
    ],

    "Microsoft" => [
        ["exchange_transport", "lireExchangeTransport"],
        ["exchange_smtp", "lireExchangeSMTP"],
        ["m365_message_trace", "lireM365MessageTrace"],
        ["m365_audit", "lireM365Audit"],
        ["m365_defender", "lireM365Defender"],
    ],

    "Cache / Services" => [
        ["redis", "lireRedis"],
        ["varnish", "lireVarnish"],
        ["squid", "lireSquid"],
        ["memcached", "lireMemcached"],
        ["unbound", "lireUnbound"],
        ["powerdns", "lirePowerDNS"],
    ],

    "Applications" => [
        ["laravel", "lireLaravel"],
        ["pm2", "lirePM2"],
        ["ci", "lireCI"],
    ],
];

/**
 * Fonction de lecture du fichier de log (ou csv) à partir du chemin
 * @param string $chemin lien du fichier de log sur le serveur
 * @return array données contenues par le fichier
 */
function analyserFichierLog(string $chemin): array
{
    global $listeTypes;
    if (!is_file($chemin)) {
        return [];
    }
    $fichierInfo = $chemin . ".info";
    if (!is_file($fichierInfo)) {
        return [];
    }
    $info = json_decode(file_get_contents($fichierInfo), true);
    if (!is_array($info) || !isset($info["type"])) {
        return [];
    }
    $typeLog = $info["type"];
    if ($typeLog === "csv") {
        $separateur = $info["separateur"];
        return lireCSV($chemin, $separateur);
    }
    foreach ($listeTypes as $types) {
        foreach ($types as $type) {
            if ($type[0] === $typeLog) {
                return call_user_func($type[1], $chemin);
            }
        }
    }
    return [];
}