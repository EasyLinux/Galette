<?php
/**
 * Cr�ation des cartes d'adh�rents au format PDF
 *
 * La cr�ation des cartes au format pdf se fait soit
 * - depuis la page de gestion des adh�rents en s�lectionnant
 *   les adh�rents  dans la liste
 * - depuis la page de visualisation d'un adh�rent. Une seule
 *   carte est alors g�n�r�e
 *
 * Les couleurs sont d�finies dans l'�cran des pr�f�rences
 * en utilisant des codes identiques � ceux utilis�s en HTML.
 *
 * @package    Galette
 * @author     John Perr <johnperr@abul.org>
 * @copyright  2007 John Perr
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL License 2.0 or (at your option) any later version
 * @version    $Id$
 * @since      Disponible depuis la Release 0.63
 */

/**
 * 
 */
    include("includes/config.inc.php");
    include(WEB_ROOT."includes/database.inc.php"); 
    include(WEB_ROOT."includes/session.inc.php");

    if ($_SESSION["logged_status"]==0) {
        header("location: index.php");
        die();
    }
    if ($_SESSION["admin_status"]==0) {
        header("location: voir_adherent.php");
        die();
    }

    include_once(WEB_ROOT."includes/i18n.inc.php");
    include_once(WEB_ROOT."includes/picture.class.php");
    require_once (WEB_ROOT."includes/pdf.class.php");

// Fill array $mailing_adh with selected ids
    $mailing_adh = array();
    if (isset($_SESSION['galette']['cards'])) {
        while (list($key,$value)=each($_SESSION['galette']['cards']))
            $mailing_adh[]=$value;
        unset($_SESSION['galette']['cards']);

// If we are called from "Voir_adherent" get unique id value           
    } elseif ($_GET["id_adh"] > 0)
        $mailing_adh[]=$_GET["id_adh"];
    else    
        die();

// Select address field to display
    switch (PREF_CARD_ADDRESS){
    case 0:
        $addr_fld="email_adh";
        break;

    case 1:
        $addr_fld="msn_adh";
        break;

    case 2:
        $addr_fld="jabber_adh";
        break;

    case 3:
        $addr_fld="url_adh";
        break;

    case 4:
        $addr_fld="icq_adh";
        break;

    case 5:
        $addr_fld="cp_adh";
        break;

    case 5:
        $addr_fld="pseudo_adh";
        break;

    case 7:
        $addr_fld="prof_adh";
        break;
    }
// Build database request
    $requete = "SELECT id_adh, nom_adh, prenom_adh,".$addr_fld.", ville_adh, titre_adh, id_statut
                    FROM ".PREFIX_DB."adherents WHERE ";
    $where_clause = "";

// Get all select members' id from selection
    while(list($key,$value)=each($mailing_adh)){
        if ($where_clause!="")
            $where_clause .= " OR ";
        $where_clause .= "id_adh=".$DB->qstr($value, get_magic_quotes_gpc());
    }
    $requete .= $where_clause." ORDER by nom_adh, prenom_adh;";
    $resultat = &$DB->Execute($requete);
    if ($resultat->EOF)
        die();
                    
// Set PDF headings    
    $doc_title    = _T("Member's Cards");
    $doc_subject  = _T("Generated by Galette");
    $doc_keywords = _T("Cards");

// Get fixed data from preferences
// and convert strings to utf-8 for tcpdf
    $an_cot = "<b>".PREF_CARD_YEAR."</b>";
    $abrev = "<b>".mb_convert_encoding(PREF_CARD_ABREV,"UTF-8")."</b>";
    $strip = mb_convert_encoding(PREF_CARD_STRIP,"UTF-8");
    $logo=& new picture(999999);
    if ($logo->HAS_PICTURE){
        $logofile = $logo->FILE_PATH;

// Set logo size to max width 30 mm or max height 25 mm
        $ratio = $logo->WIDTH/$logo->HEIGHT;
        if ($ratio < 1) {
            if ($logo->HEIGHT > 16) {
                $hlogo = 25;
            } else {
                $hlogo = $logo->HEIGHT;
            }                
            $wlogo = round($hlogo*$ratio);
        } else {
            if ($logo->WIDTH > 16) {
                $wlogo = 30;
            } else {
                $wlogo = $logo->WIDTH;
            }                
            $hlogo = round($wlogo/$ratio);
        }
// If no logo chosen force default one
    } else {
        $logofile=WEB_ROOT."templates/default/images/galette_no_alpha.png";
        $wlogo = 15;
        $hlogo = 7;
    }            
       
// Create new PDF document
    $pdf = new PDF("P","mm","A4",true,"UTF-8"); 

// Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(PDF_AUTHOR);
    $pdf->SetTitle($doc_title);
    $pdf->SetSubject($doc_subject);
    $pdf->SetKeywords($doc_keywords);

// No hearders and footers
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);
    $pdf->setFooterMargin(0);
    $pdf->setHeaderMargin(0);

// Show full page
    $pdf->SetDisplayMode("fullpage");

// Disable Auto Page breaks
    $pdf->SetAutoPageBreak(false,0);

// Set colors
    $pdf->SetDrawColor(160,160,160);
    $pdf->SetTextColor(0);
    $tcol=$pdf->ColorHex2Dec(PREF_CARD_TCOL);
    $scol=$pdf->ColorHex2Dec(PREF_CARD_SCOL);
    $bcol=$pdf->ColorHex2Dec(PREF_CARD_BCOL);
    $hcol=$pdf->ColorHex2Dec(PREF_CARD_HCOL);
   
