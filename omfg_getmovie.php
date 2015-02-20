<?php

// Load the TMDb API key.
require("secure/tmdbkey.php");
// Load the TMDb class object.
require("inc/TMDb/TMDb.php");

// Get langage.
$lang = $_GET["l"];
// Get movie code.
$code = $_GET["c"];

// Create a new TMDb object.
$tmdb = new TMDb($tmdbkey, $lang);
// First request a token from API
$token = $tmdb->getAuthToken();
// Request valid session for that particular user from API
$session = $tmdb->getAuthSession($token);

// Get movie from its code.
$data = $tmdb->getMovie($code);

// Initialization.
$countries = array();
$directors = array();
$actors = array();
$genres = array();
$covers = array();
$images = array();

if ($data['production_countries'] != null) {
  foreach($data['production_countries'] as $country) {
    $countries[] = $country['name'];
  }
}

if ($data['genres'] != null) {
  foreach($data['genres'] as $genre) {
    $genres[] = $genre['name'];
  }
}

// Get movie cast.
$movie_cast = $tmdb->getMovieCast($code);
$m_cast = $movie_cast['cast'];

// Set cast in the correct order.
function cmp_cast($a, $b) {
  return strcmp($a['order'], $b['order']);
}
usort($m_cast, "cmp_cast");

if ($m_cast != null) {
  foreach($m_cast as $member) {
    if (count($actors) < 5) {
      $actors[] = $member['name'];
    }
  }
}

if ($movie_cast['crew'] != null) {
  foreach($movie_cast['crew'] as $member) {
    if ($member['job'] == "Director") {
      $directors[] = $member['name'];
    }
  }
}

// Get movie images.
$movie_images = $tmdb->getMovieImages($code, false);
$image_url = '';

if ($movie_images['posters'] != null) {
  // Get images in the prefered langage at first.
  foreach($movie_images['posters'] as $image) {
    if ($image['iso_639_1'] == $lang) {
      //Get image URL for the backdrop image in its original size
      $image_url = $tmdb->getImageUrl($image['file_path'], TMDb::IMAGE_POSTER, 'w342');
      $covers[] = array( "thumb" => $image_url, "image" => $image_url);
    }
  }
  // If no images found in the prefered langage, get those without langage or in english.
  if ($image_url == '') {
    foreach($movie_images['posters'] as $image) {
      if (($image['iso_639_1'] == 'en') || ($image['iso_639_1'] == '') || ($image['iso_639_1'] == null)) {
        //Get image URL for the backdrop image in its original size
        $image_url = $tmdb->getImageUrl($image['file_path'], TMDb::IMAGE_POSTER, 'w342');
        $covers[] = array( "thumb" => $image_url, "image" => $image_url);
      }
    }
  }
}

if ($movie_images['backdrops'] != null) {
  foreach($movie_images['backdrops'] as $image) {
    //Get image URL for the backdrop image in its original size
    $image_url = $tmdb->getImageUrl($image['file_path'], TMDb::IMAGE_BACKDROP, 'original');
    $images[] = array( "thumb" => $image_url, "image" => $image_url);
  }
}

$output["movie"] = array(
  "title"       => $data['title'],
  "original"    => $data['original_title'],
  "year"        => substr($data['release_date'], 0, 4),
  "runtime"     => $data['runtime'],
  "description" => $data['tagline'],
  "summary"     => $data['overview'],
  "countries"   => $countries,
  "directors"   => $directors,
  "actors"      => $actors,
  "genres"      => $genres,
  "rating"      => $data['vote_average'],
  "covers"      => $covers,
  "images"      => $images,
);

echo json_encode($output);

?>