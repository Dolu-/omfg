var bLockResize = false,
    currentMovie = null,
    divMain,
    divContainer;

/*
 * Resize the accordion widget according to the size of the window (container).
 */
function window_onResize()
{
  if (!bLockResize) {
    bLockResize = true;
    var nh = $(window).height() - $("#divContainer").offset().top - 2;
    $("#divContainer").height(nh);
    divMain.accordion("refresh");
    bLockResize = false;
  }
}

/*
 * Display the movie fanart generated.
 */
function btnApply_onSuccess(data, textStatus, jqXHR)
{
  // Go to result.
  divMain.accordion("option", "active", 2);
  // Display generated fanart image.
  document.getElementById("backImg").src = "data:image/jpg;base64," + data.base64;
  document.getElementById("imgDownload").href = "data:image/jpg;base64," + data.base64;
  document.getElementById("imgDownload").download = currentMovie.title + ".jpg";
}

/*
 * Failure
 */
function btnApply_onFailure(data, textStatus, jqXHR)
{
  alert(textStatus);
}

/*
 * Generate fanart background image for current movie.
 */
function btnApply_onClick()
{
  if (currentMovie !== null) {
    document.getElementById("waiting_text").innerHTML = "Fanart generation...";
    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "omfg_buildfanart.php",
        data: {
                mid: currentMovie,
                cid: $("#cover_selection_list .ui-selected").attr('src'),
                bid: ($("#back_selection .ui-selected").attr('src') || "")
              }
    })
    .done(btnApply_onSuccess)
    .fail(btnApply_onFailure);
  }
  return false;
}

/*
 * Highlight selected cover.
 */
function cover_onClick()
{
  if (!$(this).hasClass('ui-selected')) {
    $("#cover_selection_list .ui-selected").removeClass("ui-selected");
    $(this).addClass('ui-selected');
    document.getElementById('selected_cover').src = this.src;
  }
}

/*
 * Highlight selected fanart background.
 */
function back_onClick()
{
  var bUnselect = $(this).hasClass('ui-selected');
  $("#back_selection .ui-selected").removeClass("ui-selected");
  if (!bUnselect) {
    $(this).addClass('ui-selected');
  }
}

/*
 * Display all contents about the selected movie.
 */
function result_onSuccess(data, textStatus, jqXHR)
{
  // Go to movie details.
  divMain.accordion("option", "active", 1);

  // Build HTML with movie details
  var temp = "<div class=\"text_search_result\">" +
             "<p><b>Title</b> : " + data.movie.title + " &bull; " +
                "<b>Original title</b> : " + data.movie.original + " &bull; " +
                "<b>Year</b> : " + data.movie.year + " &bull; " +
                "<b>Runtime</b> : " + data.movie.runtime + " &bull; " +
                "<b>Notation</b> : " + data.movie.rating + "</p>" +
             "<p><b>Summary</b> : <br />" + data.movie.summary + "</p>" +
             "<p><b>Genre</b> : " + data.movie.genres.join(", ") + "</p>" +
             "<p><b>Actors</b> : " + data.movie.actors.join(", ") + "</p>" +
             "<p><b>Director(s)</b> : " + data.movie.directors.join(", ") + "</p>" +
             "<p><b>Country</b> : " + data.movie.countries.join(", ") + "</p>" +
             "</div>";
  document.getElementById("text_selection").innerHTML = temp;
  
  // Display available covers.
  if (data.movie.covers.length > 0)
  {
    $("#cover_selection").html("<img id=\"selected_cover\" src=\"" + data.movie.covers[0].image + "\" width=\"154\" />");
    temp = "";
    for(var i = 0; i < Math.min(data.movie.covers.length, 3); i += 1) {
      temp += "<img id=\"cover_" + i + "\" src=\"" + data.movie.covers[i].thumb + "\" />";
    }
    cover_idx = (temp !== "" ? 0 : -1);
    $("#cover_selection").append("<div id=\"cover_selection_list\">" + temp + "</div>");
    $("#cover_0").addClass("ui-selected");
  }
  // Display available backgrounds.
  temp = "";
  for(var i = 0; i < data.movie.images.length; i += 1) {
    temp += "<img id=\"back_" + i + "\" src=\"" + data.movie.images[i].thumb + "\" width=\"180\" height=\"102\"/>";
  }
  // No background selected.
  $("#back_selection").html(temp).css({width: (182 * (i + 1)) + "px"});
  
  // Store current movie data to global variable.
  currentMovie = data.movie;
}

