/** ENRICO FEDELE */
/*
 Se scegliamo bootstrap come framewors css (e credo che sia la cosa più opportuna dal momento che,
 pur con tutti i suoi difetti tra i quali una certa pesantezza/lentezza, ci consente di procedere spediti 
 potendo contare sul lavoro della community) allora tanto vale cercare di sfruttarlo quanto più possibile,
 per evitare di sovraccaricare il sistema, tenerlo snello e soprattutto facilitarci il compito.
 Allora per i tooltip possiamo pensare di utilizzare il plugin nativo di Bootstrap, per cui la versione di 
 Jquery-ui che utilizziamo attualmente, l'ultima disponibile al 09/11/2015, è compilata senza il plugin tooltip,
 che altrimenti genererebbe conflitti con quello di Bootstrap, dal momento che hanno lo stesso nome.
 
 Questa funzione è un tentativo di portare in un unico posto i tooltip di gazie, differenziandoli per contesto:
 product-thumb: tooltip per l'immagine di un prodotto
 weight: tooltip per il peso
 
 la magia si fa con:
 class="gazie-tooltip" (classe da assegnare all'elemento da dotare di tooltip)
 data-type="product-thumb/weight" (tipologia di tooltip, al momento solo immagine prodotto e peso)
 data-id="ID_PRODOTTO/PESO_PRODOTTO"  (id prodotto per l'immagine, peso altrimenti)
 data-title="TITOLO DA DARE" (è possibile passare una stringa di testo, al momento presa in considerazione solo per il peso)
 */
/** ENRICO FEDELE */


/* Abilita/disabilita un textbox sulla base dello stato di un checkbox collegato */
$(document).ready(function () {

    gzTooltip();
    
    $(".Tlarge").addClass('table table-striped table-bordered table-condensed table-responsive');
    $("#alert-discount").fadeTo(2500, 1500).slideUp(750, function () {
        $("#alert-discount").alert('close');
    });

    var current = $("#alert-last-row").css('color');
    $("#alert-last-row")
            .animate({backgroundColor: '#faebcc'}, 2000)
            .animate({backgroundColor: '#d6e9c6'}, 2000)
            .animate({backgroundColor: '#faebcc'}, 2000)
            .animate({backgroundColor: '#d6e9c6'}, 2000);

			$('#products-list > tbody > tr:first').before($('#products-list-last'));
    $('.products-list-last').effect("highlight", {times: 1}, 5000);
    $('#products-list-last').toggleClass('products-list-last products-list-last-moved');
});

$(window).resize(function () {
    resizeNavbarText();
});

$(window).load(function () {
    resizeNavbarText();
});

/* Restringe automaticamente il testo del menu sui dispay piccoli e toglie l'header */
function resizeNavbarText() {
    var w = $(window).width() / 5;
    var lwr = $('#l-wrapper').width();
    var up = $('#user-position').position();
    var n = up.left / 5;
    if (w < n) {
        $('#l-wrapper').delay(150).animate({'margin-left': '-' + lwr + '0px'});
        $('.navbar-nav > li > a').delay(150).css({'font-size': '0.75em'});
    } else {
        $('.navbar-nav > li > a').delay(150).css({'font-size': '1em'});
        $('#l-wrapper').delay(150).css({'margin-left': '0px'});
    }
}

function toggle(boxID, toggleID) {
    var box = document.getElementById(boxID);
    var toggle = document.getElementById(toggleID);
    updateToggle = box.checked ? toggle.disabled = false : toggle.disabled = true;
    toggle.focus();
}

this.gzTooltip = function () {
    $('.gazie-tooltip').tooltip(
            {html: true,
                placement: 'auto bottom',
                delay: {show: 50},
                title: function () {
                    var codeDtls = this.getAttribute('data-type');
                    if (codeDtls == "product-thumb") {
                        codeDtls = '<span class="label">' + this.getAttribute('data-title') + '</span><img src="../root/view.php?table=artico&value=' + this.getAttribute('data-id') + '" onerror="this.src=\'../../library/images/link_break.png\'" alt="' + this.getAttribute('data-title') + '" />';
                        return codeDtls;
                    } else if (codeDtls == "weight") {
                        codeDtls = this.getAttribute('data-title') + '&nbsp;' + this.getAttribute('data-id') + 'kg';
                        return codeDtls;
                    } else if (codeDtls == "ritenuta") {
                        codeDtls = this.getAttribute('data-title') + '&nbsp;' + this.getAttribute('data-id') + '€';
                        return codeDtls;
                    } else if (codeDtls == "catmer-thumb") {
                        codeDtls = '<img src="../root/view.php?table=catmer&value=' + this.getAttribute('data-id') + '" onerror="this.src=\'../../library/images/link_break.png\'" alt="' + this.getAttribute('data-title') + '" />';
                        return codeDtls;
                    } else if (codeDtls == "movcon-thumb") {
                        codeDtls = this.getAttribute('data-title');
                        return codeDtls;
                    } else if (codeDtls == "ragstat-thumb") {
                        codeDtls = '<img src="../root/view.php?table=ragstat&value=' + this.getAttribute('data-id') + '" onerror="this.src=\'../../library/images/link_break.png\'" alt="' + this.getAttribute('data-title') + '" />';
                        return codeDtls;

                    }
                }
            });
};
