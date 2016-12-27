<?php

require_once("config.php");
require_once("Encode.php");
require_once("slugifier.php");

// Conexión a la base de datos
$db = new mysqli($DBServer, $DBUser, $DBPass, $DBName);
// verificar si hay conexión con la base de datos
if ($db->connect_errno) {
  echo "Error: Fallo al conectarse a MySQL debido a:", EOL;
  echo "Errno: " . $db->connect_errno . EOL;
  echo "Error: " . $db->connect_error . EOL;
  exit;
}

// @ objeto global para generar slugs donde haga falta.
$slugifier = new \Slug\Slugifier;
function slugifyES($text) {
  global $slugifier;
  $slugifier->setTransliterate(true);
  return $slugifier->slugify($text);
}
echo " -- Importing content from Social Engine 4 to Wordpress. Just a hobbie".EOL;
echo " START TRANSACTION ".EOL;
echo " -- Adding records to WP Terms structure ".eol;
// Consulta para sacar las categorías padre
$sqlCategoriesFromSocialEngine = "SELECT Categories.category_name, Categories.category_id, Categories.parent_id
FROM engine4_article_articles AS Articles INNER JOIN engine4_article_categories AS Categories
ON (Articles.category_id = Categories.category_id)
WHERE Categories.parent_id = 0
GROUP BY Categories.category_name
ORDER BY Categories.parent_id, Categories.category_name DESC;";

// Creo un arreglo para simular la lógice de inserción de categorías y tenerlas disponibles cuando inserte los posts y los metas correspondientes.
$arrWPCategory = array();
$cont; // Soy verga y quiero contar todo
//inicio siempre en 20 que es el último ID que tengo en la tabla de WP
$contCategory = 20;
// ejecuto la consulta
if ($rsSocialEngineCategories = $db->query($sqlCategoriesFromSocialEngine)) {
  // Ejecuto si tengo registros
  if ($rsSocialEngineCategories->num_rows) {
    // Mientras el recordset pueda obtener objetos
    while ($rowSECategory = $rsSocialEngineCategories->fetch_object()) {
      // un recordset MANUAL para cada categoría
      // La estructura es para un objeto tipo categoría creado al vuelo
      $stdCategory = new stdClass();
      $stdCategory->term_id = $contCategory;
      $stdCategory->se_category_id = $rowSECategory->category_id;
      $stdCategory->se_parent_id = $rowSECategory->parent_id;
      $stdCategory->name = \ForceUTF8\Encoding::toUTF8($rowSECategory->category_name);
      // Le hago una estructura para sus hijitos.
      $stdCategory->children = array();
      // hago el slug antes del escape para que no meta caracteres escapados como parte del slug.
      $cleanSlug =$stdCategory->slug  = slugifyES($stdCategory->name);
      // Paso el nombre a la variable para insertar.
      $cleanName = $stdCategory->name = mysqli_escape_string($db, $stdCategory->name);
      // Asigno otra posición al arreglo de categorías que se van a registrar en WP
      $arrWPCategory[] = $stdCategory;
      // Te aviso para que no tre me apendejes



      echo " Creating Category Parent {$cleanSlug}, term_id: {$contCategory}, se_category: {$stdCategory->se_category_id}".EOL;

      /**
      @TODO: INSERT SQL. YOU MUST INSERT A ROW HERE, CHECK THE CONTEXT FOR PARENT CATEGORIES
      */


      // Consulto acerca de las categorías hijo para este registro
      $sqlFindCategoryChildren = "SELECT Categories.category_name, Categories.category_id, Categories.parent_id
      FROM engine4_article_articles AS Articles INNER JOIN engine4_article_categories AS Categories
      ON (Articles.category_id = Categories.category_id)
      WHERE Categories.parent_id = {$stdCategory->se_category_id}
      GROUP BY Categories.category_name
      ;".EOL;
      // Ejecuto la consulta.
      if ($rsSECategoryChildren = $db->query($sqlFindCategoryChildren)) {
        // Si tiene registros ejecuto
        if ($rsSECategoryChildren->num_rows) {
          // me gusta contar hasta los chicles.
          $contCategoryChildren = 0;
          // Mientras mi categoría tenga hijos
          while ($rowSECategoryChildren = $rsSECategoryChildren->fetch_object()) {
            // le sumo uno para que añada los ID de WP_TERMS consecutivos.
            $contCategory++;
            // Genero una clase hijo (gemelo del padre) al vuelo
            $stdCategoryChildren = new stdClass();
            // asigno propiedades del hijo
            // Primero el ID del término que le toca
            $stdCategoryChildren->term_id = $contCategory;
            // Uno igualito para el taxonomy porque así se va agregando
            $stdCategoryChildren->taxonomy_id = $contCategory;
            // categoría de Social Engine
            $stdCategoryChildren->se_category_id = $rowSECategory->se_category_id;
            // Categoría Padre de Social Engine
            $stdCategoryChildren->se_parent_id = $rowSECategory->se_parent_id;
            // Cambio de codificación porque me vale verga.
            $stdCategoryChildren->name = \ForceUTF8\Encoding::toUTF8($rowSECategoryChildren->category_name);
            // Por si esta mierda tiene otro hijo (está capado el pedo hasta aquí; si quieres de nuevo, ya hazlo recursivo con array_walk)
            $stdCategoryChildren->children = array();
            // hago el slug antes del escape para que no meta caracteres escapados como parte del slug.
            $cleanSlug = $stdCategoryChildren->slug = slugifyES($stdCategoryChildren->name);
            // Paso el nombre a la variable para insertar.
            $cleanName = $stdCategoryChildren->name = mysqli_escape_string($db, $stdCategoryChildren->name);
            // Asigno otra posición al arreglo de categorías que se van a registrar en WP

            echo "\t{$contCategoryChildren}\t Creating Category Children Of ({$stdCategory->term_id}) term_id:($stdCategoryChildren->term_id) ".substr("{$stdCategoryChildren->name}", 0,20)." slug:(".substr($stdCategoryChildren->slug, 0, 12)."...)".eol;

            /**
            @TODO: INSERT SQL. YOU MUST INSERT A ROW HERE, CHECK THE CONTEXT FOR CHILDREN CATEGORIES
            */
            $contCategoryChildren++;
            $cont++;
          }
        }
      }
      $contCategory++;
      $cont++;
    }
    echo EOL;
  } else {
    printf(__LINE__." -  -  Errormessage: %s\n", $mysqli->error);
  }
} else {
  printf(__LINE__." -  -  Errormessage: %s\n", $mysqli->error);
};

