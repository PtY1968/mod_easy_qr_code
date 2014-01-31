<?php
/**
 *  @Copyright
 *
 *  @package	Easy QR-Code Module
 *  @author     Peter Szladovics
 *  @version	Version: 1.0 - 30-Jan-2014
 *  @link       Project Site {@link https://github.com/PtY1968/mod_easy_qr_code}
 *
 *  @license GNU/GPL
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
defined('_JEXEC') or die('Restricted access');

require_once 'Image/QRCode.php';

class mod_easy_qr_codeHelper extends JObject
{

    public function createOutput($params)
    {

        // Convert hexstring to colour-byte
        function hex2rgb($hexstr, $rgb) {
            $int = hexdec($hexstr);
            switch($rgb) {
                case "r":
                    return 0xFF & $int >> 0x10;
                case "g":
                    return 0xFF & ($int >> 0x8);
                case "b":
                    return 0xFF & $int;
                default:
                    return 0;
            }
        }

        // Get base parameters
        $align = $params->get('align');
        $currenturl = $params->get('qr_text');
        if ($currenturl == "") $currenturl = JURI::current();
        $alt = $params->get('alt');
        if ($alt == "") {
          $alt=$currenturl;
        }

        // Generate the QR-Code
        $prot = $params->get('protect');
        $qr = new Image_QRCode();
        $gd_data = $qr->makeCode($currenturl, array (
            'image_type' => 'png',
            'output_type' => 'return',
            'error_correct' => $prot,
            'module_size' => '1' ));

        // Extract colours
        $fgc = $params->get('color');
        $pr = hex2rgb($fgc, "r");
        $pg = hex2rgb($fgc, "g");
        $pb = hex2rgb($fgc, "b");
        $bgc = $params->get('bgcolor');
        $br = hex2rgb($bgc, "r");
        $bg = hex2rgb($bgc, "g");
        $bb = hex2rgb($bgc, "b");

        // Calculate sizes and margin
        $gensize = imagesx($gd_data);
        $imgsize = $gensize-8;
        $size = $params->get('size');
        $mrg = $params->get('margin');
        if ($mrg > round($size/3)) $mrg = round($size/3);

        // Remove defaulr 4px margin
        $cd_data = imagecreate($imgsize, $imgsize);
        imagecopy($cd_data, $gd_data, 0, 0, 4, 4, $imgsize, $imgsize);
        imagedestroy($gd_data);

        // Resize the image without margin
        $qr_data = imagecreate($size-$mrg, $size-$mrg);
        imagecopyresized($qr_data, $cd_data, 0, 0, 0, 0, $size-$mrg-$mrg, $size-$mrg-$mrg, $imgsize, $imgsize);
        imagedestroy($cd_data);

        // Add the margin to the image
        $ed_data = imagecreate($size, $size);
        $fcolor = imagecolorallocate($ed_data, 255, 255, 255);
        imagefill($ed_data, 0, 0, $fcolor);
        imagecopy($ed_data, $qr_data, $mrg, $mrg, 0, 0, $size-$mrg-$mrg, $size-$mrg-$mrg);
        imagedestroy($qr_data);

        // Image recolour if needed
        $blk = imagecolorclosest($ed_data, 0, 0, 0);
        $wht = imagecolorclosest($ed_data, 255, 255, 255);
        if ($fgc == "-1") {
            imagecolortransparent($ed_data, $blk);
        } else {
            imagecolorset($ed_data, $blk, $pr, $pg, $pb);
        }
        if ($bgc == "-1") {
            imagecolortransparent($ed_data, $wht);
        } else {
            imagecolorset($ed_data, $wht, $br, $bg, $bb);
        }

        // Get PNG data to BASE64 string
        ob_start();
        imagepng($ed_data);
        $data = base64_encode(ob_get_clean());
        imagedestroy($ed_data);

        // Create output
        return "<div style=\"text-align: $align\"><img src=\"data:image/png;base64,$data\" alt=\"$alt\" title=\"$alt\" /></div>";
    }

}
