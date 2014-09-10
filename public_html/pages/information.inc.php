<?php
// Information links
  ob_start();
  include vmod::check(FS_DIR_HTTP_ROOT . WS_DIR_BOXES . 'box_information_links.inc.php');
  document::$snippets['column_left'] = ob_get_clean();
  
// Information page
  $pages_query = database::query(
    "select p.id, p.status, pi.title, pi.content, pi.head_title, pi.meta_keywords, pi.meta_description from ". DB_TABLE_PAGES ." p
    left join ". DB_TABLE_PAGES_INFO ." pi on (p.id = pi.page_id and pi.language_code = '". language::$selected['code'] ."')
    where p.id = '". (int)$_GET['page_id'] ."'
    limit 1;"
  );
  $page = database::fetch($pages_query);
  
  if (empty($page['status'])) {
    notices::add('errors', language::translate('error_page_not_found', 'The requested page could not be found'));
    header('HTTP/1.1 404 Not Found');
    header('Location: '. document::ilink(''));
    exit;
  }
  
  document::$snippets['title'][] = !empty($page['head_title']) ? $page['head_title'] : $page['title'];
  document::$snippets['keywords'] = !empty($page['meta_keywords']) ? $page['meta_keywords'] : '';
  document::$snippets['description'] = !empty($page['meta_description']) ? $page['meta_description'] : '';
  
  breadcrumbs::add($page['title'], document::ilink(null, array(), true));
  
  $box_information = new view();
  
  $box_information->snippets = array(
    'title' => $page['title'],
    'content' => $page['content'],
  );
  
  echo $box_information->stitch('views/box_information');
?>