// Set margins
    $pdf->SetMargins(PREF_CARD_MARGES_H, PREF_CARD_MARGES_V);

// Set font
    $pdf->SetFont("FreeSerif");

// Set origin
// Top left corner        
    $xorigin=PREF_CARD_MARGES_H;
    $yorigin=PREF_CARD_MARGES_V;

// Card width
    $w = 75;
// Card heigth
    $h = 40;
// Number of colons
    $nbcol=2;
// Number of rows
    $nbrow=6;
// Spacing betweeen cards
    $hspacing=PREF_CARD_HSPACE;
    $vspacing=PREF_CARD_VSPACE;

// Loop over cards
    $nb_card=0;
    while (!$resultat->EOF) {
// Detect page breaks
        if ($nb_card % ($nbcol * $nbrow)==0)
            $pdf->AddPage();

// Compute card position on page
        $col=$nb_card % $nbcol;
        $row=($nb_card/$nbcol) % $nbrow;
// Set origin
        $x0 = $xorigin + $col*(round($w)+round($hspacing));
        $y0 = $yorigin + $row*(round($h)+round($vspacing));
// Logo X position
        $xl = round($x0 + $w - $wlogo);
// Get data
// Extract town if zip - town selected
        if ( PREF_CARD_ADDRESS == 5 )
            $email = "<b>".mb_convert_encoding($resultat->fields[3]." - ".$resultat->fields[4],"UTF-8")."</b>";
        else
            $email = "<b>".mb_convert_encoding($resultat->fields[3],"UTF-8")."</b>";
        
        $titre ="";
        if ( PREF_BOOL_DISPLAY_TITLE ) {
            switch ($resultat->fields[5]){
            case "1" :
                $titre = _T("Mr.");
                break;

            case "2" :
                $titre = _T("Mrs.");
                break;

            case "3" :
                $titre = _T("Miss.");
                break;

            case "4" :
                $titre = _T("Society");
                break;

            default :
                $titre = "";
            }
        $titre .=" ";
        }
// Select strip color according to status
        switch ($resultat->fields[6]){
        case  1 :
        case  2 :
        case  3 :
        case 10 :
            $fcol = $bcol;
            break;

        case  5 :
        case  6 :
            $fcol = $hcol;
            break;

        default :
            $fcol = $scol;
        }

        $id = "<b>".$resultat->fields[0]."</b>";
        $nom_adh_ext = "<b>".$titre.mb_convert_encoding(strtoupper($resultat->fields[2]." ".$resultat->fields[1]),"UTF-8")."</b>";
        $photo = new picture($resultat->fields[0]);
        $photofile = $photo->FILE_PATH;

// Photo 100x130 and logo
        $pdf->Image($photofile,$x0,$y0,25);
        $pdf->Image($logofile,$xl,$y0,round($wlogo));

// Color=#8C8C8C: Shadow of the year
         $pdf->SetTextColor(140);
         $pdf->SetFontSize(10);
         $pdf->SetXY($x0 + 65,$y0+$hlogo);
         $pdf->writeHTML($an_cot,false,0);

// Colored Text (Big label, id, year)
// Abbrev: Adapt font size to text length
        $pdf->SetTextColor($fcol["R"],$fcol["G"],$fcol["B"]);
        $fontsz = 48;
        $pdf->SetFontSize($fontsz);
        while ($pdf->GetStringWidth($abrev) > 50) {
            $fontsz--;
            $pdf->SetFontSize($fontsz);
        }
        $pdf->SetXY($x0 + 27,$y0 + 10);
        $pdf->writeHTML($abrev,false,0);
    
        $pdf->SetFontSize(8);
        $pdf->SetXY($x0 + 69,$y0 + 28);
        $pdf->writeHTML($id,false,0);
        $pdf->SetFontSize(10);
        $pdf->SetXY($x0 + 64.7,$y0+$hlogo - 0.3);
        $pdf->writeHTML($an_cot,false,0);
    
// Name: Adapt font size to text length
        $pdf->SetTextColor(0);
        $fontsz=16;
        $pdf->SetFontSize($fontsz);
        while ($pdf->GetStringWidth($nom_adh_ext) > 50) {
            $fontsz--;
            $pdf->SetFontSize($fontsz);
        }
        $pdf->SetXY($x0 + 27,$y0 + 18);
        $pdf->writeHTML($nom_adh_ext,false,0);

// Email (adapt too)
        $fontsz=14;
        $pdf->SetFontSize($fontsz);
        while ($pdf->GetStringWidth($email) > 50) {
            $fontsz--;
            $pdf->SetFontSize($fontsz);
        }
        $pdf->SetXY($x0 + 27,$y0 + 25);
        $pdf->writeHTML($email,false,0);

// Lower colored strip with long text
        $pdf->SetFillColor($fcol["R"],$fcol["G"],$fcol["B"]);
        $pdf->SetTextColor($tcol["R"],$tcol["G"],$tcol["B"]);
        $pdf->SetFont("FreeSerif","B",8);
        $pdf->SetXY($x0,$y0+33);
        $pdf->Cell(75,7,$strip,0,0,"C",1);

// Draw a gray frame around the card
        $pdf->Rect($x0,$y0,$w,$h);
        $resultat->MoveNext();
        $nb_card++;
    }
    $resultat->Close();
// Send PDF code to browser
    $_SESSION['galette']['pdf_error'] = false;
    $pdf->Output(_T("Cards").".pdf","D");
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
 
?>
