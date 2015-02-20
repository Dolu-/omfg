<?php

// Load the TMDb API key.
require("secure/tmdbkey.php");
// Load the TMDb class object.
require("inc/TMDb/TMDb.php");

// Get langage.
$lang = $_GET["l"];
// Get Title.
$title = trim($_GET["q"]);

// Create a new TMDb object.
$tmdb = new TMDb($tmdbkey, $lang);
// First request a token from API
$token = $tmdb->getAuthToken();
// Request valid session for that particular user from API
$session = $tmdb->getAuthSession($token);

// Search movie from title.
$json_movies_result = $tmdb->searchMovie($title);

$output["movies"] = array();

foreach($json_movies_result['results'] as $movie) {

  if ($movie['poster_path'] == "") {
    // No cover found.
    $image_url = "img/nocover.png";
  } else {
    // Get image URL for the backdrop image in its original size
    $image_url = $tmdb->getImageUrl($movie['poster_path'], TMDb::IMAGE_POSTER, 'w342');
  }

  // Add movie to the result array.
  $output["movies"][] = array(
      "code"        => $movie['id'],
      "title"       => $movie['title'],
      "original"    => $movie['original_title'],
      "year"        => ($movie['release_date'] != null ? substr($movie['release_date'], 0, 4) : ""),
      "cover"       => $image_url,
  );
}

echo json_encode($output);

?>