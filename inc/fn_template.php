<?php

// Include libraries.
include("inc/imagettftextboxopt.php");

DEFINE('CORNER_TOP_LEFT', 1);
DEFINE('CORNER_TOP_RIGHT', 2);
DEFINE('CORNER_BOTTOM_LEFT', 4);
DEFINE('CORNER_BOTTOM_RIGHT', 8);
DEFINE('CORNER_LEFT', 16);
DEFINE('CORNER_RIGHT', 32);
DEFINE('CORNER_TOP', 64);
DEFINE('CORNER_BOTTOM', 128);
DEFINE('CORNER_ALL', 255);

/*
 *	Convert an utf-8 string to its equivalent in HTML,
 *	to be used by GD functions.
 */
function utf8_to_numeric_html($source)
{
	$source = utf8_decode($source);
	$dest = "";
	// Look for accentuated characters.
	for($i = 0, $len = strlen($source); $i < $len; $i++){
		// Every symbol upon 127 are converted to numeric HTML.
		if (ord($source[$i]) > 127) {
			$dest .= '&#'.ord($source[$i]).';';
		} else {
			$dest .= $source[$i];
		}
	}
	return $dest;
}

/*
 * Draw a gradient rectangle (vertical only).
 * (from : http://www.php.net/manual/en/book.image.php#97137)
*/
function gradient_region($img, $x, $y, $image_width, $image_height, $c1, $c2)
{
  // first: lets type cast;
  list($c1_r, $c1_g, $c1_b) = sscanf($c1, '#%2x%2x%2x');
  list($c2_r, $c2_g, $c2_b) = sscanf($c2, '#%2x%2x%2x');
  $c1_r = (int) $c1_r;
  $c1_g = (int) $c1_g;
  $c1_b = (int) $c1_b;
  $c2_r = (int) $c2_r;
  $c2_g = (int) $c2_g;
  $c2_b = (int) $c2_b;

  // make the gradient
  for($i = 0; $i < $image_height; $i++) {
    $color_r = floor($i * ($c2_r - $c1_r) / $image_height) + $c1_r;
    $color_g = floor($i * ($c2_g - $c1_g) / $image_height) + $c1_g;
    $color_b = floor($i * ($c2_b - $c1_b) / $image_height) + $c1_b;
    imageline($img, 0, $i, $image_width, $i, imagecolorallocate($img, $color_r, $color_g, $color_b));
  }
}

/*
 * Apply transparent rounded corners to image.
 * (from http://stackoverflow.com/questions/5766865/rounded-transparent-smooth-corners-using-imagecopyresampled-php-gd)
*/
function imageCreateCorners($src, $width, $height, $radius, $corners)
{
  // find unique color
  do {
    $r = rand(0, 255);
    $g = rand(0, 255);
    $b = rand(0, 255);
  } while (imagecolorexact($src, $r, $g, $b) < 0);

  $img = imagecreatetruecolor($width, $height);
  $alphacolor = imagecolorallocatealpha($img, $r, $g, $b, 127);
  imagealphablending($img, false);
  imagesavealpha($img, true);
  imagefilledrectangle($img, 0, 0, $width, $height, $alphacolor);

  imagefill($img, 0, 0, $alphacolor);
  imagecopyresampled($img, $src, 0, 0, 0, 0, $width, $height, $width, $height);

  // Top left corner.
  if ($corners & CORNER_TOP_LEFT || $corners & CORNER_LEFT || $corners & CORNER_TOP) {
    imagearc($img, $radius-1, $radius-1, $radius*2, $radius*2, 180, 270, $alphacolor);
    imagefilltoborder($img, 0, 0, $alphacolor, $alphacolor);
  }
  
  // Top right corner.
  if ($corners & CORNER_TOP_RIGHT || $corners & CORNER_RIGHT || $corners & CORNER_TOP) {
    imagearc($img, $width-$radius, $radius-1, $radius*2, $radius*2, 270, 0, $alphacolor);
    imagefilltoborder($img, $width-1, 0, $alphacolor, $alphacolor);
  }
  
  // Bottom left corner.
  if ($corners & CORNER_BOTTOM_LEFT || $corners & CORNER_LEFT || $corners & CORNER_BOTTOM) {
    imagearc($img, $radius-1, $height-$radius, $radius*2, $radius*2, 90, 180, $alphacolor);
    imagefilltoborder($img, 0, $height-1, $alphacolor, $alphacolor);
  }
  
  // Bottom right corner.
  if ($corners & CORNER_BOTTOM_RIGHT || $corners & CORNER_RIGHT || $corners & CORNER_BOTTOM) {
    imagearc($img, $width-$radius, $height-$radius, $radius*2, $radius*2, 0, 90, $alphacolor);
    imagefilltoborder($img, $width-1, $height-1, $alphacolor, $alphacolor);
  }
  
  imagealphablending($img, true);
  imagecolortransparent($img, $alphacolor);

  // output image
  $res = $img;
  imagedestroy($src);

  return $res;
}

