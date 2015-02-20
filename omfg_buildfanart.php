<?php

// Include libraries.
require("inc/fn_template.php");

// Set specific header to avoid caching of data.
header("Expires: ".gmdate( "D, d M Y H:i:s" )." GMT" ); 
header("Last-Modified: ".gmdate( "D, d M Y H:i:s" )." GMT" ); 
header("Cache-Control: no-cache, must-revalidate" ); 
header("Pragma: no-cache" );
header("Content-type: text/x-json");

// Read POST parameters.
$movie     = $_POST["mid"];
$back_idx  = $_POST["bid"];
$cover_idx = $_POST["cid"];

$table = array( '’'=>'\'', 'œ'=>'oe' );

// Build an xml string, to be combined with the template.
$xmlstring = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<Movie xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">
  <Title>".htmlspecialchars(stripslashes(strtr($movie["title"], $table)))."</Title>
  <OriginalTitle>".htmlspecialchars(stripslashes(strtr($movie["original"], $table)))."</OriginalTitle>
  <Description>".htmlspecialchars(stripslashes(strtr($movie["description"], $table)))."</Description>
  <Plot>".htmlspecialchars(stripslashes(strtr($movie["summary"], $table)))."</Plot>
  <Year>".htmlspecialchars(stripslashes($movie["year"]))."</Year>
  <Runtime>".htmlspecialchars(stripslashes($movie["runtime"]))."</Runtime>
  <Genres><string>".htmlspecialchars(stripslashes(implode("</string><string>",$movie["genres"])))."</string></Genres>
  <Rating>".htmlspecialchars(stripslashes($movie["rating"]))."</Rating>
  <Actors><string>".htmlspecialchars(stripslashes(implode("</string><string>",$movie["actors"])))."</string></Actors>
  <Directors><string>".htmlspecialchars(stripslashes(implode("</string><string>",$movie["directors"])))."</string></Directors>
  <Countries><string>".htmlspecialchars(stripslashes(implode("</string><string>",$movie["countries"])))."</string></Countries>
  <Cover>".htmlspecialchars(stripslashes($cover_idx))."</Cover>
  <FanArt>".htmlspecialchars(stripslashes($back_idx))."</FanArt>
</Movie>";
  
$output["base64"] = apply_template("", $xmlstring, "", "", "string");

echo str_replace("\\", "", json_encode($output));

?>