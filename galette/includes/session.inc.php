<?php

// Copyright © 2003 Frédéric Jaqcuot
// Copyright © 2007-2008 Johan Cwiklinski
//
// This file is part of Galette (http://galette.tuxfamily.org).
//
// Galette is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Galette is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Galette. If not, see <http://www.gnu.org/licenses/>.

/**
 * Gestion de la session
 *
 * @package Galette
 * 
 * @author     Frédéric Jaqcuot
 * @copyright  2003 Frédéric Jaqcuot
 * @copyright  2007-2008 Johan Cwiklinski
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version    $Id$
 */

if (!isset(
	$_SESSION["logged_status"]) || 
	isset($_POST["logout"]) ||
	isset($_GET["logout"]))
{
	if (
		isset($_POST["logout"]) ||
		isset($_GET["logout"])){
		dblog(_("Log off"));
		$_SESSION['galette']['db'] = null;
		unset($_SESSION['galette']['db']);
	}
	$_SESSION["admin_status"]=0;
	$_SESSION["logged_status"]=0;
	$_SESSION["logged_id_adh"]=0;
	$_SESSION["logged_nom_adh"]="";
	$_SESSION["filtre_adh_nom"]="";
	$_SESSION["filtre_adh"]=0;
	$_SESSION["filtre_adh_2"]=1;
	$_SESSION["filtre_date_cotis_1"]="";
	$_SESSION["filtre_date_cotis_2"]="";
	$_SESSION["tri_adh"]=0;
	$_SESSION["tri_adh_sens"]=0;
	$_SESSION["tri_log"]=0;
	$_SESSION["tri_log_sens"]=0;
	$_SESSION["filtre_cotis"]=0;
	$_SESSION["tri_cotis"]=0;
	$_SESSION["tri_cotis_sens"]=1;
	$_SESSION["filtre_cotis_adh"]="";
	if(!isset($_SESSION['pref_lang']))
		$_SESSION["pref_lang"]=PREF_LANG;
}
?>
