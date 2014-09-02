<div id="box-also-purchased-products" class="box">
  <div class="heading"><h3><?php echo language::translate('title_also_purchased_products', 'Also Purchased Products'); ?></h3></div>
  <div class="content">
    <?php if ($products) { ?>
    <ul class="listing-wrapper products">
      <?php foreach ($products as $product) echo functions::draw_listing_product($product, 'column'); ?>
    </ul>
    <?php } ?>
  </div>
</div>