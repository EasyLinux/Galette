<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Création des cartes d'adhérents au format PDF
 *
 * La création des cartes au format pdf se fait soit
 * - depuis la page de gestion des adhérents en sélectionnant
 *   les adhérents  dans la liste
 * - depuis la page de visualisation d'un adhérent. Une seule
 *   carte est alors générée
 *
 * Les couleurs sont définies dans l'écran des préférences
 * en utilisant des codes identiques à ceux utilisés en HTML.
 *
 * PHP version 5
 *
 * Copyright © 2007-2014 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Print
 * @package   Galette
 *
 * @author    John Perr <johnperr@abul.org>
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2007-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2007-07-09
 */

use Galette\IO\Pdf;
use Analog\Analog;
use Galette\Repository\Members;

/** @ignore */
require_once 'includes/galette.inc.php';

if ( !$login->isLogged() ) {
    header("location: index.php");
    die();
}

if ( isset($session['filters']['members']) ) {
    $filters =  unserialize($session['filters']['members']);
} else {
    $filters = new Galette\Filters\MembersList();
}

if ( isset($_GET[Galette\Entity\Adherent::PK])
    && $_GET[Galette\Entity\Adherent::PK] > 0
) {
    // If we are called from "voir_adherent.php" get unique id value
    $unique = $_GET[Galette\Entity\Adherent::PK];
} else {
    if ( count($filters->selected) == 0 ) {
        Analog::log('No member selected to generate members cards', Analog::INFO);
        if ( $login->isAdmin() || $login->isStaff() ) {
            header('location:gestion_adherents.php');
            die();
        } else {
            header('location:voir_adherent.php');
            die();
        }
        die();
    }
}

// Fill array $mailing_adh with selected ids
$mailing_adh = array();
if ( isset($unique) && $unique ) {
    $mailing_adh[] = $unique;
} else {
    $mailing_adh = $filters->selected;
}

$m = new Members();
$members = $m->getArrayList(
    $mailing_adh,
    array('nom_adh', 'prenom_adh'),
    true
);

if ( !is_array($members) || count($members) < 1 ) {
    Analog::log('An error has occured, unable to get members list.', Analog::ERROR);
    die();
}

// Set PDF headings
$doc_title    = _T("Member's Cards");
$doc_subject  = _T("Generated by Galette");
$doc_keywords = _T("Cards");

// Get fixed data from preferences
// and convert strings to utf-8 for tcpdf
$an_cot = '<strong>' . $preferences->pref_card_year . '</strong>';
$abrev = '<strong>' . $preferences->pref_card_abrev . '</strong>';

$print_logo = new Galette\Core\PrintLogo();
if ( $logo->hasPicture() ) {
    $logofile = $print_logo->getPath();

    // Set logo size to max width 30 mm or max height 25 mm
    $ratio = $print_logo->getWidth()/$print_logo->getHeight();
    if ( $ratio < 1 ) {
        if ( $print_logo->getHeight() > 16 ) {
            $hlogo = 25;
        } else {
            $hlogo = $print_logo->getHeight();
        }
        $wlogo = round($hlogo*$ratio);
    } else {
        if ( $print_logo->getWidth() > 16 ) {
            $wlogo = 30;
        } else {
            $wlogo = $print_logo->getWidth();
        }
        $hlogo = round($wlogo/$ratio);
    }
}

// Create new PDF document
$pdf = new Pdf($preferences);

// Set document information
$pdf->SetTitle($doc_title);
$pdf->SetSubject($doc_subject);
$pdf->SetKeywords($doc_keywords);

// No hearders and footers
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);
$pdf->setFooterMargin(0);
$pdf->setHeaderMargin(0);

// Show full page
$pdf->SetDisplayMode('fullpage');

// Disable Auto Page breaks
$pdf->SetAutoPageBreak(false, 0);

// Set colors
$pdf->SetDrawColor(160, 160, 160);
$pdf->SetTextColor(0);
$tcol = $pdf->colorHex2Dec($preferences->pref_card_tcol);
$scol = $pdf->colorHex2Dec($preferences->pref_card_scol);
$bcol = $pdf->colorHex2Dec($preferences->pref_card_bcol);
$hcol = $pdf->colorHex2Dec($preferences->pref_card_hcol);

// Set margins
$pdf->SetMargins(
    $preferences->pref_card_marges_h,
    $preferences->pref_card_marges_v
);

// Set font
$pdf->SetFont(Pdf::FONT);

// Set origin
// Top left corner
$xorigin = $preferences->pref_card_marges_h;
$yorigin = $preferences->pref_card_marges_v;

