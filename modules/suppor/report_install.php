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
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
$titolo = 'Installazioni';

require("../../library/include/header.php");
$script_transl=HeadMain();

$where 	= "1";
$all 	= $where;

gaz_flt_var_assign($gTables['instal'].'_codice', 'i');
gaz_flt_var_assign('datainst', 'd');
gaz_flt_var_assign('clfoco', 'v');
gaz_flt_var_assign('telefono', 'v');
gaz_flt_var_assign('oggetto', 'v');
gaz_flt_var_assign('descrizione', 'v');

$orderby = "id asc";

if ( isset($_GET['all']) ) {
    $_GET[$gTables['instal'].'_codice'] = "";
    $_GET['datainst'] = "";
    $_GET['clfoco'] = "";
    $_GET['telefono'] = "";
    $_GET['oggetto'] = "";
    $_GET['descrizione'] = "";
    
    $where = $all;
}

?>
<div align="center" class="FacetFormHeaderFont table-responsive">
	<form method="GET">
	<!-- riga filtro -->
	<table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
		<tr>
		<td class="FacetFieldCaptionTD">
			<?php gaz_flt_disp_int($gTables['instal'].'_codice', "Numero"); ?>
		</td>
		<td class="FacetFieldCaptionTD">
			<?php gaz_flt_disp_select("datainst", "YEAR(datainst) as datainst", $gTables["instal"], "9999", $orderby); ?>
		</td>
		<td class="FacetFieldCaptionTD">
			<?php gaz_flt_disp_select("clfoco", $gTables['anagra'] . ".ragso1," . $gTables["instal"] . ".clfoco", $gTables['instal'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['instal'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra = " . $gTables['anagra'] . ".id", $all, "ragso1", "ragso1"); ?>
		</td>
		<td class="FacetFieldCaptionTD">
			<?php gaz_flt_disp_int("telefono", "Telefono"); ?>
		</td>
		<td class="FacetFieldCaptionTD">
			<?php gaz_flt_disp_int("oggetto", "Oggetto"); ?>
		</td>
      <td class="FacetFieldCaptionTD">
			<?php gaz_flt_disp_int("descrizione", "Descrizione"); ?>
		</td>
		<td colspan="2" class="FacetFieldCaptionTD">
			<input type="submit" class="btn btn-sm btn-default" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value = 1;">
			<input type="submit" class="btn btn-sm btn-default" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value=1;">
		</td>
		</tr>

		<?php 
		//riga ordinamento colonne
		$headers_assist = array  (
			"Codice" 	=> "codice",
			"Data installazione" => "datainst",
			"Cliente" 	=> "cliente",
			"Telefono" 	=> "telefono",
			"Oggetto" 	=> "oggetto",
			"Descrizione" => "descrizione",        
         "Stampa" 	=> "",
			"Elimina" 	=> ""
		);
		$linkHeaders = new linkHeaders($headers_assist);
		$linkHeaders -> output();

		$recordnav = new recordnav($gTables['instal'].
				" LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['instal'].".clfoco = ".$gTables['clfoco'].".codice". 
				" LEFT JOIN ".$gTables['anagra'].' ON '.$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id",
			$where, $limit, $passo);
		$recordnav -> output();

$result = gaz_dbi_dyn_query( $gTables['instal'].".*,
				".$gTables['anagra'].".ragso1, 
				".$gTables['anagra'].".telefo ",
				$gTables['instal'].
					" LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['instal'].".clfoco = ".$gTables['clfoco'].".codice". 
					" LEFT JOIN ".$gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '.$gTables['anagra'].'.id',
				$where, $orderby, $limit, $passo);

$month = array(1=>"Gennaio", 2=>"Febbraio", 3=>"Marzo", 4=>"Aprile", 5=>"Maggio", 6=>">Giugno", 7=>"Luglio", 8=>"Agosto", 9=>"Settembre", 10=>"Ottobre", 11=>"Novembre", 12=>"Dicembre");

while ($a_row = gaz_dbi_fetch_array($result)) {
?>
   <tr class="FacetDataTD">
		<td>
			<a class="btn btn-xs btn-default btn-100" href="admin_install.php?codice=<?php echo $a_row["codice"]; ?>&Update">
			<i class="glyphicon glyphicon-edit"></i><?php echo $a_row["codice"]; ?></a>
		</td>
		<td><?php echo date("d",strtotime($a_row["datainst"]))." ".$month[date("n",strtotime($a_row["datainst"]))]." ".date("Y",strtotime($a_row["datainst"])); ?></td>
		<td><a href="../vendit/report_client.php?auxil=<?php echo $a_row["ragso1"]; ?>&search=Cerca">
		<?php 
			if ( strlen($a_row["ragso1"]) > 20 ) {
				echo substr($a_row["ragso1"],0,20)."..."; 
			} else {
				echo $a_row["ragso1"]; 
			}
		?></a>
		</td>
		<td><?php echo $a_row["telefo"]; ?></td>
		<td><?php echo $a_row["oggetto"]; ?></td>
		<td><?php 
            $length = strlen($a_row["descrizione"]);
            $descri = substr($a_row["descrizione"], 0, 80);
            echo $descri."..."; ?>
      </td>
		<td>
			<a class="btn btn-xs btn-default" href="stampa_install.php?id=<?php echo $a_row["id"]; ?>&cod=<?php echo $a_row["codice"]; ?>&stato=<?php echo $a_row["stato"]; ?>" target="_blank"><i class="glyphicon glyphicon-print"></i></a>
		</td>
		<td>
			<a class="btn btn-xs btn-default btn-elimina" href="delete_assist.php?id=<?php echo $a_row["id"]; ?>&cod=<?php echo $a_row["codice"]; ?>">
			<i class="glyphicon glyphicon-remove"></i></a>
		</td>
   </tr>
<?php 
	//$totale_ore += $a_row["ore"];
} 

$passi = array(20, 50, 100, 10000 );
?>
<!-- riga riepilogo tabella -->
<tr>
	<td class="FacetFieldCaptionTD" colspan="6" align="right">Totale Ore : 
		<?php //echo floatval($totale_ore); ?>
	</td>
	<td class="FacetFieldCaptionTD" colspan="2" align="right">Totale Euro : 
		<?php //echo floatval($totale_ore * 42); ?>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD" align="center" colspan="8">Numero elementi : 
		<select name="flt_passo" onchange="this.form.submit()">		
		<?php
		foreach ( $passi as $val ) {
			if ( $val == $passo ) $selected = " selected";
			else $selected = "";
			echo "<option value='".$val."'".$selected.">".$val."</option>";
		}
		?>
		</select>
	</td>
</tr>
</table>
</form>
</div>
<?php
require("../../library/include/footer.php");
?>