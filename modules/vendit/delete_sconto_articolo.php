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

require_once("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
require_once("../../library/include/header.php");
$script_transl = HeadMain();
if (isset($_GET['clfoco'])) { // attivazione
   $form["codclfoco"] = $_GET['codclfoco'];
   $form["clfoco"] = $_GET['clfoco'];
   $form["codart"] = $_GET['codart'];
   $form["descrart"] = $_GET['descrart'];
   $form["sconto"] = $_GET['sconto'];
   $form["prezzo_netto"] = $_GET['prezzo_netto'];
}
if (isset($_POST['Delete'])) {
   $codcli = $_POST['codclfoco'];
   $codart = $_POST['codart'];
   $tabellaSconti = $gTables['sconti_articoli'];
   $query = "delete from $tabellaSconti "
           . "where clfoco = '$codcli' and codart = '$codart'";
   $righeCancellate = gaz_dbi_query($query, true);
   alert($script_transl['mesg'][5] . $righeCancellate);
   windowsClose();
}
if (isset($_POST['Return'])) {
   header("Location: report_piacon.php");
   exit;
}
?>
<form method="POST">
    <input type="hidden" name="codart" value="<?= $form["codart"] ?>">
    <input type="hidden" name="codclfoco" value="<?= $form["codclfoco"] ?>">
    <div align="center"><font class="FacetFormHeaderFont"><?= $script_transl['mesg'][0]; ?> </font></div>
    <table border="0" cellpadding="3" cellspacing="1" class="FacetFormTABLE" align="center">
        <tr>
            <td colspan="2" class="FacetDataTD" style="color: red;">
                <?= $script_transl['mesg'][1]; ?>
            </td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"><?= $script_transl['header'][0]; ?> &nbsp;</td>
            <td class="FacetDataTD"> <input type="text" size="100"  disabled value="<?= $form['clfoco']; ?>">&nbsp;</td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"><?= $script_transl['header'][1]; ?> &nbsp;</td>
            <td class="FacetDataTD"> <input type="text" size="100"  disabled value="<?= $form['descrart']; ?>">&nbsp;</td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"><?= $script_transl['header'][3]; ?>  &nbsp;</td>
            <td class="FacetDataTD"> <input type="text"  disabled value="<?= $form['prezzo_netto']; ?>">&nbsp;</td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"><?= $script_transl['header'][2]; ?>  &nbsp;</td>
            <td class="FacetDataTD"> <input type="text"  disabled value="<?= $form['sconto']; ?>">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2" align="right"><?= $script_transl['mesg'][2]; ?> &nbsp;
                <input title="Torna indietro" type="submit" name="Return" value="<?= $script_transl['mesg'][3]; ?>" onclick="window.close()">&nbsp;
                <input title="Conferma l'eliminazione" type="submit" name="Delete" value="<?= $script_transl['mesg'][4]; ?>"&nbsp;
            </td>
        </tr></table>
</form>
<?php
require("../../library/include/footer.php");
?>