/*
 * Combine XML details from a movie with a template to create an image.
 * (Note : An existing version of this function works with files, it allows to
 * to apply others templates without loading again details of the movie from TMDb.)
 * 
 *   $filemovie      : Filename to a file with the XML details for the movie.
 *   $xmlmovie       : XML content used instead of using $filemovie.
 *   $fanart_path    : Path where to save the resulting image (when outputmode == "file").
 *   $image_filename : Ouput file name (when outputmode == "file").
 *   $outputmode     : Generation mode (output, string or file).
 *   $filetemplate   : Complete path and filename of the template.
 */
function apply_template($filemovie                            
                       ,$xmlmovie = ""                        
                       ,$fanart_path = ""                     
                       ,$image_filename                       
                       ,$outputmode = "output"                
                       ,$filetemplate = "./xml/default.xml")  
{

  // Get the template file as string, we'll do replace on the fly.
  $strtemplate = file_get_contents($filetemplate, true);

  if ($xmlmovie == "") {
    // Load the XML of the movie from a file.
    $xmlmovie = @simplexml_load_file($filemovie);
  } else {
    // Load the XML of the movie from a string.
    $xmlmovie = @simplexml_load_string($xmlmovie);
  }
  
  if ($xmlmovie === false) {
  
    // TODO : Put some default values.
    
  } else {

    foreach($xmlmovie as $key_movie => $val_movie) {
   
      $new_string = $val_movie;
      // Transform arrays to string.
      foreach($val_movie->string as $str => $val) {
        $new_string .= $val."\n";
      }
      // Convert to HTML and remove last character.
      $new_string = str_replace("\n", "&lt;br /&gt;", htmlspecialchars(trim($new_string, " \n")));

      // Specific calculation for the "rating" item.
      if ($key_movie == "Rating") {

        // Callback function for rating.
        $replace_rating = create_function('$matches',
          "\$ret = (float) $val_movie * (\$matches[1] == \"*\" ? \$matches[2] : 1 / \$matches[2]);
          return (\$matches[3] != \"f\" ? intval(\$ret) : \$ret);");
        $strtemplate = preg_replace_callback("/%RATING(\*|\/)(\d+)(f*)%/i", $replace_rating, $strtemplate);
      }
      
      // Search and replace.
      $pattern = "/%".strtoupper($key_movie)."%/i";
      $strtemplate = preg_replace($pattern, $new_string, $strtemplate);
    }
  }

  // Load the XML of the template after it has been updated above.
  $xmltemplate = simplexml_load_string($strtemplate);
  
  // Get dimension of the template.
  $width  = (int) $xmltemplate->attributes()->Width;
  $height = (int) $xmltemplate->attributes()->Height;

  if ($width  == 0) $width = 1280;
  if ($height == 0) $width = 720;
  
  // Create image to work with.
  $image = imagecreatetruecolor($width, $height);

  // Go through each item of the template (in order of appearance)
  // and do the required process.
  foreach($xmltemplate as $itemTemplate => $itemValues)
  {
    // Read commons values.
    $x       = (int) $itemValues->attributes()->X;
    $y       = (int) $itemValues->attributes()->Y;
    $width   = (int) $itemValues->attributes()->Width;
    $height  = (int) $itemValues->attributes()->Height;
    $radius  = (int) $itemValues->attributes()->Radius;
    $opacity = (int) $itemValues->attributes()->Opacity;
    $fill    =       $itemValues->attributes()->FillColor;
    $fillfr  =       $itemValues->attributes()->FillColorFrom;
    $fillto  =       $itemValues->attributes()->FillColorTo;
    $corners =       $itemValues->attributes()->Corners;
    $source  = strtolower($itemValues->attributes()->Source);
    $data    = (string) $itemValues->attributes()->SourceData;
    // Set color to RGB.
    list($r, $g, $b) = sscanf($fill, '#%2x%2x%2x');
    
    switch(strtolower($corners)) {
      case "all"         : $corners = CORNER_ALL;          break;
      case "left"        : $corners = CORNER_LEFT;         break;
      case "top"         : $corners = CORNER_TOP;          break;
      case "right"       : $corners = CORNER_RIGHT;        break;
      case "bottom"      : $corners = CORNER_BOTTOM;       break;
      case "topleft"     : $corners = CORNER_TOP_LEFT;     break;
      case "bottomleft"  : $corners = CORNER_BOTTOM_LEFT;  break;
      case "topright"    : $corners = CORNER_TOP_RIGHT;    break;
      case "bottomright" : $corners = CORNER_BOTTOM_RIGHT; break;
    }  

    switch ($itemTemplate) {

      case "ImageElement" :

        // Load image from base64 string or an Url.
        if ((($source == "base64") && (($data == "") || !($imageitem = @imagecreatefromstring(base64_decode($data)))))
         || (($source == "url")    && (($data == "") || !($imageitem = @imagecreatefromjpeg($data))))) {
          // Fall into default values for background.
          $source = "file";
          $data = "xml/default_background.jpg";
        }
          
        // Load image from a file, so we check if it exists.
        if (($source == "file") && (!file_exists(utf8_decode($data)) || !($imageitem = @imagecreatefromjpeg($data)))) {
          // Create empty image with black background.
          $source = "";
        }
        
        if (($source != "url") && ($source != "file") && ($source != "base64")) {
          // Create empty image with black background.
          $imageitem = imagecreatetruecolor($width, $height);
          imagefill($imageitem, 0, 0, imagecolorallocate($imageitem, 20, 20, 20));
        }
        
        $imageitem_w = imagesx($imageitem);
        $imageitem_h = imagesy($imageitem);
        
        // Resize the image if needed
        if (($imageitem_w != $width) && ($imageitem_h != $height)) {
          $image_p = imagecreatetruecolor($width, $height);
          imagecopyresampled($image_p, $imageitem, 0, 0, 0, 0, $width, $height, $imageitem_w, $imageitem_h);
          imagedestroy($imageitem);
          $imageitem = $image_p;
        }
        
        // Set rounded corners if needed.
        if ($radius != 0) {
          // Put transparent rounded corners.
          $imageitem = imageCreateCorners($imageitem, $width, $height, $radius, $corners);
        }
        
        // Merge the image item with the current image.
        imagecopymerge($image, $imageitem, $x, $y, 0, 0, $width, $height, $opacity);

        imagedestroy($imageitem);
        
        break;

      case "RectangleElement" :
      case "GradientElement" :
        
        if ($radius == 0) {
          
          if ($itemTemplate == "RectangleElement") {
            // Draw a "simple" rectangle directly onto the canvas.
            imagefilledrectangle($image, $x, $y, $x + $width, $y + $height,
                                 imagecolorallocatealpha($image, $r, $g, $b, $opacity));
          } else {
            // Draw a gradient rectangle onto the canvas.
            gradient_region($image, 0, 0, $width, $height, $fillfr, $fillto);
          }
        } else {

          // Create a rectangle with rounded corners.
          $rectangle = imagecreatetruecolor($width, $height);
          // Fill the rectangle with the requested color.
          imagefilledrectangle($rectangle, 0, 0, $width, $height,
                               imagecolorallocate($rectangle, $r, $g, $b));
          if ($itemTemplate == "GradientElement") {
            gradient_region($rectangle, 0, 0, $width, $height, $fillfr, $fillto);
          }
          // Put transparent rounded corners.
          $rectangle = imageCreateCorners($rectangle, $width, $height, $radius, $corners);
          // Merge the image with the current canvas.
          imagecopymerge($image, $rectangle, $x, $y, 0, 0, $width, $height, $opacity);
          imagedestroy($rectangle);
          
        }
        
        break;

      case "PolygonElement" :
        
        $a = array();
        $a[] = (int) $itemValues->attributes()->X1;
        $a[] = (int) $itemValues->attributes()->Y1;
        $a[] = (int) $itemValues->attributes()->X2;
        $a[] = (int) $itemValues->attributes()->Y2;
        $a[] = (int) $itemValues->attributes()->X3;
        $a[] = (int) $itemValues->attributes()->Y3;
        $a[] = (int) $itemValues->attributes()->X4;
        $a[] = (int) $itemValues->attributes()->Y4;
        
        // Draw a polygon onto the canvas.
        imagefilledpolygon($image, $a, 4, imagecolorallocatealpha($image, $r, $g, $b, $opacity));

        break;
        
      case "TextElement" :
        // This item has some more specific values.
        
        $fontsize   = (int) $itemValues->attributes()->FontSize;
        $fontname   = $itemValues->attributes()->FontName;
        $fontcolor  = $itemValues->attributes()->FontColor;
        $text       = $itemValues->attributes()->Text;
        $lineheight = (int) $itemValues->attributes()->LineHeight;
        $texthalign = strtolower($itemValues->attributes()->TextAlign);
        $textvalign = strtolower($itemValues->attributes()->TextVerticalAlign);
        $show3dots  = (strtolower($itemValues->attributes()->Show3Dots) != "false");
        if ($texthalign == "") $texthalign = "right";
        if ($textvalign == "") $textvalign = "top";
        list($r, $g, $b) = sscanf($fontcolor, '#%2x%2x%2x');
        $fontname = "ttf/".$fontname.".ttf";
        if (!file_exists($fontname)) $fontname = "./ttf/DejaVuSans.ttf";
        $text = strip_tags(str_ireplace("<br/>", "\n", str_ireplace("<br />", "\n", $text)));
        
        imagettftextboxopt($image, $fontsize, 0,
                           $x, $y,
                           imagecolorallocate($image, $r, $g, $b),
                           $fontname,
                           utf8_to_numeric_html($text),
                           array( 'width'         => $width,
                                  'height'        => $height,
                                  'line_height'   => $lineheight,
                                  'align'         => $texthalign,
                                  'valign'        => $textvalign,
                                  'show_3dots'    => $show3dots,
                                  'use_mbstring'  => true
                          ));
        
        break;

      default :
        
        break;
     }
  }

  // Return result according to selected output mode.
  if ($outputmode == "output") {
  
    // Return result as an image (jpg) directly to the browser.
    header("Content-Type: image/jpeg");
    imagejpeg($image);
    
  } else if ($outputmode == "string") {
  
    // start buffering
    ob_start();
    imagejpeg($image);
    $imageString = ob_get_contents();
    ob_end_clean();
    
    // Return result as a base64 string.
    $outputmode = base64_encode($imageString);
    
  } else if ($outputmode == "file") {
  
    // Save result to a file.
    imagejpeg($image, $fanart_path."/".$image_filename, 90);
    
  }

  imagedestroy($image);
  
  return $outputmode;
}

?>