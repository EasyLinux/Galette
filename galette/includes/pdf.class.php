<?php
//
//  PDF class for galette
//
//

/**
 *  Require TCPDF class
 */
    require_once (WEB_ROOT."includes/tcpdf/tcpdf.php");

/**
 * PDF class for galette
 * Traps tcpdf errors by overloading tcpdf::error method
 * Adds convenient method to convert color html codes
 * @name PDF
 * @package galette
 * @abstract Class for extanding TCPDF.
 * @author     John Perr <johnperr@abul.org>
 * @copyright  2007 John Perr
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL License 2.0 or (at your option) any later version
 * @version    $Id$
 * @since      Disponible depuis la Release 0.63
 */

class PDF extends TCPDF
{
/**
* Constructeur de la classe PDF
* 
*/
    public function __construct() {
        parent::__construct();
    }

/**
* Destructeur de la classe PDF
* 
*/
    public function __destruct() {
        parent::__destruct();
    }

/**
 * This method is automatically called in case of fatal error;
 * it simply outputs the message and halts the execution.
 * An inherited class may override it to customize the error
 * handling but should always halt the script, or the resulting
 * document would probably be invalid.
 * 2004-06-11 :: Nicola Asuni : changed bold tag with strong
 * 2007-07-21 :: John Perr : changed function to return error to session
 * @access public
 * @param string $msg The error message
 * @since 1.0
 */
	public function Error($msg) {
        $_SESSION['galette']['pdf_error'] = TRUE;
        $_SESSION['galette']['pdf_error_msg'] = $msg;
        header("location:".$_SESSION['galette']['caller']);
        die();
	}
/**
 * Fonction de conversion d'une couleur au format HTML
 * #RRVVBB en un tableau de 3 valeurs comprises dans
 * l'interval [0;255]
 *
 * @param  cha�ne de 6 carat�res RRVVBB
 * @return tableau de 3 valeur R, G et B comprises entre 0 et 255
 * @access public
 */
    public function ColorHex2Dec($hex6) {
        $dec = array("R" => hexdec(substr($hex6,0,2)),
                     "G" => hexdec(substr($hex6,2,2)),
                     "B" => hexdec(substr($hex6,4,2)));
        return $dec;             
    }

/**
 * Extract info from a GIF file
 * (In fact: converts gif image to png and feed it to _parsepng)
 * @access protected
 */
	protected function _parsegif($file) {
		$a=GetImageSize($file);
		if(empty($a)) {
			$this->Error(_T('Missing or incorrect image file ').$file);
		}
		if($a[2]!=1) {
			$this->Error(_T('Not a GIF file ').$file);
		}

// Tentative d'ouverture du fichier
		if(function_exists('gd_info')) {
            $data = @imagecreatefromgif ($file);

// Test d'�chec & Affichage d'un message d'erreur
            if (!$data) {
			    $this->Error(_T('Error loading ').$file);
            }
            if (Imagepng($data,WEB_ROOT.'tempimages/gif2png.png')) {
     	        return $this->_parsepng(WEB_ROOT.'tempimages/gif2png.png');
	       } else {
			    $this->Error(_T('Error creating temporary png file from ').$file);
           }
        } else {
		    $this->Error(_T('Unable to convert GIF file ').$file);
        }
	}
}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