echo "-- (Father categories ".count($arrWPCategory).") + (init_on 21) +  ".count($arrWPCategory). " =  total: " , (count($arrWPCategory) + 21), eol;
echo "\t\t -- (Children categories ".(21+$contCategoryChildren).") WPTermTaxonomies Added. ".eol;
echo "total: {$cont}".eol;

echo eol.eol.eol.eol.eol. "# # # # TIME TO REAL HACK # # # ".eol.eol.eol;
// Ahora por cada registro de categoría voy a buscar sus artículos.
foreach ($arrWPCategory as $rowWPCategory) {
  echo "\$rowWPCategory->name {$rowWPCategory->name}".eol;
  // Buscando artículos por cada categoría
  $sqlSQArticles = "SELECT Articles.article_id, CONVERT(CONVERT(CONVERT(Articles.title USING latin1) USING binary) USING utf8) AS title, CONCAT('(', Categories.category_name, ')') AS category_name, Articles.photo_id,  CONVERT(CONVERT(CONVERT(Articles.body USING latin1) USING binary) USING utf8) AS article_content
  FROM engine4_article_articles AS Articles INNER JOIN engine4_article_categories AS Categories
  ON (Articles.category_id = Categories.category_id)
  WHERE Articles.category_id =  {$rowWPCategory->se_category_id}
  ";
  // Ejecuto la consulta.
  if ($rsArticlesBySocialEngineCategory = $db->query($sqlSQArticles)) {
    // Si tiene registros ejecuto
    if ($rsArticlesBySocialEngineCategory->num_rows) {
      // me gusta contar hasta los chicles.
      $contPosts = 0;
      // Mientras mi categoría tenga hijos
      while ($rowSocialEnginePost = $rsArticlesBySocialEngineCategory->fetch_object()) {

        // Creo el objeto al vuelo
        $wpPost = new stdclass();
        $wpPost->orig_id = $rowSocialEnginePost->article_id;
        $cleanSlug = $wpPost->slug = slugifyES($rowSocialEnginePost->title);
        $wpPost->title = \ForceUTF8\Encoding::toUTF8($rowSocialEnginePost->title);
        $wpPost->name = \ForceUTF8\Encoding::toUTF8(slugifyES($rowSocialEnginePost->title));
        $wpPost->content = \ForceUTF8\Encoding::toUTF8($rowSocialEnginePost->article_content);

        // información para el debug
        echo "*SE_ID({$rowSocialEnginePost->article_id}) title: ({$rowSocialEnginePost->title}) ".substr($wpPost->name, 0, 10)." slug: {$cleanSlug}".eol;

      }
    }
  }
}