// Card width
$w = 75;
// Card heigth
$h = 40;
// Number of colons
$nbcol=2;
// Number of rows
$nbrow=6;
// Spacing betweeen cards
$hspacing = $preferences->pref_card_hspace;
$vspacing = $preferences->pref_card_vspace;

//maximum size for visible text. May vary with fonts.
$max_text_size = 80;

$year_font_size = 8;

// Loop over cards
$nb_card=0;
foreach ( $members as $member ) {
    // Detect page breaks
    if ( $nb_card % ($nbcol * $nbrow)==0 ) {
        $pdf->AddPage();
    }

    // Compute card position on page
    $col=$nb_card % $nbcol;
    $row=($nb_card/$nbcol) % $nbrow;
    // Set origin
    $x0 = $xorigin + $col*(round($w)+round($hspacing));
    $y0 = $yorigin + $row*(round($h)+round($vspacing));
    // Logo X position
    $xl = round($x0 + $w - $wlogo);
    // Get data
    $email = '<strong>';
    switch ( $preferences->pref_card_address ) {
    case 0:
        $email .= $member->email;
        break;
    case 1:
        $email .= $member->msn;
        break;
    case 2:
        $email .= $member->jabber;
        break;
    case 3:
        $email .= $member->website;
        break;
    case 4:
        $email .= $member->icq;
        break;
    case 5:
        $email .= $member->zipcode . ' - ' . $member->town;
        break;
    case 6:
        $email .= $member->nickname;
        break;
    case 7:
        $email .= $member->job;
        break;
    }
    $email .= '</strong>';

    // Select strip color according to status
    switch ( $member->status ) {
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

    $id = '<strong>' . $member->id . '</strong>';
    $nom_adh_ext = '<strong>' .
        (( $preferences->pref_bool_display_title ) ? $member->stitle . ' ' : '') .
        $member->sname . '</strong>';
    $photo = $member->picture;
    $photofile = $photo->getPath();

    // Photo 100x130 and logo
    $pdf->Image($photofile, $x0, $y0, 25);
    $pdf->Image($logofile, $xl, $y0, round($wlogo));

    // Color=#8C8C8C: Shadow of the year
    $pdf->SetTextColor(140);
    $pdf->SetFontSize($year_font_size);
    $pdf->SetXY($x0 + 65, $y0 + $hlogo);
    $pdf->writeHTML($an_cot, false, 0);

    // Colored Text (Big label, id, year)
    $pdf->SetTextColor($fcol['R'], $fcol['G'], $fcol['B']);

    $pdf->SetFontSize(8);
    $pdf->SetXY($x0 + 69, $y0 + 28);
    $pdf->writeHTML($id, false, 0);
    $pdf->SetFontSize($year_font_size);
    $pdf->SetXY($x0 + 64.7, $y0 + $hlogo - 0.3);
    $pdf->writeHTML($an_cot, false, 0);

    // Abbrev: Adapt font size to text length
    $fontsz = 12;
    $pdf->SetFontSize($fontsz);
    while ( $pdf->GetStringWidth($abrev) > $max_text_size ) {
        $fontsz--;
        $pdf->SetFontSize($fontsz);
    }
    $pdf->SetXY($x0 + 27, $y0 + 12);
    $pdf->writeHTML($abrev, true, 0);

    // Name: Adapt font size to text length
    $pdf->SetTextColor(0);
    $fontsz = 8;
    $pdf->SetFontSize($fontsz);
    while ( $pdf->GetStringWidth($nom_adh_ext) > $max_text_size ) {
        $fontsz--;
        $pdf->SetFontSize($fontsz);
    }
    $pdf->SetXY($x0 + 27, $pdf->getY() + 4);
    //$pdf->setX($x0 + 27);
    $pdf->writeHTML($nom_adh_ext, true, 0);

    // Email (adapt too)
    $fontsz = 6;
    $pdf->SetFontSize($fontsz);
    while ( $pdf->GetStringWidth($email) > $max_text_size ) {
        $fontsz--;
        $pdf->SetFontSize($fontsz);
    }
    $pdf->setX($x0 + 27);
    $pdf->writeHTML($email, false, 0);

    // Lower colored strip with long text
    $pdf->SetFillColor($fcol['R'], $fcol['G'], $fcol['B']);
    $pdf->SetTextColor($tcol['R'], $tcol['G'], $tcol['B']);
    $pdf->SetFont(Pdf::FONT, 'B', 6);
    $pdf->SetXY($x0, $y0 + 33);
    $pdf->Cell($w, 7, $preferences->pref_card_strip, 0, 0, 'C', 1);

    // Draw a gray frame around the card
    $pdf->Rect($x0, $y0, $w, $h);
    $nb_card++;
}

// Send PDF code to browser
$session['pdf_error'] = false;
$pdf->Output(_T("Cards") . '.pdf', 'D');
