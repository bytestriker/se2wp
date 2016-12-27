<?php
ini_set('default_charset', 'utf-8');
// Inclusión de archivos para manejar pendejadas de Social Engine
require_once("config.php");
require_once("../slug.php-master/src/Slug/Slugifier.php");

// @ objeto global para generar slugs donde haga falta.
$slugifier = new \Slug\Slugifier;
function slugifyES($text) {
  global $slugifier;
  $slugifier->setTransliterate(true);
  return $slugifier->slugify($text);
}


// Conexión a la base de datos
$db = new mysqli($DBServer, $DBUser, $DBPass, $DBName);
// verificar si hay conexión con la base de datos
if ($db->connect_errno) {
  echo "Error: Fallo al conectarse a MySQL debido a:", EOL;
  echo "Err: " . $db->error . EOL;
  exit;
}


$sqlGetSERecipes = "SELECT recipe_id, title, body, overview, category_id,
(SELECT category_name
  FROM engine4_recipe_categories recipe_categories
  WHERE recipe.category_id = recipe_categories.category_id) AS category_name
FROM engine4_recipe_recipes recipe LIMIT 1;";

echo " -- Importing social engine recipes".EOL;
echo " START TRANSACTION ".EOL;
echo " --  ".eol;
$dateInsert = date('Y-m-d H:i:s');
$db->query("SET NAMES utf8;");
if ($rsGetSERecipes = $db->query($sqlGetSERecipes)) {
  // Ejecuto si tengo registros
  if ($rsGetSERecipes->num_rows) {
    // Mientras el recordset pueda obtener objetos
    $sqlInsertPost = '';
    while ($rowSERecipe = $rsGetSERecipes->fetch_object()) {
      // echo "-- row:  ", print_r($rowSERecipe, 1), EOL;
      echo "-- title: ".slugifyES($rowSERecipe->title).EOL;
      $post_date = $dateInsert;
      $post_date_gmt = $dateInsert;
      $post_content = $rowSERecipe->body. "<br>". $rowSERecipe->overview;
      $post_title = $rowSERecipe->title;
      $post_excerpt = '';
      $post_status = 'draft';
      $comment_status = 'open';
      $ping_status = 'open';
      $post_password = '';
      $post_name = slugifyES($rowSERecipe->title);
      $to_ping = '';
      $pinged = '';
      $post_modified = '';
      $post_modified_gmt = '';
      $post_content_filtered = '';
      $post_parent = '';
      $guid = '';
      $menu_order = '0';
      $post_type = 'post';
      $post_mime_type = '';
      $comment_count = '0';
      $sqlInsertPost .= "INSERT INTO `wp5x_posts` (`post_date`, `post_date_gmt`, `post_content`, `post_title`,
        `post_excerpt`, `post_status`, `comment_status`, `ping_status`,
        `post_password`, `post_name`, `to_ping`, `pinged`,
        `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`,
        `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`)";
      $sqlInsertPost .= " VALUES ";
      $sqlInsertPost .= "('".mysqli_escape_string($db, $post_date)."','".mysqli_escape_string($db, $post_date_gmt)."','".mysqli_escape_string($db, $post_content)."','".mysqli_escape_string($db, $post_title)."'";
      $sqlInsertPost .= ",'".mysqli_escape_string($db, $post_excerpt)."','".mysqli_escape_string($db, $post_status)."','".mysqli_escape_string($db, $comment_status)."','".mysqli_escape_string($db, $ping_status)."'";
      $sqlInsertPost .= ",'".mysqli_escape_string($db, $post_password)."','".mysqli_escape_string($db, $post_name)."','".mysqli_escape_string($db, $to_ping)."','".mysqli_escape_string($db, $pinged)."'";
      $sqlInsertPost .= ",'".mysqli_escape_string($db, $post_modified)."','".mysqli_escape_string($db, $post_modified_gmt)."','".mysqli_escape_string($db, $post_content_filtered)."','".mysqli_escape_string($db, $post_parent)."'";
      $sqlInsertPost .= ",'".mysqli_escape_string($db, $guid)."','".mysqli_escape_string($db, $menu_order)."','".mysqli_escape_string($db, $post_type)."','".mysqli_escape_string($db, $post_mime_type)."','".mysqli_escape_string($db, $comment_count)."');";
      $sqlInsertPost .= EOL;
      // echo $sqlInsertPost.EOL;
    }
    echo "--  insert post:   ". EOL;
    echo $sqlInsertPost;

    echo " --- ".EOL;
  } else {
    echo "-- NOT ROWS ".EOL;
  }
} else {
  echo "-- NOT QUERY ".EOL;
}

echo " COMMIT; ";
 ?>
