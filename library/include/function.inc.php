<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2017 - Antonio De Vincentiis Montesilvano (PE)
  (http://www.devincentiis.it)
  <http://gazie.sourceforge.net>
  --------------------------------------------------------------------------
  Questo programma e` free software;   e` lecito redistribuirlo  e/o
  modificarlo secondo i  termini della Licenza Pubblica Generica GNU
  come e` pubblicata dalla Free Software Foundation; o la versione 2
  della licenza o (a propria scelta) una versione successiva.

  Questo programma  e` distribuito nella speranza  che sia utile, ma
  SENZA   ALCUNA GARANZIA; senza  neppure  la  garanzia implicita di
  NEGOZIABILITA` o di  APPLICABILITA` PER UN  PARTICOLARE SCOPO.  Si
  veda la Licenza Pubblica Generica GNU per avere maggiori dettagli.

  Ognuno dovrebbe avere   ricevuto una copia  della Licenza Pubblica
  Generica GNU insieme a   questo programma; in caso  contrario,  si
  scriva   alla   Free  Software Foundation, 51 Franklin Street,
  Fifth Floor Boston, MA 02110-1335 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
if (isset($_SERVER['SCRIPT_FILENAME']) && (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME'])) {
    exit('Accesso diretto non consentito');
}

connectToDB();

session_cache_limiter('nocache');
$scriptname = basename($_SERVER['PHP_SELF']);
$direttorio = explode("/", dirname($_SERVER['PHP_SELF']));
$module = array_pop($direttorio);
$radixarr = array_diff($direttorio, array('modules', $module, ''));
$radix = implode('/', $radixarr);
if (strlen($radix) > 1) {
    session_name(implode($radixarr));
} else {
    session_name(_SESSION_NAME);
}
session_start();
$prev_script = '';
if (isset($_SERVER["HTTP_REFERER"])) {
    $prev = explode("?", basename($_SERVER["HTTP_REFERER"]));
    $prev_script = $prev[0];
}
$script_uri = basename($_SERVER['REQUEST_URI']);
$mod_uri = '/' . $module . '/' . $script_uri;

//stati per le assistenze periodiche
$per_stato = array("Aperto", "Avvisare", "Effettuare", "Fatturare", "Chiuso");

//funzione che estrae i valori tra i tag html di una stringa
function getTextBetweenTags($tag, $html, $strict = 0) {
    $dom = new domDocument;
    if ($strict == 1) {
        $dom->loadXML($html);
    } else {
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        foreach (libxml_get_errors() as $error) {
            //echo $error->code." - Line: ".$error->line;
        }
    }
    $dom->preserveWhiteSpace = false;
    $content = $dom->getElementsByTagname($tag);
    $out = array();
    foreach ($content as $item) {
        $out[] = $item->nodeValue;
    }
    libxml_use_internal_errors(false);
    return $out;
}

function gaz_flt_var_assign($flt, $typ) {
    global $where;
    if (isset($_GET[$flt]) && $_GET[$flt] != 'All' && $_GET[$flt] != "") {
        if ($typ == "i") {
            $where .= " AND " . $flt . " = " . intval($_GET[$flt]) . " ";
        } else if ($typ == "v") {
            $where .= " AND " . $flt . " LIKE '%" . addslashes(substr($_GET[$flt], 0, 30)) . "%'";
        } else if ($typ == "d") {
            $where .= " AND $flt >= \"" . intval($_GET[$flt]) . "/01/01\" and $flt <= \"" . intval($_GET[$flt]) . "/12/31\"";
        }
    }
}

// crea una select che permette di filtrare la colonna di una tabella
// $flt - colonna sulla quale eseguire il filtro
// 
// $optval - valore opzionale se diverso dal valore del campo, può essere array (es: stato=0 diventa stato=aperto preso da var)
function gaz_flt_disp_select($flt, $fltdistinct, $tbl, $where, $orderby, $optval = "") {
    ?><select class="form-control input-sm" name="<?php echo $flt; ?>" onchange="this.form.submit()">
    <?php
    if (isset($_GET[$flt]))
        $fltget = $_GET[$flt];
    else
        $fltget = "";
    ?>
        <option value="All" <?php echo ($flt == "All") ? "selected" : ""; ?>>Tutti</option> <?php //echo $script_transl['tuttitipi'];             ?>

        <?php
        $res = gaz_dbi_dyn_query("distinct " . $fltdistinct, $tbl, $where, $orderby);
        while ($val = gaz_dbi_fetch_array($res)) {
            if ($fltget == $val[$flt])
                $selected = "selected";
            else
                $selected = "";

            if (is_array($optval)) {
                $testo = $optval[$val[$flt]];
            } else {
                $testo = ($optval != "") ? $val[$optval] : $val[$flt];
            }

            echo "<option value=\"" . $val[$flt] . "\" " . $selected . ">" . $testo . "</option>";
        }
        ?>
    </select><?php
}

function gaz_flt_disp_int($flt, $hint) {
    ?><input type="text" placeholder="<?php echo $hint; ?>" class="input-sm form-control" name="<?php echo $flt; ?>" value="<?php if (isset($_GET[$flt])) print $_GET[$flt]; ?>" size="5" class="FacetInput"><?php
}

function gaz_filtro($flt_name, $table, $where, $orderby) {
    //global $gTables;
    echo $_GET[$flt_name];
    if (isset($_GET[$flt_name]) && $_GET[$flt_name] != 'All') {
        $citta = $_GET[$flt_name];
        $where .= " and " . $flt_name . " = '$citta'";
    } else
        $citta = "All";

    $res = gaz_dbi_dyn_query("distinct " . $flt_name, $table, $where, $orderby);
    while ($val = gaz_dbi_fetch_array($res)) {
        if ($citta == $val[$flt_name])
            $selected = "selected";
        else
            $selected = "";
        echo "<option value=\"" . $val[$flt_name] . "\" " . $selected . ">" . $val[$flt_name] . "</option>";
    }
}

function gaz_today() {
    $today = date("d/m/Y");
    $tmp = DateTime::createFromFormat('d/m/Y', $today);
    $today = $tmp->format('Y-m-d');
    return $today;
}

function gaz_time_from($time) {
    $time = time() - $time; // to get the time since that moment
    $time = ($time < 1) ? 1 : $time;
    $tokens = array(
        31536000 => 'anni',
        2592000 => 'mesi',
        604800 => 'settimane',
        86400 => 'giorni',
        3600 => 'ore',
        60 => 'minuti',
        1 => 'secondi'
    );
    foreach ($tokens as $unit => $text) {
        if ($time < $unit)
            continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . " fa"; //.(($numberOfUnits>1)?'i':'o');
    }
}

function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

function gaz_format_number($number = 0) {
    global $gTables;
    $currency = gaz_dbi_get_row($gTables['admin'] . ' LEFT JOIN ' . $gTables['aziend'] . ' ON ' . $gTables['admin'] . '.company_id = ' . $gTables['aziend'] . '.codice
                                                    LEFT JOIN ' . $gTables['currencies'] . ' ON ' . $gTables['currencies'] . '.id = ' . $gTables['aziend'] . '.id_currency', "Login", $_SESSION["Login"]);
    return number_format(floatval($number), $currency['decimal_place'], $currency['decimal_symbol'], $currency['thousands_symbol']);
}

function gaz_create_date($d, $m, $yyyy) { // crea una data nel formato dd-mm-yyyy
    $giorno = substr('00' . $d, -2); // mettiamo lo 0 davanti ai numeri di 1 cifra
    $mese = substr('00' . $m, -2); // mettiamo lo 0 davanti ai numeri di 1 cifra
    return $giorno . "-" . $mese . "-" . $yyyy;
}

function gaz_format_date($date, $from_form = false, $to_form = false) {
    if ($from_form) { // dal formato gg-mm-aaaa o gg/mm/aaaa (es. proveniente da form) a diversi 
        $m = intval(substr($date, 3, 2));
        $d = intval(substr($date, 0, 2));
        $Y = intval(substr($date, 6, 4));
        $uts = mktime(0, 0, 0, $m, $d, $Y);
        if ($from_form === true) { // adatto al db
            return date("Y-m-d", $uts);
        } elseif ($from_form === 1) { // per i campi input dei form
            return date("d/m/Y", $uts);
        } elseif ($from_form === 2) { // restituisce l'mktime
            return $uts;
        } elseif ($from_form === 3) { // il valore numerico (confrontabile)
            return date("Ymd", $uts);
        } elseif ($from_form === 'chk') { // restituisce true o false se la data non è stata formattata bene
            return checkdate($m, $d, $Y);
        } else { // altri restituisco il timestamp 
            return date("Ymd", $uts);
        }
    } else { // dal formato aaaa-mm-gg oppure aaaa/mm/gg (es. proveniente da db) a diversi
        $uts = mktime(0, 0, 0, intval(substr($date, 5, 2)), intval(substr($date, 8, 2)), intval(substr($date, 0, 4)));
        if ($to_form === false) { // adatto al db
            return date("d-m-Y", $uts);
        } elseif ($to_form === 2) { // restituisce l'mktime
            return $uts;
        } else { // adatto ai form input
            return date("d/m/Y", $uts);
        }
    }
}

function gaz_format_datetime($date) {
    $uts = mktime(substr($date, 11, 2), substr($date, 14, 2), substr($date, 17, 2), substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4));
    return date("d-m-Y H:i:s", $uts);
}

function gaz_html_call_tel($tel_n) {
    if ($tel_n != "_") {
        preg_match_all("/([\d]+)/", $tel_n, $r);
        $ret = '<a href="tel:' . implode("", $r[0]) . '" >' . $tel_n . "</a>\n";
    } else {
        $ret = $tel_n;
    }
    return $ret;
}

function gaz_html_ae_checkiva($paese, $pariva) {
    $htmlpariva = "<a target=\"_blank\" href=\"http://www1.agenziaentrate.gov.it/servizi/vies/vies.htm?s=" . $paese . "&p=" . $pariva . "\">" . $paese . " " . $pariva . "</a>";
    return $htmlpariva;
}

function gaz_format_quantity($number, $comma = false, $decimal = false) {
    $number = sprintf("%.3f", preg_replace("/\,/", '.', $number)); //max 3 decimal
    if (!$decimal) { // decimal is not defined (depreceted in recursive call)
        global $gTables;
        $config = gaz_dbi_get_row($gTables['aziend'], 'codice', 1);
        $decimal = $config['decimal_quantity'];
    }
    if ($decimal > 3) { //float
        if ($comma == true) {
            return preg_replace("/\./", ',', floatval($number));
        } else {
            return floatval($number);
        }
    } else { //decimal defined
        if ($comma == true) {
            return number_format($number, $decimal, ',', '.');
        } else {
            return number_format($number, $decimal, '.', '');
        }
    }
}

function gaz_set_time_limit($time) {
    global $disable_set_time_limit;
    if (!$disable_set_time_limit) {
        set_time_limit($time);
    }
}

function CalcolaImportoRigo($quantita, $prezzo, $sconto, $decimal = 2) {
    if (is_array($sconto)) {
        $res = 1;
        foreach ($sconto as $val) {
            $res -= $res * $val / 100;
        }
        $res = 1 - $res;
    } else {
        $res = $sconto / 100;
    }
    return round($quantita * ($prezzo - $prezzo * $res), $decimal);
}

//
// La funzione table_prefix_ok() serve a determinare se il prefisso
// delle tabelle è valido, secondo lo schema di Gazie, oppure no.
// In pratica, si verifica che inizi con la stringa `gaz' e può
// continuare con lettere minuscole e cifre numeriche, fino
// a un massimo di ulteriori nove caratteri
//
function table_prefix_ok($table_prefix) {
    if (preg_match("/^[g][a][z][a-z0-9]{0,9}$/", $table_prefix) == 1) {
        return TRUE;
    } else {
        return FALSE;
    }
}

//
// La funzione table_prefix_get() serve a estrapolare il prefisso
// del nome di una tabella di Gazie, usando le stesse regole
// della funzione table_prefix_ok() per tale individuazione.
// Il riconoscimenti si basa soprattutto sul fatto che il prefisso
// dei nomi delle tabelle non possa contenere il trattino basso.
//
// ATTENZIONE: il funzionamento corretto di questa funzione
//             è ancora da verificare e viene aggiunta solo
//             come suggerimento, in abbinamento alla funzione
//             table_prefix_ok().
//
function table_prefix_get($table_name) {
    $matches;
    if (preg_match("/^([g][a][z][a-z0-9]{0,9})[_]/", $table_name, $matches) == 1) {
        return $matches[1];
    } else {
        return "";
    }
}

//
// Una funzione per segnalare errori fatali in modo molto semplice.
//
function message_fatal_error($text) {
    echo '<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//IT\" \"http://www.w3.org/TR/html4/loose.dtd\">
   			<html>
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
					<meta name="author" content="Antonio De Vincentiis http://www.devincentiis.it">
					<link rel="stylesheet" type="text/css" href="../../library/style/stylesheet.css">
					<link rel="shortcut icon" href="../../library/images/favicon.ico">
					<title>Fatal error</title>
				</head>
				<body>
					<h1>Fatal error</h1>
					<p><strong>' . $text . '</strong></p>
				</body>
			</html>';
}

/**
 * crea la select per le destinazioni
 */
function selectDestinazione($rs_destinazioni) {
    $retVal = "";
    if (count($rs_destinazioni) > 0) {
        $retVal = $retVal . "<select name=\"destina\" class=\"FacetSelect\" onchange=\"cambiaDestinazione(this)\">\n";
        $retVal = $retVal . "<option value=\"\" selected>-------</option>\n";
        foreach ($rs_destinazioni as $dest) {
            $destinazione = //getStringaNonVuota($dest['codice'], "-")
                    getStringaNonVuota($dest['unita_locale1'], "\n")
                    . getStringaNonVuota($dest['unita_locale2'], "\n")
                    . getStringaNonVuota($dest['indspe'], "\n")
                    . getStringaNonVuota($dest['capspe'], " ")
                    . getStringaNonVuota($dest['citspe'], " - ")
                    . getStringaNonVuota($dest['prospe'], " - ")
                    . getStringaNonVuota($dest['country']);

            $retVal = $retVal . "<option value=\"" . $destinazione . "\">"
                    . $destinazione
                    . "</option>\n";
        }
        $retVal = $retVal . "</select><p>\n";
    }
    return $retVal;
}

function getStringaNonVuota($stringa, $daAggiungere = "") {
    $returnVal = "";
    if (!empty($stringa)) {
        $returnVal = $returnVal . $stringa . $daAggiungere;
    }
    return $returnVal;
}

function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

function alert($message) {
// This is in the PHP file and sends a Javascript alert to the client

    echo "<script type='text/javascript'>alert('$message');</script>";
}

function windowsClose() {
// This is in the PHP file and sends a Javascript alert to the client

    echo "<script type='text/javascript'>window.close();</script>";
}

function tornaPaginaPrecedente() {
    echo "<script type='text/javascript'>javascript:history.go(-1);</script>";
}

function isDDT($tipdoc) {
    return (startsWith($tipdoc, 'DD') || $tipdoc == "RDV");
}

function msgDebug($txt, $titolo = "debug message") {
    $nomeFile = dirname(__FILE__) . "/php.log";
    error_log("\n***************** $titolo *****************"
            . "\n" . $txt . "\n&&&&&&&&", 3, $nomeFile);
}

class selectAgente extends SelectBox {

    private $tipo;

    public function __construct($name, $tipo = "C") {
        parent::__construct($name);
        $this->tipo = $tipo;
    }

    function output($class = '') {
        if ($this->tipo == "C") {
            $nomeTabella = 'agenti';
        } else {
            $nomeTabella = 'agenti_forn';
        }
        global $gTables;
        $query = "SELECT " . $gTables[$nomeTabella] . ".id_agente," . $gTables[$nomeTabella] . ".id_fornitore," . $gTables['anagra'] . ".ragso1," . $gTables['clfoco'] . ".codice
                  FROM " . $gTables[$nomeTabella] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables[$nomeTabella] . ".id_fornitore = " . $gTables['clfoco'] . ".codice
                  LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra = " . $gTables['anagra'] . ".id";
        SelectBox::_output($query, 'ragso1', True, '', '', "id_agente", '', $class);
    }

}

class Config {

    function __construct() {
        global $gTables;
        $results = gaz_dbi_query("SELECT variable, cvalue FROM " . $gTables['config']);
        while ($row = gaz_dbi_fetch_object($results)) {
            $this->{$row->variable} = $row->cvalue;
        }
    }

    function getValue($variable) {
        return $this->{$variable};
    }

    function setValue($variable, $value = array('description' => '', 'cvalue' => '', 'show' => 0)) {
        /* in $variabile va sempre il nome della variabile, 
         * la tabella viene aggiornata ne caso in cui il nome variabile esiste mentre 
         * viene inserita qualora non esista.  
         * In caso di inserimento è necessario passare un array in $value mentre in caso di
         * aggiornamento è sufficiente un valore */
        global $gTables;
        $variable = filter_var(substr($variable, 0, 100), FILTER_SANITIZE_STRING);
        $result = gaz_dbi_dyn_query("*", $gTables['config'], "variable='" . $variable . "'");
        if (gaz_dbi_num_rows($result) >= 1) { // è un aggiornamento
            if (is_array($value)) {
                $row = gaz_dbi_fetch_array($result);
                $value['cvalue'] = filter_var(substr($value['cvalue'], 0, 100), FILTER_SANITIZE_STRING);
                $this->{$variable} = $value['cvalue'];
                $value['variable'] = $variable;
                ;
                gaz_dbi_table_update('config', array('id', $row['id']), $value);
            } else {
                $this->{$variable} = filter_var(substr($value, 0, 100), FILTER_SANITIZE_STRING);
                gaz_dbi_put_row($gTables['config'], 'variable', $variable, 'cvalue', $value['cvalue']);
            }
        } else { // è un inserimento
            gaz_dbi_table_insert('config', $value);
        }
    }

}

class UserConfig {

    function __construct() {
        global $gTables;
        $results = gaz_dbi_query("SELECT var_name, var_value FROM " . $gTables['admin_config']);
        while ($row = gaz_dbi_fetch_object($results)) {
            $this->{$row->var_name} = $row->var_value;
        }
    }

    function getValue($variable) {
        return $this->{$variable};
    }

    function setValue($variable, $value = array('var_descri' => '', 'var_value' => '')) {
        /* in $variabile va sempre il nome della variabile, 
         * la tabella viene aggiornata ne caso in cui il nome variabile esiste mentre 
         * viene inserita qualora non esista.  
         * In caso di inserimento è necessario passare un array in $value mentre in caso di
         * aggiornamento è sufficiente un valore */
        global $gTables, $form;
        $variable = filter_var(substr($variable, 0, 100), FILTER_SANITIZE_STRING);
        $result = gaz_dbi_dyn_query("*", $gTables['admin_config'], "var_name='" . $variable . "'");
        if (gaz_dbi_num_rows($result) >= 1) { // è un aggiornamento
            if (is_array($value)) {
                $row = gaz_dbi_fetch_array($result);
                $value['var_value'] = filter_var(substr($value['var_value'], 0, 100), FILTER_SANITIZE_STRING);
                $this->{$variable} = $value['var_value'];
                $value['var_name'] = $variable;
                gaz_dbi_table_update('admin_config', array('id', $row['id']), $value);
            } else {
                $this->{$variable} = filter_var(substr($value, 0, 100), FILTER_SANITIZE_STRING);
                gaz_dbi_put_row($gTables['admin_config'], 'var_name', $variable, 'var_value', $value['var_value']);
            }
        } else { // è un inserimento
            gaz_dbi_table_insert('admin_config', $value);
        }
    }

    function setDefaultValue() {
        $this->setValue('LTE_Fixed', array("var_name" => "LTE_Fixed", "var_descri" => "Attiva lo stile fisso. Non puoi usare fisso e boxed insieme", "var_value" => "false"));
        $this->setValue('LTE_Boxed', array("var_name" => "LTE_Boxed", "var_descri" => "Attiva lo stile boxed", "var_value" => "false"));
        $this->setValue('LTE_Collapsed', array("var_name" => "LTE_Collapsed", "var_descri" => "Collassa il menu principale", "var_value" => "true"));
        $this->setValue('LTE_Onhover', array("var_name" => "LTE_Onhover", "var_descri" => "Espandi automaticamente il menu", "var_value" => "false"));
        $this->setValue('LTE_SidebarOpen', array("var_name" => "LTE_SidebarOpen", "var_descri" => "Mantieni la barra aperta", "var_value" => "false"));
    }

}

// end Config

class configTemplate {

    function __construct() {
        global $gTables;
        $row = gaz_dbi_get_row($gTables['aziend'], 'codice', $_SESSION['company_id']);
        $this->template = $row['template'];
    }

}

class Anagrafica {

    function __construct() {
        global $gTables;
        $this->gTables = $gTables;
        $this->partnerTables = $gTables['clfoco'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id';
    }

    function getPartner($idClfoco) {
        return gaz_dbi_get_row($this->partnerTables, "codice", $idClfoco);
    }

    function getPartnerData($idAnagra, $acc = 1) {
        global $table_prefix;
        $rs_co = gaz_dbi_dyn_query('codice', $this->gTables['aziend'], 1);
        $partner_data = array();
        $partner = array();
        while ($co = gaz_dbi_fetch_array($rs_co)) {
            $rs_partner = gaz_dbi_query('SELECT * FROM ' . $table_prefix . sprintf('_%03d', $co['codice']) . 'clfoco WHERE ' .
                    ' codice BETWEEN ' . $acc . '00000001 AND ' . $acc . '99999999 AND id_anagra =' . $idAnagra . '  LIMIT 1');
            $r_p = gaz_dbi_fetch_array($rs_partner);
            if ($r_p) {
                $r_p['id_aziend'] = $co['codice'];
                $partner_data[] = $r_p;
            }
        }
        if (sizeof($partner_data) == 0) {  // se non ci sono tra i partner omogenei controllo su tutti
            $rs_co = gaz_dbi_dyn_query('codice', $this->gTables['aziend'], 1);
            while ($co = gaz_dbi_fetch_array($rs_co)) {
                $rs_partner = gaz_dbi_query('SELECT * FROM ' . $table_prefix . sprintf('_%03d', $co['codice']) . 'clfoco WHERE ' .
                        ' id_anagra =' . $idAnagra . '  LIMIT 1');
                $r_p = gaz_dbi_fetch_array($rs_partner);
                if ($r_p) {
                    $r_p['id_aziend'] = $co['codice'];
                    $partner_data[] = $r_p;
                }
            }
        }
        if (sizeof($partner_data) == 0) { // e' un'anagrafica isolata inserisco una tabella vuota
            $partner_data[0] = gaz_dbi_fields('clfoco');
            $partner_data[0]['last_modified'] = 'isolated';
            $partner_data[0]['id_anagra'] = $idAnagra;
        }
        foreach ($partner_data as $k => $row) {
            $partner[$row['last_modified']] = $row;
        }
        ksort($partner);
        $r_a = gaz_dbi_get_row($this->gTables['anagra'], 'id', $idAnagra);
        $data = array_merge(array_pop($partner), $r_a);
        unset($data['codice']);
        return $data;
    }

    function queryPartners($select, $where = 1, $orderby = 2, $limit = 0, $passo = 1900000) {
        $result = gaz_dbi_dyn_query($select, $this->partnerTables, $where, $orderby, $limit, $passo);
        $partners = array();
        while ($row = gaz_dbi_fetch_array($result)) {
            $partners[] = $row;
        }
        return $partners;
    }

    function updatePartners($codice, $newValue) {
        $newValue['descri'] = $newValue['ragso1'] . ' ' . $newValue['ragso2'];
        gaz_dbi_table_update('clfoco', $codice, $newValue);
        gaz_dbi_table_update('anagra', array('id', $newValue['id_anagra']), $newValue);
    }

    function anagra_to_clfoco($v, $m) {
        $last_partner = gaz_dbi_dyn_query("*", $this->gTables['clfoco'], 'codice BETWEEN ' . $m . '000001 AND ' . $m . '999999', "codice DESC", 0, 1);
        $last = gaz_dbi_fetch_array($last_partner);
        if ($last) {
            $v['codice'] = $last['codice'] + 1;
        } else {
            $v['codice'] = $m . '000001';
        }
        $v['descri'] = $v['ragso1'];
        if (isset($v['ragso2'])) {
            $v['descri'] .= $v['ragso2'];
        }
        gaz_dbi_table_insert('clfoco', $v);
        return $v['codice'];
    }

    function insertPartner($v) {
        $v['descri'] = $v['ragso1'];
        if (isset($v['ragso2'])) {
            $v['descri'] .= $v['ragso2'];
        }
        gaz_dbi_table_insert('anagra', $v);
        $v['id_anagra'] = gaz_dbi_last_id();
        gaz_dbi_table_insert('clfoco', $v);
    }

    function deletePartner($idClfoco) {
        global $gTables;
        gaz_dbi_del_row($gTables['clfoco'], 'codice', $idClfoco);
    }

}

//===============================================================================
// classe generica per la generazione di select box
//================================================================================
class SelectBox {

    var $name;

    // assegno subito il nome della select box
    function __construct($name) {
        $this->name = $name;
    }

    function setSelected($selected) {
        $this->selected = $selected;
    }

    function addSelected($selected) {
        $this->setSelected($selected);
    }

    function _output($query, $index1, $empty = false, $bridge = '', $index2 = '', $key = 'codice', $refresh = '', $class = false) {
        if (!empty($refresh)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$refresh'; this.form.submit();\"";
        }
        $cl = 'FacetSelect';
        if ($class) {
            $cl = $class;
        }
        echo "\t <select id=\"$this->name\" name=\"$this->name\" class=\"$cl\" $refresh >\n";
        if ($empty) {
            echo "\t\t <option value=\"\"></option>\n";
        }
        $result = gaz_dbi_query($query);
        while ($a_row = gaz_dbi_fetch_array($result)) {
            $selected = "";
            if ($a_row[$key] == $this->selected) {
                $selected = "selected";
            }
            echo "\t\t <option value=\"" . $a_row[$key] . "\" $selected >";
            if (empty($index2)) {
                echo substr($a_row[$index1], 0, 43) . "</option>\n";
            } else {
                echo substr($a_row[$index1], 0, 38) . $bridge . substr($a_row[$index2], 0, 35) . "</option>\n";
            }
        }
        echo "\t </select>\n";
    }

}

// classe per la generazione di select box dei clienti e fornitori (partner commerciali)
class selectPartner extends SelectBox {

    function __construct($name) {
        global $gTables;
        $this->gTables = $gTables;
        $this->name = $name;
        $this->what = "a.id AS id,pariva,codfis,a.citspe AS citta, ragso1 AS ragsoc,
                     (SELECT " . $this->gTables['clfoco'] . ".codice FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1) AS codice,
                     (SELECT " . $this->gTables['clfoco'] . ".status FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1) AS status, 0 AS codpart ";
    }

    function setWhat($m) {
        $this->what = "a.id AS id,pariva,codfis,a.citspe AS citta, ragso1 AS ragsoc,
                     (SELECT " . $this->gTables['clfoco'] . ".codice FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra AND " . $this->gTables['clfoco'] . ".codice BETWEEN " . $m . "000001 AND " . $m . "999999 LIMIT 1) AS codpart ,
                     (SELECT " . $this->gTables['clfoco'] . ".codice FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1) AS codice,
                     (SELECT " . $this->gTables['clfoco'] . ".status FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1) AS status ";
    }

    function queryAnagra($where = 1) {
        $rs = gaz_dbi_dyn_query($this->what, $this->gTables['anagra'] . ' AS a', $where, "a.ragso1 ASC");
        $anagrafiche = array();
        while ($r = gaz_dbi_fetch_array($rs)) {
            $anagrafiche[] = $r;
        }
        return $anagrafiche;
    }

    function queryNomeAgente($id_agente) {
        $retVal = "";
        $rs = gaz_dbi_dyn_query("b.descri as nomeAgente", $this->gTables['agenti'] . ' AS a join ' . $this->gTables['clfoco'] . " as b on a.id_fornitore=b.codice ", "a.id_agente=$id_agente");
//        $anagrafiche = array();
        if ($r = gaz_dbi_fetch_array($rs)) {
            $retVal = $r["nomeAgente"];
        }
        return $retVal;
    }

    function output($mastro, $cerca) {
        global $script_transl;
        $msg = "";
        $put_anagra = '';
        $tabula = " tabindex=\"1\" ";
        if (strlen($cerca) >= 2) {
            if (is_numeric($cerca)) {                      //ricerca per partita iva
                $partners = $this->queryAnagra(" pariva = " . intval($cerca));
            } elseif (is_numeric(substr($cerca, 6, 2))) {   //ricerca per codice fiscale
                $partners = $this->queryAnagra(" a.codfis LIKE '%" . addslashes($cerca) . "%'");
            } else {                                      //ricerca per ragione sociale
                $partners = $this->queryAnagra(" a.ragso1 LIKE '" . addslashes($cerca) . "%'");
            }
            $numclfoco = sizeof($partners);
            if ($numclfoco > 0) {
                $tabula = " ";
                echo "\t <select name=\"$this->name\" class=\"FacetSelect\">\n";
                while (list($key, $a_row) = each($partners)) {
                    $selected = "";
                    $style = '';
                    if ($a_row["codice"] == $this->selected) {
                        $selected = "selected";
                        if ($a_row["codice"] < 1) {
                            $put_anagra = "\t<input type=\"hidden\" name=\"put_anagra\" value=\"" . $a_row['id'] . "\">\n";
                        }
                    }
                    if ($a_row["codice"] < 1) {
                        $style = 'style="background:#FF0000";';
                    }
                    echo "\t\t <option value=\"" . $a_row["codice"] . "\" $selected $style>" . $a_row["ragsoc"] . "&nbsp;" . $a_row["citta"] . "</option>\n";
                }
                echo "\t </select>\n";
            } else {
                $msg = $script_transl['notfound'] . "!\n";
                echo "\t<input type=\"hidden\" name=\"$this->name\" value=\"\">\n";
            }
        } else {
            $msg = $script_transl['minins'] . " 2 " . $script_transl['charat'] . "!\n";
            echo "\t<input type=\"hidden\" name=\"$this->name\" value=\"\">\n";
        }
        echo $put_anagra;
        echo "\t<input type=\"text\" name=\"ragso1\" " . $tabula . " accesskey=\"e\" value=\"" . $cerca . "\" maxlength=\"15\" size=\"9\" class=\"FacetInput\">\n";
        echo $msg;
        //echo "\t<input type=\"image\" align=\"middle\" accesskey=\"c\" " . $tabula . " name=\"clfoco\" src=\"../../library/images/cerbut.gif\" title=\"" . $script_transl['search'] . "\">\n";
        /** ENRICO FEDELE */
        /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
        echo '<button type="submit" class="btn btn-default btn-sm" accesskey="c" name="clfoco" ' . $tabula . ' title="' . $script_transl['search'] . '"><i class="glyphicon glyphicon-search"></i></button>';
        /** ENRICO FEDELE */
    }

    function selectDocPartner($name, $val, $strSearch = '', $val_hiddenReq = '', $mesg, $m = 0, $anonimo = -1, $tab = 1, $soloMastroSelezionato = false) {
        /* se passo $m=-1 ottengo tutti i partner nel piano dei conti indistintamente
          passare false su $tab se non si vuole la tabulazione
          $soloMastroSelezionato = true se si vogliono visualizzare solo i clienti (o i fornitori) in base a $m
         */
        global $gTables;
        $tab1 = '';
        $tab2 = '';
        $tab3 = '';
        if ($tab) {
            $tab1 = ' tabindex="' . $tab . '"';
            $tab2 = ' tabindex="' . ($tab + 1) . '"';
            $tab3 = ' tabindex="' . ($tab + 2) . '"';
        }
        if ($val > 100000000) { //vengo da una modifica della precedente select case quindi non serve la ricerca
            $partner = gaz_dbi_get_row($gTables['clfoco'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id', "codice", $val);
            echo "\t<input type=\"submit\" value=\"⇒\" name=\"fantoccio\" disabled>\n";
            echo "\t<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$val\">\n";
            echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner['ragso1'], 0, 8) . "\">\n";
            echo "\t<input type=\"submit\" tabindex=\"999\" value=\"" . $partner['ragso1'] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
        } elseif (preg_match("/^id_([0-9]+)$/", $val, $match)) { // e' stato selezionata la sola anagrafica
            $partner = gaz_dbi_get_row($gTables['anagra'], 'id', $match[1]);
            echo "\t<input type=\"submit\" value=\"⇒\" name=\"fantoccio\" disabled>\n";
            echo "\t<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$val\">\n";
            echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner['ragso1'], 0, 8) . "\">\n";
            echo "\t<input type=\"submit\" tabindex=\"999\" style=\"background:#FFBBBB\"; value=\"" . $partner['ragso1'] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
        } elseif ($val == $anonimo) { // e' un cliente anonimo
            echo "\t<input type=\"submit\" value=\"⇒\" name=\"fantoccio\" disabled>\n";
            echo "\t<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$val\">\n";
            echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"\">\n";
            echo "\t<input type=\"submit\" tabindex=\"999\" value=\"" . $mesg[5] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
        } else {
            if (strlen($strSearch) >= 2) { //sto ricercando un nuovo partner
                if ($m > 100) { //ho da ricercare nell'ambito di un mastro
                    $this->setWhat($m);
                }
                if (is_numeric($strSearch)) {                      //ricerca per partita iva
                    $partner = $this->queryAnagra(" pariva = " . intval($strSearch));
                } elseif (substr($strSearch, 0, 1) == '@') { //ricerca conoscendo il codice cliente
                    $temp_agrafica = new Anagrafica();
                    $codicetemp = intval($m * 1000000 + substr($strSearch, 1));
                    $last = $temp_agrafica->getPartner($codicetemp);
                    $codicecer = $last['id_anagra'];
                    $partner = $this->queryAnagra(" a.id = " . intval($codicecer));
                    //echo "---".$m."-".$codicetemp."-".$codicecer; //debug
                } elseif (substr($strSearch, 0, 1) == '#') { //ricerca conoscendo il codice univoco ufficio
                    $partner = $this->queryAnagra(" a.fe_cod_univoco LIKE '%" . addslashes(substr($strSearch, 1)) . "%'");
                } elseif (is_numeric(substr($strSearch, 6, 2))) {   //ricerca per codice fiscale
                    $partner = $this->queryAnagra(" a.codfis LIKE '%" . addslashes($strSearch) . "%'");
                } else {                                      //ricerca per ragione sociale
                    $partner = $this->queryAnagra(" a.ragso1 LIKE '" . addslashes($strSearch) . "%'");
                }
                if (count($partner) > 0) {
                    echo "\t<select name=\"$name\" $tab1 class=\"FacetSelect\" onchange=\"if(typeof(this.form.hidden_req)!=='undefined'){this.form.hidden_req.value='$name';} this.form.submit();\">\n";
                    echo "<option value=\"0\"> ---------- </option>";
                    if ($anonimo > 100) {
                        echo "<option value=\"$anonimo\">" . $mesg[5] . "</option>";
                    }
                    preg_match("/^id_([0-9]+)$/", $val, $match);
                    foreach ($partner as $r) {
                        if ($r['codpart'] > 0) {
                            $r['codice'] = $r['codpart'];
                        }
                        $style = '';
                        $selected = '';
                        $disabled = '';
                        if ($r['status'] == 'HIDDEN') {
                            $disabled = ' disabled ';
                        }
                        if (isset($match[1]) && $match[1] == $r['id']) {
                            $selected = "selected";
                        } elseif ($r['codice'] == $val && $val > 0) {
                            $selected = "selected";
                        }
                        if ($m < 0) { // vado cercando tutti i partner del piano dei conti
                            if ($r["codice"] < 1) {  // disabilito le anagrafiche presenti solo in altre aziende
                                $disabled = ' disabled ';
                                $style = 'style="background:#FF6666";';
                            }
                        } elseif ($r["codice"] < 1) {
                            $style = 'style="background:#FF6666";';
                            $r['codice'] = 'id_' . $r['id'];
                        } elseif (substr($r["codice"], 0, 3) != $m) {// non appartiene al mastro passato in $m
                            /** inizio modifica FP 28/11/2015 */
                            if ($soloMastroSelezionato) { // voglio solo le anagrafi di questo mastro
                                continue;   // salto questa riga
                            }
                            /** fine modifica FP */
                            $style = 'style="background:#FFBBBB";';
                            $r['codice'] = 'id_' . $r['id'];
                        }
                        echo "\t\t <option $style value=\"" . $r['codice'] . "\" $selected $disabled>" . $r["ragsoc"] . " " . $r["citta"] . "</option>\n";
                    }
                    echo "\t </select>\n";
                } else {
                    $msg = $mesg[0];
                    echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
                }
            } else {
                $msg = $mesg[1];
                echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
            }
            echo "\t<input type=\"text\" $tab2 id=\"search_$name\" name=\"search[$name]\" value=\"" . $strSearch . "\" maxlength=\"15\" size=\"9\" class=\"FacetInput\">\n";
            if (isset($msg)) {
                echo "<input type=\"text\" style=\"color: red; font-weight: bold;\" size=\"" . strlen($msg) . "\" disabled value=\"$msg\">\n";
            }
            //echo "\t<input type=\"image\" $tab3 align=\"middle\" name=\"search_str\" src=\"../../library/images/cerbut.gif\">\n";
            /** ENRICO FEDELE */
            /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
            echo '<button type="submit" class="btn btn-default btn-sm" name="search_str" ' . $tab3 . '><i class="glyphicon glyphicon-search"></i></button>';
            /** ENRICO FEDELE */
        }
    }

    function selectAnagra($name, $val, $strSearch = '', $val_hiddenReq = '', $mesg, $tab = false, $where = 1) {
        global $gTables;
        $tab1 = '';
        $tab2 = '';
        $tab3 = '';
        if ($tab) {
            $tab1 = ' tabindex="' . $tab . '"';
            $tab2 = ' tabindex="' . ($tab + 1) . '"';
            $tab3 = ' tabindex="' . ($tab + 2) . '"';
        }
        if ($val > 1) { //vengo da una modifica della precedente select case quindi non serve la ricerca
            $partner = gaz_dbi_get_row($gTables['anagra'], "id", $val);
            echo "\t<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$val\">\n";
            echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner['ragso1'], 0, 8) . "\">\n";
            echo "\t<input type=\"submit\" tabindex=\"999\" value=\"" . $partner['ragso1'] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
        } else {
            if (strlen($strSearch) >= 2) { //sto ricercando un nuovo partner
                if (is_numeric($strSearch)) {                      //ricerca per partita iva
                    $partner = $this->queryAnagra(" pariva = " . intval($strSearch) . " and $where");
                } elseif (is_numeric(substr($strSearch, 6, 2))) {   //ricerca per codice fiscale
                    $partner = $this->queryAnagra(" a.codfis LIKE '%" . addslashes($strSearch) . "%' and $where");
                } else {                                      //ricerca per ragione sociale
                    $partner = $this->queryAnagra(" a.ragso1 LIKE '" . addslashes($strSearch) . "%' and $where");
                }
                if (count($partner) > 0) {
                    echo "\t<select name=\"$name\" $tab1 class=\"FacetSelect\" onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\">\n";
                    echo "<option value=\"0\"> ---------- </option>";
                    foreach ($partner as $r) {
                        $style = '';
                        $selected = '';
                        if ($r['codice'] == $val && $val > 0) {
                            $selected = "selected";
                        }
                        echo "\t\t <option $style value=\"" . $r['id'] . "\" $selected >" . $r["ragsoc"] . " " . $r["citta"] . "</option>\n";
                    }
                    echo "\t </select>\n";
                } else {
                    $msg = $mesg[0];
                    echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
                }
            } else {
                $msg = $mesg[1];
                echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
            }
            echo "\t<input type=\"text\"  $tab2  name=\"search[$name]\" value=\"" . $strSearch . "\" maxlength=\"15\" size=\"9\" class=\"FacetInput\">\n";
            if (isset($msg)) {
                echo "<input type=\"text\" style=\"color: red; font-weight: bold;\" size=\"" . strlen($msg) . "\" disabled value=\"$msg\">";
            }
            //echo "\t<input type=\"image\"  $tab3  align=\"middle\" name=\"search_str\" src=\"../../library/images/cerbut.gif\">\n";
            /** ENRICO FEDELE */
            /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
            echo '<button type="submit" class="btn btn-default btn-sm" name="search_str" ' . $tab3 . '><i class="glyphicon glyphicon-search"></i></button>';
            /** ENRICO FEDELE */
        }
    }

    function queryClfoco($codiceAnagrafe, $mastro) {
        $retVal = 0;
        $codiceAnagrafe = addslashes($codiceAnagrafe);
//      $where = "id_anagra='$codiceAnagrafe' and codice like '$mastro%'";
        $where = "codice='$codiceAnagrafe'";
        $rs = gaz_dbi_dyn_query('codice', $this->gTables['clfoco'] . ' AS a', $where);
        if ($r = gaz_dbi_fetch_array($rs)) {
            $retVal = $r['codice'];
        }
        return $retVal;
    }

}

// classe per la generazione di select box degli articoli
class selectartico extends SelectBox {

    function output($cerca, $field = 'C', $class = 'FacetSelect') {
        global $gTables, $script_transl, $script_transl;
        $msg = "";
        $tabula = ' tabindex="4" ';
        $opera = "%'";
        if (strlen($cerca) >= 1) {
            $opera = "'"; ////
            $field_sql = 'codice';
            if (substr($cerca, 0, 1) == "@") {
                $cerca = substr($cerca, 1);
            }
            $result = gaz_dbi_dyn_query("codice,descri,barcode", $gTables['artico'], $field_sql . " LIKE '" . addslashes($cerca) . $opera, "descri DESC");
            // $result = gaz_dbi_dyn_query("codice,descri,barcode", $gTables['artico'], "codice LIKE '" . addslashes($cerca) . $opera, "descri DESC");
            $numclfoco = gaz_dbi_num_rows($result);
            if ($numclfoco > 0) {
                $tabula = "";
                echo ' <select tabindex="4" name="' . $this->name . '" class="' . $class . '">';
                while ($a_row = gaz_dbi_fetch_array($result)) {
                    $selected = "";
                    if ($a_row["codice"] == $this->selected) {
                        $selected = ' selected=""';
                    }
                    echo ' <option value="' . $a_row["codice"] . '"' . $selected . '>' . $a_row["codice"] . '-' . $a_row["descri"] . '</option>';
                }
                echo ' </select>';
            } else {
                $msg = $script_transl['notfound'] . '!';
                echo '<input type="hidden" name="' . $this->name . '" value="" />';
            }
        } else {
//            $msg = $script_transl['minins'] . ' 1 ' . $script_transl['charat'] . '!';
            $msg = $script_transl['minins'] . ' 2 ' . $script_transl['charat'] . '!';
            echo '<input type="hidden" name="' . $this->name . '" value="" />';
        }
        //echo "\t<input type=\"text\" name=\"cosear\" id=\"search_cosear\" value=\"".$cerca."\" ".$tabula." maxlength=\"16\" size=\"9\" class=\"FacetInput\">\n";
        echo '&nbsp;<input type="text" class="' . $class . '" name="cosear" id="search_cosear" value="' . $cerca . '" ' . $tabula . ' maxlength="16" />';
        //echo "<font style=\"color:#ff0000;\">$msg </font>";
        if ($msg != "") {
            echo '&nbsp;<span class="bg-danger text-danger"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>' . $msg . '</span>';
        }
    }

}

// classe per la generazione di select box dei conti ricavi di vendita-costi d'acquisto
class selectconven extends SelectBox {

    function output($mastri, $class = false, $empty = false) {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['clfoco'] . "` WHERE codice LIKE '" . $mastri . "%' AND codice NOT LIKE '%000000' ORDER BY `codice` ASC";
        SelectBox::_output($query, 'codice', $empty, '-', 'descri', 'codice', '', $class);
    }

}

// classe per la generazione di select box dei conti ricavi di vendita-costi d'acquisto
class selectbanacc extends SelectBox {

    function output($mastri) {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['clfoco'] . "` WHERE codice LIKE '" . $mastri . "%' AND codice > '" . $mastri . "000000' ORDER BY `codice` ASC";
        SelectBox::_output($query, 'codice', True, '-', 'ragso1');
    }

}

// classe per la generazione di select box banche d'appoggio
class selectbanapp extends SelectBox {

    function output() {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['banapp'] . '` ORDER BY `descri`';
        SelectBox::_output($query, 'descri', True, ' ', 'locali');
    }

}

// classe per la generazione di select box dei pagamenti
class selectpagame extends SelectBox {

    function output($refresh = '', $class = false, $empty = true) {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['pagame'] . '` ORDER BY `descri`, `codice`';
        SelectBox::_output($query, 'descri', $empty, '', '', 'codice', $refresh, $class);
    }

}

// classe per la generazione di select box delle aliquote iva
class selectaliiva extends SelectBox {

    function output($class = false, $tipiva = false) {
        global $gTables;
        $where = '';
        if ($tipiva) {
            $where = " WHERE tipiva='" . $tipiva . "'";
        }
        $query = 'SELECT * FROM `' . $gTables['aliiva'] . '`' . $where . ' ORDER BY `codice`';
        SelectBox::_output($query, 'descri', True, '', '', 'codice', '', $class);
    }

}

// classe per la generazione di select box delle categorie merceologiche
class selectcatmer extends SelectBox {

    function output($refresh = '') {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['catmer'] . '` ORDER BY `codice`';
        SelectBox::_output($query, 'codice', True, '-', 'descri', 'codice', $refresh);
    }

}

// classe per la generazione di select box porto resa
class selectportos extends SelectBox {

    function output() {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['portos'] . '` ORDER BY `codice`';
        SelectBox::_output($query, 'codice', True, '-', 'descri');
    }

}

// classe per la generazione di select box delle spedizioni
class selectspediz extends SelectBox {

    function output() {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['spediz'] . '` ORDER BY `codice`';
        SelectBox::_output($query, 'codice', True, '-', 'descri');
    }

}

// classe per la generazione di select box imballi
class selectimball extends SelectBox {

    function output() {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['imball'] . '` ORDER BY `codice`';
        SelectBox::_output($query, 'codice', True, '-', 'descri');
    }

}

// classe per la generazione di select box imballi, spedizioni, porto resa
class SelectValue extends SelectBox {

    function output($table, $fieldName) {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables[$table] . '` ORDER BY `codice`';
        $index1 = 'codice';
        $empty = True;
        $bridge = '&nbsp; ';
        $index2 = 'descri';
        echo "\t <select name=\"$this->name\" class=\"FacetSelect\" onChange=\"pulldown_menu('" . $this->name . "','" . $fieldName . "')\" style=\"width: 20px\">\n";
        if ($empty) {
            echo "\t\t <option value=\"\"></option>\n";
        }
        $result = gaz_dbi_query($query);
        while ($a_row = gaz_dbi_fetch_array($result)) {
            if ($index2 == '') {
                echo "\t\t <option value=\"\">" . $a_row[$index1] . "</option>\n";
            } else {
                echo "\t\t <option value=\"" . $a_row[$index2] . "\">&nbsp;" . $a_row[$index1] . $bridge . $a_row[$index2] . "</option>\n";
            }
        }
        echo "\t </select>\n";
    }

}

// classe per la generazione di select box vettori
class selectvettor extends SelectBox {

    function output() {
        global $gTables;
        echo "\t <select name=\"$this->name\" class=\"FacetSelect\">\n";
        echo "\t\t <option value=\"\"></option>\n";
        $result = gaz_dbi_dyn_query("*", $gTables['vettor'], 1, "codice");
        while ($a_row = gaz_dbi_fetch_array($result)) {
            $selected = "";
            if ($a_row["codice"] == $this->selected) {
                $selected = "selected";
            }
            echo "\t\t <option value=\"" . $a_row["codice"] . "\" $selected >" . substr($a_row["ragione_sociale"], 0, 22) . "</option>\n";
        }
        echo "\t </select>\n";
    }

}

// classe per l'invio di documenti allegati ad una e-mail
class GAzieMail {

    function sendMail($admin_data, $user, $content, $partner) {
        global $gTables;

        require_once "../../library/phpmailer/class.phpmailer.php";
        require_once "../../library/phpmailer/class.smtp.php";
        //
        //
        // Si procede con la costruzione del messaggio.
        //
        // definisco il server SMTP e il mittente
        $config_mailer = gaz_dbi_get_row($gTables['company_config'], 'var', 'mailer');
        $config_host = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_server');
        $config_notif = gaz_dbi_get_row($gTables['company_config'], 'var', 'return_notification');
        $config_port = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_port');
        $config_secure = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_secure');
        $config_user = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_user');
        $config_pass = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_password');
        $config_replyTo = gaz_dbi_get_row($gTables['company_config'], 'var', 'reply_to');
        // attingo il contenuto del corpo della email dall'apposito campo della tabella configurazione utente
        $user_text = gaz_dbi_get_row($gTables['admin_config'], 'var_name', "body_send_doc_email' AND adminid = '" . $user['Login']);
        $company_text = gaz_dbi_get_row($gTables['company_config'], 'var', 'company_email_text');
        $admin_data['web_url'] = trim($admin_data['web_url']);
        $mailto = $partner['e_mail']; //recipient
        $subject = $admin_data['ragso1'] . " " . $admin_data['ragso2'] . " - Trasmissione documenti"; //subject
        // aggiungo al corpo  dell'email
        $body_text = "<h3><span style=\"color: #000000; background-color: #" . $admin_data['colore'] . ";\">" . $admin_data['ragso1'] . " " . $admin_data['ragso2'] . "</span></h3>";
        $body_text .= ( empty($admin_data['web_url']) ? "" : "<h4><span style=\"color: #000000;\">Web: <a href=\"" . $admin_data['web_url'] . "\">" . $admin_data['web_url'] . "</a></span></h4>" );
        $body_text .= "<div>" . $company_text['val'] . "</div>\n";
        $body_text .= "<address><div style=\"color: #" . $admin_data['colore'] . ";\">" . $user['Nome'] . " " . $user['Cognome'] . "</div>\n";
        $body_text .= "<div>" . $user_text['var_value'] . "</div></address>\n";
        $body_text .= "<hr /><small>" . EMAIL_FOOTER . " " . GAZIE_VERSION . "</small>\n";
        //
        // Inizializzo PHPMailer
        //
        $mail = new PHPMailer();
        $mail->Host = $config_host['val'];
        $mail->IsHTML();                                // Modalita' HTML
        $mail->CharSet = 'UTF-8';
        // Imposto il server SMTP
        if (!empty($config_port['val'])) {
            $mail->Port = $config_port['val'];             // Imposto la porta del servizio SMTP
        }
        switch ($config_mailer['val']) {
            case "smtp":
                // Invio tramite protocollo SMTP
                $mail->SMTPDebug = 2;                           // Attivo il debug
                $mail->IsSMTP();                                // Modalita' SMTP
                if (!empty($config_secure['val'])) {
                    $mail->SMTPSecure = $config_secure['val']; // Invio tramite protocollo criptato
                } else {
                    $mail->SMTPOptions = array('ssl' => array('verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true));
                }
                $mail->SMTPAuth = (!empty($config_user['val']) && $config_mailer['val'] == 'smtp' ? TRUE : FALSE );
                if ($mail->SMTPAuth) {
                    $mail->Username = $config_user['val'];     // Imposto username per autenticazione SMTP
                    $mail->Password = $config_pass['val'];     // Imposto password per autenticazione SMTP
                }
                break;
            case "mail":
            default:
                break;
        }
        /* Imposto email a cui rispondere (se è stata impostata nella tabella gaz_xxxcompany_config`)
         * deve stare prima di $mail->SetFrom perchè altrimenti aggiunge il from al reply
         */
        if (isset($config_replyTo) && !empty($config_replyTo['val'])) {
            $mittente = $config_replyTo['val'];
//            $mail->AddReplyTo($config_replyTo['val']);
        } else {
            $mittente = $admin_data['e_mail'];
        }
        // Imposto eventuale richiesta di notifica
        if ($config_notif['val'] == 'yes') {
            $mail->AddCustomHeader($mail->HeaderLine("Disposition-notification-to", $mittente));
        }
        $mail->setLanguage(strtolower($admin_data['country']));
        // Imposto email del mittente
        $mail->SetFrom($mittente, $admin_data['ragso1'] . " " . $admin_data['ragso2']);
        // Imposto email del destinatario
//$mailto="ironeman@tiscali.it";
        $mail->AddAddress($mailto);
        // Aggiungo l'email del mittente tra i destinatari in cc
        $mail->AddCC($mittente, $admin_data['ragso1'] . " " . $admin_data['ragso2']);
        // Imposto l'oggetto dell'email
        $mail->Subject = $subject;
        // Imposto il testo HTML dell'email
        $mail->MsgHTML($body_text);
        // Aggiungo la fattura in allegato
        $mail->AddStringAttachment($content->string, $content->name, $content->encoding, $content->mimeType);
        $mail->SMTPDebug = false;
        // Invio...
        if ($mail->Send()) {
            echo "invio e-mail riuscito... <strong>OK</strong><br />mail send has been successful... <strong>OK</strong>"; // or use booleans here
        } else {
            echo "<br />invio e-mail <strong style=\"color: #ff0000;\">NON riuscito... ERROR!</strong><br />mail send has<strong style=\"color: #ff0000;\"> NOT been successful... ERROR!</strong> ";
            echo "<br />mailer error: " . $mail->ErrorInfo;
        }
    }

}

// classe per la generazione dinamica dei form di amministrazione
class GAzieForm {

    function outputErrors($idxMsg, $transl_errors) {
        /* In questa funzione si deve passare una striga dove il "+"
          serve a separare i diversi indici di errori e il "-" separa il riferimento
          all'errore es. "fa150-3+" dara' un risultato del genere:
          ERRORE! -> introdotto un valore negativo ¯fa150
         */
        global $script_transl;
        $message = '';
        if (!empty($idxMsg)) {
            $rsmsg = array_slice(explode('+', chop($idxMsg)), 0, -1);
            foreach ($rsmsg as $value) {
                $message .= $script_transl['error'] . "! -> ";
                $rsval = explode('-', chop($value));
                $k = array_pop($rsval);
                $message .= $transl_errors[$k] . ' ';
                foreach ($rsval as $valmsg) {
                    $message .= ' &raquo;' . $valmsg;
                }
                $message .= "<br />";
            }
        }
        return $message;
    }

    function Calendar($name, $day, $month, $year, $class = 'FacetSelect', $refresh = '') {
        if (!empty($refresh)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$refresh'; this.form.submit();\"";
        }

        echo "\t <select name=\"" . $name . "_D\" id=\"" . $name . "_D\" class=\"$class\" $refresh>\n";
        for ($i = 1; $i <= 31; $i++) {
            $selected = "";
            if ($i == $day) {
                $selected = "selected";
            }
            echo "\t\t <option value=\"$i\" $selected >$i</option>\n";
        }
        echo "\t </select>\n";
        echo "\t <select name=\"" . $name . "_M\" id=\"" . $name . "_M\" class=\"$class\" $refresh>\n";
        for ($i = 1; $i <= 12; $i++) {
            $selected = "";
            if ($i == $month) {
                $selected = "selected";
            }
            $month_name = ucwords(strftime("%B", mktime(0, 0, 0, $i, 1, 0)));
            echo "\t\t <option value=\"$i\"  $selected >$month_name</option>\n";
        }
        echo "\t </select>\n";
        echo "\t <select name=\"" . $name . "_Y\" id=\"" . $name . "_Y\" class=\"$class\" $refresh>\n";
        for ($i = $year - 10; $i <= $year + 10; $i++) {
            $selected = "";
            if ($i == $year) {
                $selected = "selected";
            }
            echo "\t\t <option value=\"$i\"  $selected >$i</option>\n";
        }
        echo "\t </select>\n";
    }

    function CalendarPopup($name, $day, $month, $year, $class = 'FacetSelect', $refresh = '') {
        global $script_transl;
        if (!empty($refresh)) {
            $refresh = ' onchange="this.form.hidden_req.value=\'' . $refresh . '\'; this.form.submit();"';
        }

        echo '<select name="' . $name . '_D" id="' . $name . '_D" class="' . $class . '"' . $refresh . '>';
        for ($i = 1; $i <= 31; $i++) {
            $selected = "";
            if ($i == $day) {
                $selected = ' selected=""';
            }
            echo '		<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
        }
        echo '	</select>
	  			<select name="' . $name . '_M" id="' . $name . '_M" class="' . $class . '"' . $refresh . '>';
        for ($i = 1; $i <= 12; $i++) {
            $selected = "";
            if ($i == $month) {
                $selected = ' selected=""';
            }
            $month_name = ucwords(strftime("%B", mktime(0, 0, 0, $i, 1, 0)));
            echo '		<option value="' . $i . '"' . $selected . '>' . $month_name . '</option>';
        }
        echo '</select>
	  		<input type="text" name="' . $name . '_Y" id="' . $name . '_Y" value="' . $year . '" class="' . $class . '"  maxlength="4" size="4"' . $refresh . ' />
	  		<a class="btn btn-default btn-sm" href="#" onClick="setDate(\'' . $name . '\'); return false;" title="' . $script_transl['changedate'] . '" name="anchor" id="anchor">
				<i class="glyphicon glyphicon-calendar"></i>
			</a>';
    }

    function variousSelect($name, $transl, $sel, $class = 'FacetSelect', $bridge = true, $refresh = '', $maxlenght = false, $style = '') {
        if (!empty($refresh)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$refresh'; this.form.submit();\"";
        }
        echo "<select name=\"$name\" id=\"$name\" class=\"$class\" $refresh $style>\n";
        foreach ($transl as $i => $val) {
            if ($maxlenght) {
                $val = substr($val, 0, $maxlenght);
            }
            $selected = '';
            if ($bridge) {
                $k = $i . ' -';
            } else {
                $k = '';
            }
            if ($sel == $i) {
                $selected = ' selected ';
            }
            echo "<option value=\"$i\"$selected>$k $val</option>\n";
        }
        echo "</select>\n";
    }

    function selCheckbox($name, $sel, $title = '', $refresh = '', $class = 'FacetSelect') {
        if (!empty($refresh)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$refresh'; this.form.submit();\"";
        }
        $selected = '';
        if ($sel == $name) {
            $selected = ' checked ';
        }
        echo "<input type=\"checkbox\" name=\"$name\" title=\"$title\" value=\"$name\" $selected $refresh>\n";
    }

    function selectNumber($name, $val, $msg = false, $min = 0, $max = 1, $class = 'FacetSelect', $val_hiddenReq = '', $style = '') {
        global $script_transl;
        $refresh = '';
        if (!empty($val_hiddenReq)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
        }
        echo "<select  name=\"$name\" id=\"$name\" class=\"$class\" $refresh $style>\n";
        for ($i = $min; $i <= $max; $i++) {
            $selected = '';
            $message = $i;
            if ($val == $i) {
                $selected = " selected ";
            }
            if ($msg && $i == 0) {
                $message = $script_transl['no'];
            }
            if ($msg && $i == 1) {
                $message = $script_transl['yes'];
            }
            echo "<option value=\"$i\"$selected>$message</option>\n";
        }
        echo "</select>\n";
    }

    function selectFromDB($table, $name, $key, $val, $order = false, $empty = false, $bridge = '', $key2 = '', $val_hiddenReq = '', $class = 'FacetSelect', $addOption = null, $style = '', $where = false) {
        global $gTables;
        $refresh = '';
        if (!$order) {
            $order = $key;
        }
        $query = 'SELECT * FROM `' . $gTables[$table] . '` ';
        if ($where) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY `' . $order . '`';
        if (!empty($val_hiddenReq)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
        }
        echo "\t <select id=\"$name\" name=\"$name\" class=\"$class\" $refresh $style>\n";
        if ($empty) {
            echo "\t\t <option value=\"\"></option>\n";
        }
        $result = gaz_dbi_query($query);
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            if ($r[$key] == $val) {
                $selected = "selected";
            }
            echo "\t\t <option value=\"" . $r[$key] . "\" $selected >";
            if (empty($key2)) {
                echo substr($r[$key], 0, 43) . "</option>\n";
            } else {
                echo substr($r[$key], 0, 28) . $bridge . substr($r[$key2], 0, 35) . "</option>\n";
            }
        }
        if ($addOption) {
            echo "\t\t <option value=\"" . $addOption['value'] . "\"";
            if ($addOption['value'] == $val) {
                echo " selected ";
            }
            echo ">" . $addOption['descri'] . "</option>\n";
        }
        echo "\t </select>\n";
    }

    // funzione per la generazione di una select box da file XML
    function selectFromXML($nameFileXML, $name, $key, $val, $empty = false, $val_hiddenReq = '', $class = 'FacetSelect', $addOption = null) {
        $refresh = '';
        if (file_exists($nameFileXML)) {
            $xml = simplexml_load_file($nameFileXML);
        } else {
            exit('Failed to open: ' . $nameFileXML);
        }
        if (!empty($val_hiddenReq)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
        }
        echo "\t <select id=\"$name\" name=\"$name\" class=\"$class\" $refresh >\n";
        if ($empty) {
            echo "\t\t <option value=\"\"></option>\n";
        }
        foreach ($xml->record as $v) {
            $selected = '';
            if ($v->field[0] == $val) {
                $selected = "selected";
            }
            echo "\t\t <option value=\"" . $v->field[0] . "\" $selected >&nbsp;" . $v->field[0] . " - " . $v->field[1] . "</option>\n";
        }
        if ($addOption) {
            echo "\t\t <option value=\"" . $addOption['value'] . "\"";
            if ($addOption['value'] == $val) {
                echo " selected ";
            }
            echo ">" . $addOption['descri'] . "</option>\n";
        }
        echo "\t </select>\n";
    }

    function selectAccount($name, $val, $type = 1, $val_hiddenReq = '', $tabidx = false, $class = 'FacetSelect', $opt = 'style="max-width: 350px;"', $mas_only = true) {
        global $gTables, $admin_aziend;
        $bg_class = Array(1 => "gaz-attivo", 2 => "gaz-passivo", 3 => "gaz-costi", 4 => "gaz-ricavi", 5 => "gaz-transitori",
            6 => "gaz-transitori", 7 => "gaz-transitori", 8 => "gaz-transitori", 9 => "gaz-transitori");
        if (!empty($val_hiddenReq)) {
            $opt = " onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\"";
        }
        if ($tabidx) {
            $opt .= " tabindex=" . $tabidx;
        }
        if (is_array($type)) { /* per cercare tra i mastri l'array deve contenere tutti i
          i primi numeri che si vogliono ovvero: 1=attivo,2=passivo,3=ricavi,4=costi, ecc
          se si vuole cercare tra i sottoconti allora il primo elemento
          dell'array deve contenere il valore "SUB"
         */
            $where = '';
            $first = true;
            $sub = false;
            foreach ($type as $v) {
                if (strtoupper($v) == 'SUB') {
                    $sub = true;
                    continue;
                }
                $where .= ($first ? "" : " OR ");
                $first = false;
                if ($sub) {
                    $where .= "codice BETWEEN " . intval(substr($v, 0, 1)) . "00000001 AND " . intval(substr($v, 0, 1)) . "99999999 AND codice NOT LIKE '" . $admin_aziend['mascli'] . "%' AND codice NOT LIKE '" . $admin_aziend['masfor'] . "%' AND codice NOT LIKE '%000000'";
                } else {
                    $where .= "codice LIKE '" . intval(substr($v, 0, 1)) . "__000000'";
                }
            }
        } elseif ($type > 99) { // se passo il mastro
            $type = sprintf('%03d', substr($type, 0, 3));
            $where = "codice BETWEEN " . $type . "000001 AND " . $type . "999999 AND codice NOT LIKE '%000000'";
        } else {
            $where = "codice BETWEEN " . $type . "00000001 AND " . $type . "99999999 AND codice NOT LIKE '" . $admin_aziend['mascli'] . "%' AND codice NOT LIKE '" . $admin_aziend['masfor'] . "%' AND codice NOT LIKE '%000000'";
        }
        echo "<select id=\"$name\" name=\"$name\" class=\"$class\" $opt>\n";
        echo "\t<option value=\"0\"> ---------- </option>\n";
        $result = gaz_dbi_dyn_query("codice,descri", $gTables['clfoco'], $where, "codice ASC");
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            $v = $r["codice"];
            $c = intval($v / 100000000);
            $selected .= ' class="' . $bg_class[$c] . '" ';
            if ((intval($type) > 99 || (is_array($type) && count($type) == 1)) && $mas_only) {
                $v = intval(substr($r["codice"], 0, 3));
            }
            if ($val == $v) {
                $selected .= " selected ";
            }
            echo "\t<option value=\"" . $v . "\"" . $selected . ">" . $r["codice"] . "-" . $r['descri'] . "</option>\n";
        }
        echo "</select>\n";
    }

    function selTypeRow($name, $val, $class = 'FacetDataTDsmall') {
        global $script_transl;
        $this->variousSelect($name, $script_transl['typerow'], $val, $class, true);
    }

    function selSearchItem($name, $val, $class = 'FacetDataTDsmall') {
        global $script_transl;
        $this->variousSelect($name, $script_transl['search_item'], $val, $class, true);
    }

    function gazHeadMessage($message, $transl, $type = 'err') {
        if (!empty($message)) {
            $m = 'ERROR';
            $c = 'alert-danger';
            if ($type == 'war') {
                $m = 'ATTENTION';
                $c = 'alert-warning';
            }
            echo '<div class="container">
			<div class="row alert ' . $c . ' fade in" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Chiudi">
					<span aria-hidden="true">&times;</span>
				</button>
				';
            foreach ($message as $v) {
                echo '<span class="glyphicon glyphicon-alert" aria-hidden="true"></span> ' . $m . '!=> ' . $transl[$v] . "<br>\n";
            }
            echo "</div>
		</div>\n";
        }
        return '';
    }

    function gazResponsiveTable($rows, $id = 'gaz-responsive-table') {
        /* in $row ci devono essere i righi con un array così formattato:
         * $rows[row][col]=array('title'=>'nome_colonna','value'=>'valore','type'=>'es_input','class'=>'classe_bootstrap',table_id=>'gaz-resposive_table')
         * */
        ?>
        <div class="panel panel-default" >
            <div id="<?php echo $id; ?>"  class="container-fluid">
                <table class="col-xs-12 table-responsive table-striped table-condensed cf">
                    <thead class="cf">
                        <tr class="bg-success">
                            <?php
                            // attraverso per la prima volta l'array del primo rigo allo scopo di scrivere il thead 
                            foreach ($rows[0] as $v) {
                                echo '<th class="' . $v['class'] . '">' . $v['head'] . "</th>";
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($rows as $col) {
                            echo '<tr>';
                            foreach ($col as $v) {
                                echo '<td data-title="' . $v['head'] . '" class="' . $v['class'] . '"';
                                if (isset($v['td_content'])) { // se ho un tipo diverso dal semplice 
                                    echo $v['td_content'];
                                }
                                echo '>' . $v['value'] . "&nbsp;</td>\n";
                            }
                            echo "</tr>\n";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

}

/* SEZIONE PER L'ORDINAMENTO DEI RECORD IN OUTPUT
  SONO IMPOSTATE TUTE LE VARIABILI NECESSARIE ALLA FUNZIONE gaz_dbi_dyn_query
  imposto le variabili di sessione con i valori di default */
if (!isset($_GET['flag_order'])) {
    $flag_order = '';
    $flagorpost = '';
}
if (!isset($_GET['auxil'])) {
    $auxil = "1";
}
if (!isset($limit)) {
    $limit = "0";
}
if (!isset($passo)) {
    $passo = "20";
}
if (!isset($field)) {
    $field = "2";
}
//flag di ordinamento ascendente e discendente
if (isset($_GET['flag_order']) && ($_GET['flag_order'] == "DESC")) {
    $flag_order = "ASC";
    $flagorpost = "DESC";
} elseif (isset($_GET['flag_order']) && ($_GET['flag_order'] <> "DESC")) {
    $flag_order = "DESC";
    $flagorpost = "ASC";
}
// se $PHP_SELF e' compreso nel referer (ricaricamento dalla stessa pagina), conservo tutte le variabili di
// sessione, altrimenti resetto $session['field'], $session['limit'], $session['passo'], $session['where'] e session['order']
if (!isset($_SERVER["HTTP_REFERER"])) {
    $_SERVER["HTTP_REFERER"] = "";
}
// If you only want to determine if a particular needle  occurs within haystack, use the faster and less memory intensive function strpos() instead
//if (!strstr ($_SERVER["HTTP_REFERER"],$_SERVER['PHP_SELF'])) {
if (!strpos($_SERVER["HTTP_REFERER"], $_SERVER['PHP_SELF'])) {
    $field = "2";  // valore che indica alla gaz_dbi_dyn_query che orderby non va usato
    $flag_order = "DESC"; // per default i dati piu' recenti sono i primi
    $limit = "0";
    $passo = "20";
    $orderby = $field . " " . $flag_order;
    $auxil = "1";
    $where = '1';
}
// imposto il nuovo campo per l'ordinamento
if (isset($_GET['auxil'])) {
    $auxil = $_GET['auxil'];
}
if (isset($_GET['field'])) {
    $field = $_GET['field'];
}
$orderby = $field . ' ' . $flag_order;
if (isset($_GET['limit'])) {
    $limit = $_GET['limit'];
}
// statement where di default = 1
if (!isset($_GET['where'])) {
    $where = "1";
} else {
    $where = $_GET['where'];
}

// classe che visualizza i pulsanti per la navigazione dei record
// input= tabella, session[where], limit e passo.
// calcola i valori da impostare sulla variabile limit per scorrere i record
// visualizza il numero totale di record e i pulsanti
class recordnav {

    var $table;
    var $where;
    var $limit;
    var $passo;
    var $last;

    function __construct($table, $where, $limit, $passo) {
        global $limit, $passo;
        $this->table = $table;
        $this->where = $where;
        $this->limit = $limit;
        $this->passo = $passo;
        // faccio il conto totale dei record selezionati dalla query
        $this->count = gaz_dbi_record_count($table, $where);
        $this->last = $this->count - ($this->count % $this->passo);
        //return $last;
    }

    function output() {
        global $flagorpost;
        global $field;
        global $auxil, $script_transl;
        global $datfat;
        global $datemi;
        $first = 0;
        $next = $this->limit + $this->passo;
        $prev = $this->limit - $this->passo;
        // se e' arrivato a fondo scala imposto il fermo
        if ($prev <= 0) {
            $prev = 0;
        }
        if ($next >= $this->last) {
            $next = $this->last;
        }
        if (($this->count) <= $this->passo) {
            // non visualizzo la barra di navigazione dei record
            echo "<div align=\"center\"><font class=\"FacetFormDataFont\">Num. record = $this->count</font></div>";
        } else {
            echo "<div align=\"center\"><font class=\"FacetFormDataFont\">Num. record = $this->count</font></div>";
            echo "<div align=\"center\">";
            echo "| << <a href=\"" . $_SERVER['PHP_SELF'] . "?field=" . $field . "&auxil=" . $auxil . "&flag_order=" . $flagorpost . "&limit=0" . "&datfat=" . $datfat . "&datemi=" . $datemi . "\" >" . ucfirst($script_transl['first']) . "</a> ";
            echo "| < <a href=\"" . $_SERVER['PHP_SELF'] . "?field=" . $field . "&auxil=" . $auxil . "&flag_order=" . $flagorpost . "&limit=$prev" . "&datfat=" . $datfat . "&datemi=" . $datemi . "\">" . ucfirst($script_transl['prev']) . "</a> ";
            echo "| <a href=\"" . $_SERVER['PHP_SELF'] . "?field=" . $field . "&auxil=" . $auxil . "&flag_order=" . $flagorpost . "&limit=$next" . "&datfat=" . $datfat . "&datemi=" . $datemi . "\">" . ucfirst($script_transl['next']) . "</a> > ";
            echo "| <a href=\"" . $_SERVER['PHP_SELF'] . "?field=" . $field . "&auxil=" . $auxil . "&flag_order=" . $flagorpost . "&limit=$this->last" . "&datfat=" . $datfat . "&datemi=" . $datemi . "\">" . ucfirst($script_transl['last']) . "</a> >> |";
            echo "</div>";
        }
    }

}

// classe per la creazione di headers cliccabili per l'ordinamento dei record
// accetta come parametro un array associativo composto dalle label e relativi campi del db
class linkHeaders {

    var $headers = array(); // label e campi degli headers

    function __construct($headers) {
        $this->headers = $headers;
        $this->align = false;
        $this->style = false;
    }

    function setAlign($align) { // funzione per settare l'allineamento del testo passando un array
        $this->align = $align;
    }

    function setStyle($style) { // funzione per settare uno stile particolare passando un array
        $this->style = $style;
    }

    function output() {
        global $flag_order, $script_transl, $auxil, $headers;
        $k = 0; // è l'indice dell'array dei nomi di campo 
        foreach ($this->headers as $header => $field) {
            $style = 'FacetFieldCaptionTD';
            $align = '';
            if ($this->align) { // ho settato i nomi dei campi del db
                $align = ' style="text-align:' . $this->align[$k] . ';" ';
            }
            if ($this->style) { // ho settato degli stili diversi
                $style = $this->style[$k];
            }
            if ($field <> "") {
                echo "\t<th class=\"$style\" $align ><a href=\"" . $_SERVER['PHP_SELF'] . "?field=" . $field . "&flag_order=" . $flag_order . "&auxil=" . $auxil . "\" title=\"" . $script_transl['order'] . $header . "\">" . $header . "</a></th>\n\r";
            } else {
                echo "\t<th class=\"$style\" $align >" . $header . "</th>\n\r";
            }
            $k++;
        }
    }

}

function cleanMemberSession($abilit, $login, $password, $count, $company_id, $table_prefix) {
    global $gTables;
    $_SESSION["Abilit"] = true;
    $_SESSION["Login"] = $login;
    $_SESSION["Password"] = $password;
    $_SESSION["logged_in"] = true;
    $_SESSION["company_id"] = $company_id;
    $_SESSION["table_prefix"] = $table_prefix;
    /* appoggio il valore del thema scelto sulla sessione così da non fare la query sul db ad ogni richiesta di esecuzione di qualsiasi script  
     * però se vengo da un vecchio database non ho la tabella gaz_admin_config allora imposterò il valore di default anche per evitare l'errore
     * sulla query  
     */
    $result = gaz_dbi_query("SHOW TABLES LIKE '" . $gTables['admin_config'] . "'");
    if (gaz_dbi_num_rows($result) > 0) {
        $admin_config_theme = gaz_dbi_get_row($gTables['admin_config'], 'var_name', "theme' AND adminid = '" . $login);
        $_SESSION["theme"] = $admin_config_theme['var_value'];
    } else {
        $_SESSION["theme"] = 'g7';
    }
    $count++;
    //incremento il contatore d'accessi
    gaz_dbi_put_row($gTables['admin'], "Login", $login, "Access", $count);
    //modifico l'ultimo IP
    gaz_dbi_put_row($gTables['admin'], "Login", $login, 'last_ip', $_SERVER['REMOTE_ADDR']);
    /*  se sul file config/config/gconfig.php scelgo di comunicare ad un hosting d'appoggio 
      il mio eventuale nuovo IP DINAMICO del router ADSL faccio un ping ad esso così altri utenti
      che sono a conoscenza del meccanismo possono richiederlo e successivamente essere ridiretti
      qui tramite HTTPS
     */
    if (SET_DYNAMIC_IP != '') {
        @file_get_contents(SET_DYNAMIC_IP);
    }
}

function checkAdmin($Livaut = 0) {
    global $gTables, $module, $table_prefix;
    $_SESSION["logged_in"] = false;
    $_SESSION["Abilit"] = false;
    // Se utente non  loggato lo mandiamo alla pagina di login
    if ((!isset($_SESSION["Login"])) or ( $_SESSION["Login"] == "Null")) {
        $_SESSION["Login"] = "Null";
        header("Location: ../root/login_admin.php?tp=" . $table_prefix);
        exit;
    }
    if (checkAccessRights($_SESSION['Login'], $module, $_SESSION['company_id']) == 0) {
        // Se utente non ha il diritto di accedere al modulo, lo mostriamo
        // il messaggio di errore, ma senza obligarlo di fare un altro (inutile) login
        header("Location: ../root/access_error.php?module=" . $module);
        exit;
    }
    $test = gaz_dbi_query("SHOW COLUMNS FROM `" . $gTables['admin'] . "` LIKE 'enterprise_id'");
    $exists = (gaz_dbi_num_rows($test)) ? TRUE : FALSE;
    if ($exists) {
        $c_e = 'enterprise_id';
    } else {
        $c_e = 'company_id';
    }
    $admin_aziend = gaz_dbi_get_row($gTables['admin'] . ' LEFT JOIN ' . $gTables['aziend'] . ' ON ' . $gTables['admin'] . '.' . $c_e . '= ' . $gTables['aziend'] . '.codice', "Login", $_SESSION["Login"]);
    $currency = array();
    if (isset($admin_aziend['id_currency'])) {
        $currency = gaz_dbi_get_row($gTables['currencies'], "id", $admin_aziend['id_currency']);
    }
    if ($Livaut > $admin_aziend["Abilit"]) {
        header("Location: ../root/login_admin.php?tp=" . $table_prefix);
        exit;
    } else {
        $_SESSION["Abilit"] = true;
    }

    if (!$admin_aziend || $admin_aziend["Password"] != $_SESSION["Password"]) {
        header("Location: ../root/login_admin.php?tp=" . $table_prefix);
        exit;
    }
    $_SESSION["logged_in"] = true;
    return array_merge($admin_aziend, $currency);
}

function changeEnterprise($new_co = 1) {
    global $gTables;
    gaz_dbi_put_row($gTables['admin'], 'Login', $_SESSION['Login'], 'company_id', $new_co);
    $_SESSION['company_id'] = $new_co;
}

class Compute {

    function payment_taxstamp($value, $percent, $cents_ceil_round = 5) {
        if ($cents_ceil_round == 0) {
            $cents_ceil_round = 5;
        }
        $cents = 100 * $value * ($percent / 100 + $percent * $percent / 10000);
        if ($cents_ceil_round < 0) { // quando passo un arrotondamento negativo ritorno il valore di $percent
            $this->pay_taxstamp = round($percent, 2);
        } else {
            $this->pay_taxstamp = round(ceil($cents / $cents_ceil_round) * $cents_ceil_round / 100, 2);
        }
    }

    function add_value_to_VAT_castle($vat_castle, $value = 0, $vat_rate = 0) {
        global $gTables;
        $new_castle = array();
        $row = 0;
        $this->total_imp = 0;
        $this->total_vat = 0;
        $this->total_exc_with_duty = 0;
        $this->total_isp = 0; // totale degli inesigibili per split payment PA
        /* ho due metodi di calcolo del castelletto IVA:
         * 1 - quando non ho l'aliquota IVA allora uso la ventilazione
         * 2 - in presenza di aliquota IVA e quindi devo aggiungere al castelletto */

        if ($vat_rate == 0) {        // METODO VENTILAZIONE (per mantenere la retrocompatibilità)
            $total_imp = 0;
            $decalc_imp = 0;
            foreach ($vat_castle as $k => $v) { // attraverso dell'array per calcolare i totali
                $total_imp += $v['impcast'];
                $row++;
            }
            if ($total_imp >= 0.01) { // per evitare il divide by zero in caso di imponibile 0
                foreach ($vat_castle as $k => $v) {   // riattraverso l'array del castelletto
                    // per aggiungere proporzionalmente (ventilazione)
                    $vat = gaz_dbi_get_row($gTables['aliiva'], "codice", $k);
                    $new_castle[$k]['periva'] = $vat['aliquo'];
                    $new_castle[$k]['tipiva'] = $vat['tipiva'];
                    $new_castle[$k]['descriz'] = $vat['descri'];
                    $new_castle[$k]['fae_natura'] = $vat['fae_natura'];
                    $row--;
                    if ($row == 0) { // è l'ultimo rigo del castelletto
                        // aggiungo il resto
                        $new_imp = round($total_imp - $decalc_imp + ($value * ($total_imp - $decalc_imp) / $total_imp), 2);
                    } else {
                        $new_imp = round($v['impcast'] + ($value * $v['impcast'] / $total_imp), 2);
                        $decalc_imp += $v['impcast'];
                    }
                    $new_castle[$k]['impcast'] = $new_imp;
                    $new_castle[$k]['imponi'] = $new_imp;
                    $this->total_imp += $new_imp; // aggiungo all'accumulatore del totale
                    if ($vat['aliquo'] < 0.01 && $vat['taxstamp'] > 0) { // è senza aliquota ed è soggetto a bolli
                        $this->total_exc_with_duty += $new_imp; // aggiungo all'accumulatore degli esclusi/esenti/non imponibili
                    }
                    $new_castle[$k]['ivacast'] = round(($new_imp * $vat['aliquo']) / 100, 2);
                    if ($vat['tipiva'] == 'T') { // è un'IVA non esigibile per split payment 
                        $this->total_isp += $new_castle[$k]['ivacast']; // aggiungo all'accumulatore 
                    }
                    $this->total_vat += $new_castle[$k]['ivacast']; // aggiungo anche l'IVA al totale
                }
            }
        } else {  // METODO DELL'AGGIUNTA DIRETTA (nuovo)
            $match = false;
            foreach ($vat_castle as $k => $v) { // attraverso dell'array 
                $vat = gaz_dbi_get_row($gTables['aliiva'], "codice", $k);
                $new_castle[$k]['periva'] = $vat['aliquo'];
                $new_castle[$k]['tipiva'] = $vat['tipiva'];
                $new_castle[$k]['descriz'] = $vat['descri'];
                $new_castle[$k]['fae_natura'] = $vat['fae_natura'];
                if ($k == $vat_rate) { // SE è la stessa aliquota aggiungo il nuovo valore
                    $match = true;
                    $new_imp = $v['impcast'] + $value;
                    $new_castle[$k]['impcast'] = $new_imp;
                    $new_castle[$k]['imponi'] = $new_imp;
                    $new_castle[$k]['ivacast'] = round(($new_imp * $vat['aliquo']) / 100, 2);
                } else { // è una aliquota che non interessa il valore che devo aggiungere 
                    $new_castle[$k]['impcast'] = $v['impcast'];
                    $new_castle[$k]['imponi'] = $v['impcast'];
                    $new_castle[$k]['ivacast'] = round(($v['impcast'] * $vat['aliquo']) / 100, 2);
                }
                if ($vat['aliquo'] < 0.01 && $vat['taxstamp'] > 0) { // è senza IVA ed è soggetto a bolli
                    $this->total_exc_with_duty += $new_castle[$k]['impcast']; // aggiungo all'accumulatore degli esclusi/esenti/non imponibili
                }
                if ($vat['tipiva'] == 'T') { // è un'IVA non esigibile per split payment 
                    $this->total_isp += $new_castle[$k]['ivacast']; // aggiungo all'accumulatore 
                }
                $this->total_imp += $new_castle[$k]['impcast']; // aggiungo all'accumulatore del totale
                $this->total_vat += $new_castle[$k]['ivacast']; // aggiungo anche l'IVA al totale
            }
            if (!$match && $value >= 0.01) { // non ho trovato una aliquota uguale a quella del nuovo valore se > 0 
                $vat = gaz_dbi_get_row($gTables['aliiva'], "codice", $vat_rate);
                $new_castle[$vat_rate]['periva'] = $vat['aliquo'];
                $new_castle[$vat_rate]['tipiva'] = $vat['tipiva'];
                $new_castle[$vat_rate]['impcast'] = $value;
                $new_castle[$vat_rate]['imponi'] = $value;
                $new_castle[$vat_rate]['ivacast'] = round(($value * $vat['aliquo']) / 100, 2);
                $new_castle[$vat_rate]['descriz'] = $vat['descri'];
                $new_castle[$vat_rate]['fae_natura'] = $vat['fae_natura'];
                if ($vat['aliquo'] < 0.01 && $vat['taxstamp'] > 0) { // è senza IVA ed è soggetto a bolli
                    $this->total_exc_with_duty += $new_castle[$vat_rate]['impcast']; // aggiungo all'accumulatore degli esclusi/esenti/non imponibili
                }
                if ($vat['tipiva'] == 'T') { // è un'IVA non esigibile per split payment 
                    $this->total_isp += $new_castle[$vat_rate]['ivacast']; // aggiungo all'accumulatore 
                }
                $this->total_imp += $new_castle[$vat_rate]['impcast']; // aggiungo all'accumulatore del totale
                $this->total_vat += $new_castle[$vat_rate]['ivacast']; // aggiungo anche l'IVA al totale
            }
        }
        $this->castle = $new_castle;
    }

}

class Schedule {

    function __construct() {
        $this->target = 0;
        $this->id_target = 0;
    }

    function setPartnerTarget($account) {
        /*
         * setta il valore del conto (piano dei conti) del partner (cliente o fornitore) 
         */
        $this->target = $account;
    }

    function setIdTesdocRef($id_tesdoc_ref) {
        /*
         * setta sia l'identificativo di partita che il valore del conto (piano dei conti) del partner (cliente o fornitore) 
         */
        global $gTables;
        $rs = gaz_dbi_dyn_query($gTables['paymov'] . ".id_tesdoc_ref," . $gTables['tesmov'] . ".clfoco ", $gTables['paymov'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON " . $gTables['paymov'] . ".id_rigmoc_doc = " . $gTables['rigmoc'] . ".id_rig LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['tesmov'] . ".id_tes = " . $gTables['rigmoc'] . ".id_tes", $gTables['paymov'] . ".id_tesdoc_ref = '" . $id_tesdoc_ref . "'");
        $r = gaz_dbi_fetch_array($rs);
        $this->target = $r['clfoco'];
        $this->id_target = $id_tesdoc_ref;
    }

    function setScheduledPartner($partner_type = false) { // false=TUTTI altrimenti passare le prime tre cifre del mastro clienti o fornitori
        /*
         * restituisce in $this->Partners i codici dei clienti o dei fornitori
         * che hanno almeno un movimento nell'archivio dello scadenzario
         * è un po' lento se si hanno molti righi contabili 
         */
        global $gTables;
        if (!$partner_type) { // se NON mi è stato passato il mastro dei clienti o dei fornitori
            $partner_where = '1';
        } else {
            $partner_where = $gTables['rigmoc'] . ".codcon  BETWEEN " . $partner_type . "000001 AND " . $partner_type . "999999";
        }
        $sqlquery = "SELECT " . $gTables['rigmoc'] . ".codcon 
          FROM " . $gTables['paymov'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON (" . $gTables['paymov'] . ".id_rigmoc_pay = " . $gTables['rigmoc'] . ".id_rig OR " . $gTables['paymov'] . ".id_rigmoc_doc = " . $gTables['rigmoc'] . ".id_rig ) "
                . " WHERE  " . $partner_where . " GROUP BY " . $gTables['rigmoc'] . ".codcon ";
        $rs = gaz_dbi_query($sqlquery);
        $acc = array();
        while ($r = gaz_dbi_fetch_array($rs)) {
            $partner = gaz_dbi_get_row($gTables['clfoco'], 'codice', $r['codcon']);
            $acc[$r['codcon']] = $partner['descri'];
        }
        asort($acc);
        $res = array();
        foreach ($acc as $k => $v) {
            $res[] = $k;
        }
        $this->Partners = $res;
    }

    function getScheduleEntries($ob = 0, $masclifor, $date = false) {
        /*
         * genera un array con tutti i movimenti di partite aperte con quattro tipi di ordinamento
         * se viene settato il partnerTarget allora prende in considerazione solo quelli relativi allo stesso 
         */
        global $gTables;
        if ($this->target == 0) {
            $where = $gTables['rigmoc'] . ".codcon BETWEEN " . $masclifor . "000001 AND " . $masclifor . "999999";
        } else {
            $where = $gTables['rigmoc'] . ".codcon BETWEEN " . $this->target . "000001 AND " . $this->target . "999999";
        }
        if ($date != false) {
            $where .= " AND expiry>='" . date("Y-m-d", strtotime("-5 days")) . "' and expiry<='" . date("Y-m-d", strtotime("+2 month")) . "' group by id_tesdoc_ref ";
        }
        $sqlquery = "SELECT * FROM " . $gTables['paymov']
                . " LEFT JOIN " . $gTables['rigmoc'] . " ON (" . $gTables['paymov'] . ".id_rigmoc_pay = " . $gTables['rigmoc'] . ".id_rig OR " . $gTables['paymov'] . ".id_rigmoc_doc = " . $gTables['rigmoc'] . ".id_rig ) "
                . " WHERE  " . $where . " ORDER BY id_tesdoc_ref, expiry";
        $rs = gaz_dbi_query($sqlquery);
        $this->Entries = array();
        $acc = array();
        while ($r = gaz_dbi_fetch_array($rs)) {
            $anagrafica = new Anagrafica();
            $partner = $anagrafica->getPartner($r['codcon']);
            $tes = gaz_dbi_get_row($gTables['tesmov'], 'id_tes', $r['id_tes']);
            $tes['ragsoc'] = $partner['ragso1'] . ' ' . $partner['ragso2'];
            switch ($ob) {
                case 1:
                    $acc[$r['expiry']][] = $r + $tes + $partner;
                    break;
                case 2:
                case 3:
                    $acc[$partner['ragso1']][] = $r + $tes + $partner;
                    break;
                default:
                    $acc[$r['expiry']][] = $r + $tes + $partner;
            }
        }
        if ($ob == 1 || $ob == 3) {
            krsort($acc);
        } else {
            ksort($acc);
        }
        $res = array();
        foreach ($acc as $v1) {
            foreach ($v1 as $v2) {
                $this->Entries[] = $v2;
            }
        }
    }

    function getPartnerAccountingBalance($clfoco, $date = false) {
        /*
         * restituisce il valore del saldo contabile di un cliente ad una data, se passata, oppure alla data di sistema
         * */
        global $gTables;
        if ($this->target > 0 && $clfoco == 0) {
            $clfoco = $this->target;
        }
        if (!$date) {
            $date = strftime("%Y-%m-%d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        }
        $sqlquery = "SELECT " . $gTables['tesmov'] . ".datreg ," . $gTables['rigmoc'] . ".import, " . $gTables['rigmoc'] . ".darave
            FROM " . $gTables['rigmoc'] . " LEFT JOIN " . $gTables['tesmov'] .
                " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
            WHERE codcon = $clfoco AND caucon <> 'CHI' AND caucon <> 'APE' OR (caucon = 'APE' AND codcon = $clfoco AND datreg IN (SELECT MIN(datreg) FROM " . $gTables['tesmov'] . ")) ORDER BY datreg ASC";
        $rs = gaz_dbi_query($sqlquery);
        $date_ctrl = new DateTime($date);
        $acc = 0.00;
        while ($r = gaz_dbi_fetch_array($rs)) {
            $dr = new DateTime($r['datreg']);
            if ($dr <= $date_ctrl) {
                if ($r['darave'] == 'D') {
                    $acc += $r['import'];
                } else {
                    $acc -= $r['import'];
                }
            }
        }
        return round($acc, 2);
    }

    function getAmount($id_tesdoc_ref, $date = false) {
        /*
         * restituisce in $this->Satus la differenza (stato) tra apertura e chiusura di una partita
         */
        global $gTables;
        $date_ctrl = new DateTime($date);
        $sqlquery = "SELECT SUM(amount*(id_rigmoc_doc>0)- amount*(id_rigmoc_pay>0)) AS diff_paydoc, 
          SUM(amount*(id_rigmoc_pay>0)) AS pay, 
          SUM(amount*(id_rigmoc_doc>0))AS doc,
          MAX(expiry) AS exp
            FROM " . $gTables['paymov'] . "
            WHERE id_tesdoc_ref = '" . $id_tesdoc_ref . "' GROUP BY id_tesdoc_ref";
        $rs = gaz_dbi_query($sqlquery);
        $r = gaz_dbi_fetch_array($rs);
        return $r['diff_paydoc'];
    }

    function getStatus($id_tesdoc_ref, $date = false) {
        /*
         * restituisce in $this->Satus la differenza (stato) tra apertura e chiusura di una partita
         */
        global $gTables;
        $date_ctrl = new DateTime($date);
        $sqlquery = "SELECT SUM(amount*(id_rigmoc_doc>0)- amount*(id_rigmoc_pay>0)) AS diff_paydoc, 
          SUM(amount*(id_rigmoc_pay>0)) AS pay, 
          SUM(amount*(id_rigmoc_doc>0))AS doc,
          MAX(expiry) AS exp
            FROM " . $gTables['paymov'] . "
            WHERE id_tesdoc_ref = '" . $id_tesdoc_ref . "' GROUP BY id_tesdoc_ref";
        $rs = gaz_dbi_query($sqlquery);
        $r = gaz_dbi_fetch_array($rs);
        $ex = new DateTime($r['exp']);
        $interval = $date_ctrl->diff($ex);
        if ($r['diff_paydoc'] >= 0.01) { // la partita è aperta
            $r['sta'] = 0;
            if ($date_ctrl > $ex) { // ... ed è pure scaduta
                $r['sta'] = 3;
            }
        } elseif ($r['diff_paydoc'] == 0.00) { // la partita è chiusa ma...
            if ($date_ctrl < $ex) { //  se è un pagamento che avverrà ma non è stato realmente effettuato , che comporta esposizione a rischio
                $r['sta'] = 2; // esposta
            } else { // altrimenti è chiusa completamente
                $r['sta'] = 1;
            }
        } else {
            $r['sta'] = 9;
        }
        $this->Status = $r;
    }

    function getDocumentData($id_tesdoc_ref, $clfoco = null) {
        /*
          restituisce i dati relativi al documento che ha aperto la partita
         */
        global $gTables;

        if (!is_numeric($id_tesdoc_ref)) {
            $id_tesdoc_ref = "'" . $id_tesdoc_ref . "'";
        }

        $where_clfoco = "";
        if (isset($clfoco)) {
            $where_clfoco = " AND " . $gTables['tesmov'] . ".clfoco = $clfoco ";
        }

        $sqlquery = "SELECT " . $gTables['tesmov'] . ".* 
            FROM " . $gTables['paymov'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON " . $gTables['paymov'] . ".id_rigmoc_doc = " . $gTables['rigmoc'] . ".id_rig
            LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
            WHERE " . $gTables['paymov'] . ".id_rigmoc_doc > 0 AND " . $gTables['paymov'] . ".id_tesdoc_ref = " . $id_tesdoc_ref . $where_clfoco . " ORDER BY datreg ASC";
        $rs = gaz_dbi_query($sqlquery);
        return gaz_dbi_fetch_array($rs);
    }

    function getDocFromID($id_rigmoc_doc) {
        global $gTables;
        $sqlquery = "SELECT " . $gTables['tesmov'] . ".* 
            FROM " . $gTables['rigmoc'] . " LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
            WHERE " . $gTables['rigmoc'] . ".id_rig = " . $id_rigmoc_doc;
        $rs = gaz_dbi_query($sqlquery);
        return gaz_dbi_fetch_array($rs);
    }

    function getPartnerStatus($clfoco, $date = false)
    /*
     * genera un array ($this->PartnerStatus)con i valori dell'esposizione verso un partner commerciale
     * riferito ad una data, se passata, oppure alla data di sistema
     * $this->docData verrà valorizzato con i dati relativi al documento di riferimento
     * */ {
        global $gTables;
        $this->PartnerStatus = array();
        if ($clfoco <= 999 && $clfoco >= 100) { // ho un mastro clienti o foritori
            $clfoco = "999999999 OR " . $gTables['clfoco'] . ".codice LIKE '" . $clfoco . "%'";
        } elseif ($this->target > 0 && $this->id_target > 0) {
            $clfoco = $this->target . " AND id_tesdoc_ref = '" . $this->id_target . "'";
        } elseif ($this->target > 0 && $clfoco == 0) {
            $clfoco = $this->target;
        }
        if (!$date) {
            $date = strftime("%Y-%m-%d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        }
        $sqlquery = "SELECT " . $gTables['paymov'] . ".*, " . $gTables['tesmov'] . ".* ," . $gTables['rigmoc'] . ".*
            FROM " . $gTables['paymov'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON (" . $gTables['paymov'] . ".id_rigmoc_pay = " . $gTables['rigmoc'] . ".id_rig OR " . $gTables['paymov'] . ".id_rigmoc_doc = " . $gTables['rigmoc'] . ".id_rig )"
                . "LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes "
                . "LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['clfoco'] . ".codice = " . $gTables['rigmoc'] . ".codcon 
            WHERE " . $gTables['clfoco'] . ".codice  = " . $clfoco . " ORDER BY id_tesdoc_ref, id_rigmoc_pay, expiry";
        $rs = gaz_dbi_query($sqlquery);
        $date_ctrl = new DateTime($date);
        $ctrl_id = 0;
        $acc = array();
        while ($r = gaz_dbi_fetch_array($rs)) {
            $expo = false;
            $k = $r['id_tesdoc_ref'];
            if ($k <> $ctrl_id) { // PARTITA DIVERSA DALLA PRECEDENTE
                $acc[$k] = array();
                $this->docData[$k] = array('id_tes' => $r['id_tes'], 'descri' => $r['descri'], 'numdoc' => $r['numdoc'], 'seziva' => $r['seziva'], 'datdoc' => $r['datdoc'], 'amount' => $r['amount']);
            }
            $ex = new DateTime($r['expiry']);
            $interval = $date_ctrl->diff($ex);
            if ($r['id_rigmoc_doc'] > 0) { // APERTURE (vengono prima delle chiusure)
                $s = 0;
                if ($date_ctrl >= $ex) {
                    $s = 3; // SCADUTA
                }
                $acc[$k][] = array('id' => $r['id'], 'op_val' => $r['amount'], 'expiry' => $r['expiry'], 'cl_val' => 0, 'cl_exp' => '', 'expo_day' => 0, 'status' => $s, 'op_id_rig' => $r['id_rig'], 'cl_rig_data' => array());
            } else {                    // ATTRIBUZIONE EVENTUALI CHIUSURE ALLE APERTURE (in ordine di scadenza)
                if ($date_ctrl < $ex) { //  se è un pagamento che avverrà ma non è stato realmente effettuato , che comporta esposizione a rischio
                    $expo = true;
                }
                $v = $r['amount'];
                foreach ($acc[$k] as $ko => $vo) { // attraverso l'array delle aperture
                    $diff = round($vo['op_val'] - $vo['cl_val'], 2);
                    if ($diff >= 0.01 && $v > 0.01) { // faccio il push sui dati del rigo
                        $acc[$k][$ko]['cl_rig_data'][] = array('id_rig' => $r['id_rig'], 'descri' => $r['descri'], 'id_tes' => $r['id_tes'], 'import' => $r['import']);
                    }
                    if ($v <= $diff) { // se c'è capienza
                        $acc[$k][$ko]['cl_val'] += $v;
                        if ($expo) { // è un pagamento che avverrà ma non è stato realmente effettuato , che comporta esposizione a rischio
                            $acc[$k][$ko]['expo_day'] = $interval->format('%a');
                            $acc[$k][$ko]['cl_exp'] = $r['expiry'];
                            $expo = false;
                        } else {
                            $acc[$k][$ko]['cl_exp'] = $r['expiry'];
                        }
                        $v = 0;
                    } else { // non c'è capienza
                        $acc[$k][$ko]['cl_val'] += $diff;
                        if ($expo && $diff >= 0.01) { // è un pagamento che avverrà ma non è stato realmente effettuato , che comporta esposizione a rischio
                            $acc[$k][$ko]['expo_day'] = $interval->format('%a');
                            $acc[$k][$ko]['cl_exp'] = $r['expiry'];
                        }
                        $v = round($v - $diff, 2);
                    }
                    if (round($acc[$k][$ko]['op_val'] - $acc[$k][$ko]['cl_val'], 2) < 0.01) { // è chiusa
                        $acc[$k][$ko]['status'] = 1;
                    }
                }
                if (count($acc[$k]) == 0) {
                    $acc[$k][] = array('id' => $r['id'], 'op_val' => 0, 'expiry' => 0, 'cl_val' => $r['amount'], 'cl_exp' => $r['expiry'], 'expo_day' => 0, 'status' => 9, 'op_id_rig' => 0, 'cl_rig_data' => array(0 => array('id_rig' => $r['id_rig'], 'descri' => $r['descri'], 'import' => $r['import'], 'id_tes' => $r['id_tes'])));
                }
            }
            $ctrl_id = $r['id_tesdoc_ref'];
        }
        $this->PartnerStatus = $acc;
    }

    function updatePaymov($data) {
        global $gTables;
        if (isset($data['id']) && !empty($data['id'])) { // se c'è l'id vuol dire che è un rigo da aggiornare
            paymovUpdate(array('id', $data['id']), $data);
        } elseif (is_numeric($data)) { /* se passo un dato numerico vuol dire che devo eliminare tutti i righi
         * di paymov che fanno riferimento a quell'id_rig */
            gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $data);
            gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_pay", $data);
        } elseif (isset($data['id_del'])) { /* se passo un id da eliminare elimino SOLO quello */
            gaz_dbi_del_row($gTables['paymov'], "id", $data['id_del']);
        } else {    // altrimenti è un nuovo rigo da inserire
            paymovInsert($data);
        }
    }

    function setRigmocEntries($id_rig) { // 
        global $gTables;
        $sqlquery = "SELECT * FROM " . $gTables['paymov'] . " WHERE id_rigmoc_pay=$id_rig OR id_rigmoc_doc=$id_rig";
        $this->RigmocEntries = array();
        $rs = gaz_dbi_query($sqlquery);
        while ($r = gaz_dbi_fetch_array($rs)) {
            $this->RigmocEntries[] = $r;
        }
    }

}

/* controllo se ho delle funzioni specifiche per il modulo corrente
  residente nella directory del module stesso, con queste caratteristiche:
  modules/nome_modulo/lib.function.php
 */

if (@file_exists('./lib.function.php')) {
    require('./lib.function.php');
}
?>
