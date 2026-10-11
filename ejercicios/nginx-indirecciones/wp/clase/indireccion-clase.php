<?php
// Código de clase, tal cual (el mismo PHP de la clase 10, "igual que con Apache").
// prueba.py lo copia a wp-content/mu-plugins/ para medirlo.
function custom_rewrite_query_vars($vars) {
$vars[] = 'fake_articulo_seo';
return $vars;
}
add_filter('query_vars', 'custom_rewrite_query_vars');
function custom_rewrite_rules($rules) {
$new_rules = array(
'articulo-seo/(.*)?$' => 'index.php?name=$matches[1]&fake_articulo_seo=1'
);
return $new_rules + $rules;
}
add_filter('rewrite_rules_array', 'custom_rewrite_rules');
?>