/*
 * Movie selection.
 */
function result_onClick()
{
  document.getElementById("waiting_text").innerHTML = "Get movie details...";
  $.getJSON("omfg_getmovie.php", {l: $("#search_lang").val(), c: this.id.substring(1)}, result_onSuccess );    
  return false;
}

/*
 * Display the result of the movie query.
 * (in case of success of the ajax call).
 */
function search_onSuccess(data, textStatus, jqXHR)
{
  var movie_count = data.movies.length,
      searchResult = document.getElementById("search_result"),
      i, movie;
  if (movie_count < 1) {
    searchResult.innerHTML = "No movie found!<br />";
  } else if (movie_count == 1) {
    searchResult.innerHTML = "1 movie found.<br />";
  } else {
    searchResult.innerHTML = movie_count + " movies found.<br />";
  }
  for (i = 0; i < movie_count; i += 1) {
    movie = data.movies[i];
    searchResult.insertAdjacentHTML("beforeend",
                                    "<div class=\"result_item\" id=\"k" + movie.code + "\">" +
                                      "<div class=\"result_item_text\">" + movie.original + "</div>" +
                                      (movie.year !== "" ? "<div class=\"result_item_year\">(" + movie.year + ")</div>" : "") +
                                      "<img src=\"" + (movie.cover !== "" ? movie.cover : "images/nocover.png") + "\" height=\"180\" " +
                                      "alt=\"" + movie.original + "\" title=\"" + movie.original + "\">" +
                                    "</div>");
  }
}

/*
 * Movie query.
 */
function search_onClick()
{
  document.getElementById("waiting_text").innerHTML = "Search in progress...";
  $.getJSON("omfg_searchmovie.php", {l: $("#search_lang").val(), q: document.getElementById("search_title").value}, search_onSuccess);
  return false;
}

$(function() {

  divMain = $("#divMain");
    
  // Set the accordion widget (jQuery-ui component).
  divMain.accordion({ header: "h3", active: 0, heightStyle: "fill" });

  // Force the first resize.
  window_onResize();

  // Resize the content of the window according to its size.
  $(window).resize(window_onResize);

  $("#search_submit").button({
      text: false,
      icons: {
        primary: "ui-icon-search"
      }
  }).click(search_onClick); // Clic sur la recherche de film.

  // Item (movie) selection in the result list.
  $("#search_result").on("click", ".result_item", result_onClick);

  // Background selection (highlight).
  $("#back_selection").on("click", "img", back_onClick);

  // Cover selection (highlight).
  divMain.on("click", "#cover_selection_list img", cover_onClick);

  // Generate the final background with all selected images.
  $("#btnApply").button().click(btnApply_onClick);
  
  $("#imgDownload").button();
  
  $("#dialog-message").dialog({
      autoOpen : false,
      modal: true,
      width: 500,
      buttons: {
        Ok: function() {
          $(this).dialog("close");
        }
      }
    });  
  
  $("#btnAbout").button({
      icons: {
        primary: "ui-icon-info"
      }
  }).click(function(){
    $("#dialog-message").dialog("open");
  });
  
  $("#search_lang").val(window.navigator.userLanguage || window.navigator.language);
  
  // Display the "modal" popup during ajax calls.
  $(document).ajaxStart(function(){
    document.getElementById("search_loading").style.display = 'block';
  }).ajaxStop(function(){
    document.getElementById("search_loading").style.display = 'none';
  });
  
});
