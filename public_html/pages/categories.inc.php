<?php
  document::$snippets['title'][] = language::translate('categories:head_title', 'Categories');
  document::$snippets['description'] = language::translate('categories:meta_description', '');

  breadcrumbs::add(language::translate('title_categories', 'Categories'));

  $_page = new view();
  echo $_page->stitch('pages/categories');
