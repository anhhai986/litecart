<main id="content">
  <!--snippet:notices-->

  <?php echo functions::form_draw_form_begin('checkout_form', 'post'); ?>

  <div id="box-checkout">
    <div class="cart wrapper">
      {snippet:box_checkout_cart}
    </div>

    <div class="row">
      <div class="col-md-halfs">
        <div class="customer wrapper">
         {snippet:box_checkout_customer}
        </div>
      </div>

      <div class="col-md-halfs">
        <div class="shipping wrapper">
        </div>

        <div class="payment wrapper">
          {snippet:box_checkout_payment}
        </div>
      </div>
    </div>

    <div class="summary wrapper">
      {snippet:box_checkout_summary}
    </div>
  </div>

  <?php echo functions::form_draw_form_end(); ?>
</main>

<script>
// Queue Handler

  var updateQueue = [
    {component: 'cart',     data: null, refresh: true},
    {component: 'customer', data: null, refresh: true},
    {component: 'shipping', data: null, refresh: true},
    {component: 'payment',  data: null, refresh: true},
    {component: 'summary',  data: null, refresh: true}
  ];

  function queueUpdateTask(component, data=null, refresh=true) {
    updateQueue = jQuery.grep(updateQueue, function(tasks) {
      return (tasks.component == component) ? false : true;
    });

    updateQueue.push({
      component: component,
      data: data,
      refresh: refresh
    });

    runQueue();
  }

  var queueRunLock = false;
  function runQueue() {

    if (queueRunLock) return;

    if (updateQueue.length == 0) return;

    queueRunLock = true;

    task = updateQueue.shift();

    if (console) console.log('Processing ' + task.component);

    if (!$('body > .loader-wrapper').length) {
      var progress_bar = '<div class="loader-wrapper">' +
                         '  <img class="loader" style="width: 256px; height: 256px;" alt="" />' +
                         '</div>';
      $('body').append(progress_bar);
    }

    if (task.refresh) {
      $('#box-checkout .'+ task.component +'.wrapper').fadeTo('fast', 0.15);
    }

    var url = '';
    switch(task.component) {
      case 'cart':
        url = '<?php echo document::ilink('ajax/checkout_cart.html'); ?>';
        break;
      case 'customer':
        url = '<?php echo document::ilink('ajax/checkout_customer.html'); ?>';
        break;
      case 'shipping':
        url = '<?php echo document::ilink('ajax/checkout_shipping.html'); ?>';
        break;
      case 'payment':
        url = '<?php echo document::ilink('ajax/checkout_payment.html'); ?>';
        break;
      case 'summary':
        url = '<?php echo document::ilink('ajax/checkout_summary.html'); ?>';
        break;
      default:
        alert('Error: Invalid component ' + task.component);
        break;
    }

    $.ajax({
      type: 'post',
      url: url,
      data: task.data,
      dataType: 'html',
      beforeSend: function(jqXHR) {
        jqXHR.overrideMimeType('text/html;charset=<?php echo language::$selected['charset']; ?>');
      },
      error: function(jqXHR, textStatus, errorThrown) {
        if (console) console.warn("Error");
        $('#box-checkout .'+ task.component +'.wrapper').html(textStatus + ': ' + errorThrown);
      },
      success: function(html) {
        if (task.refresh) $('#box-checkout .'+ task.component +'.wrapper').html(html).fadeTo('fast', 1);
      },
      complete: function(html) {
        if (!updateQueue.length) {
          $('body > .loader-wrapper').fadeOut('fast', function(){$(this).remove();});
        }
        queueRunLock = false;
        runQueue();
      }
    });
  }

  runQueue();

// Cart

  $('#box-checkout .cart.wrapper').on('click', 'button[name="remove_cart_item"]', function(e){
    e.preventDefault();
    var data = $(this).closest('td').find(':input').serialize() + '&remove_cart_item=' + $(this).val();
    queueUpdateTask('cart', data);
    queueUpdateTask('shipping');
    queueUpdateTask('payment');
    queueUpdateTask('summary');
  });

  $('#box-checkout .cart.wrapper').on('click', 'button[name="update_cart_item"]', function(e){
    e.preventDefault();
    var data = $(this).closest('td').find(':input').serialize() + '&update_cart_item=' + $(this).val();
    queueUpdateTask('cart', data);
    queueUpdateTask('shipping');
    queueUpdateTask('payment');
    queueUpdateTask('summary');
  });

// Customer Form: Toggles

  $('#box-checkout .customer.wrapper').on('change', 'input[name="different_shipping_address"]', function(e){
    if (this.checked == true) {
      $('#shipping-address-container').slideDown('fast');
    } else {
      $('#shipping-address-container').slideUp('fast');
    }
  });

  $('#box-checkout .customer.wrapper').on('change', 'input[name="create_account"]', function(){
    if (this.checked == true) {
      $('#account-container').slideDown('fast');
    } else {
      $('#account-container').slideUp('fast');
    }
  });

// Customer Form: Get Address / Auto Complete

  $('#box-checkout .customer.wrapper').on('change', '.billing-address :input', function() {
    if ($(this).val() == '') return;
    if (console) console.log('Retrieving address (Trigger: "'+ $(this).attr('name') +')');
    $.ajax({
      url: '<?php echo document::ilink('ajax/get_address.json'); ?>?trigger='+$(this).attr('name'),
      type: 'post',
      data: $('.billing-address :input').serialize(),
      cache: false,
      async: false,
      dataType: 'json',
      error: function(jqXHR, textStatus, errorThrown) {
        if (console) console.warn(errorThrown.message + "\n" + jqXHR.responseText);
      },
      success: function(data) {
        if (data['alert']) alert(data['alert']);
        if (console) console.log(data);
        $.each(data, function(key, value) {
          if ($(".billing-address *[name='"+key+"']").length && $(".billing-address *[name='"+key+"']").val() == '') {
            $(".billing-address *[name='"+key+"']").val(value);
          }
        });
      },
    });
  });

// Customer Form: Chained Select

  $('#box-checkout .customer.wrapper').on('change', 'select[name="country_code"]', function() {
    if ($(this).find('option:selected').data('tax-id-format') != '') {
      $(this).closest('table').find("input[name='tax_id']").attr('pattern', $(this).find('option:selected').data('tax-id-format'));
    } else {
      $(this).closest('table').find("input[name='tax_id']").removeAttr('pattern');
    }

    if ($(this).find('option:selected').data('postcode-format') != '') {
      $(this).closest('table').find("input[name='postcode']").attr('pattern', $(this).find('option:selected').data('postcode-format'));
      $(this).closest('table').find("input[name='postcode']").attr('required', 'required');
      $(this).closest('table').find("input[name='postcode']").closest('td').find('.required').show();
    } else {
      $(this).closest('table').find("input[name='postcode']").removeAttr('pattern');
      $(this).closest('table').find("input[name='postcode']").removeAttr('required');
      $(this).closest('table').find("input[name='postcode']").closest('td').find('.required').hide();
    }

    if ($(this).find('option:selected').data('phone-code') != '') {
      $(this).closest('table').find("input[name='phone']").attr('placeholder', '+' + $(this).find('option:selected').data('phone-code'));
    } else {
      $(this).closest('table').find("input[name='phone']").removeAttr('placeholder');
    }

    $('body').css('cursor', 'wait');
    $.ajax({
      url: '<?php echo document::ilink('ajax/zones.json'); ?>?country_code=' + $(this).val(),
      type: 'get',
      cache: true,
      async: true,
      dataType: 'json',
      error: function(jqXHR, textStatus, errorThrown) {
        if (console) console.warn(errorThrown.message);
      },
      success: function(data) {
        $("select[name='zone_code']").html('');
        if (data) {
          $("select[name='zone_code']").removeAttr('disabled');
          $("select[name='zone_code']").closest('td').css('opacity', 1);
          $.each(data, function(i, zone) {
            $("select[name='zone_code']").append('<option value="'+ zone.code +'">'+ zone.name +'</option>');
          });
        } else {
          $("select[name='zone_code']").attr('disabled', 'disabled');
          $("select[name='zone_code']").closest('td').css('opacity', 0.15);
        }
      },
      complete: function() {
        $('body').css('cursor', 'auto');
      }
    });
  });

  $('#box-checkout .customer.wrapper').on('change', 'select[name="shipping_address[country_code]"]', function() {
    if ($(this).find('option:selected').data('postcode-format') != '') {
      $(this).closest('table').find("input[name='shipping_address[postcode]']").attr('pattern', $(this).find('option:selected').data('postcode-format'));
      $(this).closest('table').find("input[name='shipping_address[postcode]']").attr('required', 'required');
      $(this).closest('table').find("input[name='shipping_address[postcode]']").closest('td').find('.required').show();
    } else {
      $(this).closest('table').find("input[name='shipping_address[postcode]']").removeAttr('pattern');
      $(this).closest('table').find("input[name='shipping_address[postcode]']").removeAttr('required');
      $(this).closest('table').find("input[name='shipping_address[postcode]']").closest('td').find('.required').hide();
    }

    console.log('Retrieving zones');
    $('body').css('cursor', 'wait');
    $.ajax({
      url: '<?php echo document::ilink('ajax/zones.json'); ?>?country_code=' + $(this).val(),
      type: 'get',
      cache: true,
      async: false,
      dataType: 'json',
      error: function(jqXHR, textStatus, errorThrown) {
        if (console) console.warn(errorThrown.message);
      },
      success: function(data) {
        $("select[name='shipping_address[zone_code]']").html('');
        if (data) {
          $("select[name='shipping_address[zone_code]']").removeAttr('disabled');
          $("select[name='shipping_address[zone_code]']").closest('td').css('opacity', 1);
          $.each(data, function(i, zone) {
            $("select[name='shipping_address[zone_code]']").append('<option value="'+ zone.code +'">'+ zone.name +'</option>');
          });
        } else {
          $("select[name='shipping_address[zone_code]']").attr('disabled', 'disabled');
          $("select[name='shipping_address[zone_code]']").closest('td').css('opacity', 0.15);
        }
      },
      complete: function() {
        $('body').css('cursor', 'auto');
      }
    });
  });

// Customer Form: Checksum

  window.customer_form_changed = false;
  window.customer_form_checksum = $('#box-checkout-customer :input').serialize();

  $('#box-checkout .customer.wrapper').on('input change', '#box-checkout-customer', function(e) {
    if ($('#box-checkout-customer :input').serialize() != window.customer_form_checksum) {
      window.customer_form_changed = true;
      $('#box-checkout-customer button[name="save_customer_details"]').removeAttr('disabled');
    } else {
      window.customer_form_changed = false;
      $('#box-checkout-customer button[name="save_customer_details"]').attr('disabled', 'disabled');
    }
  });

// Customer Form: Auto-Save

  var timerSubmitCustomer;
  $('#box-checkout .customer.wrapper').on('focusout', '#box-checkout-customer', function() {
    timerSubmitCustomer = setTimeout(
      function() {
        if (!$(this).is(':focus')) {
          if (window.customer_form_changed) {
            if (console) console.log('Autosaving customer details');
            var data = $('#box-checkout-customer :input').serialize();
            queueUpdateTask('customer', data);
            queueUpdateTask('cart');
            queueUpdateTask('shipping');
            queueUpdateTask('payment');
            queueUpdateTask('summary');
          }
        }
      }, 50
    );
  });

  $('body').on('focusin', '#box-checkout-customer', function() {
    clearTimeout(timerSubmitCustomer);
  });

// Customer Form: Process Data

  $('#box-checkout .customer.wrapper').on('click', 'button[name="save_customer_details"]', function(e){
    e.preventDefault();
    var data = $('#box-checkout-customer :input').serialize() + '&save_customer_details=true';
    queueUpdateTask('customer', data);
    queueUpdateTask('cart');
    queueUpdateTask('shipping');
    queueUpdateTask('payment');
    queueUpdateTask('summary');
    customer_form_checksum = $('#box-checkout-customer :input').serialize();
    $('#box-checkout-customer :input:first-child').trigger('change');
  });

// Shipping Form: Process Data

  $('#box-checkout .shipping.wrapper').on('click', '.option:not(.active):not(.disabled)', function(){
    $('#box-checkout-shipping .option').removeClass('active');
    $(this).find('input[name="shipping[option_id]"]').prop('checked', true);
    $(this).addClass('active');
    var data = $('#box-checkout-shipping .option.active :input').serialize();
    queueUpdateTask('shipping', data, false);
    queueUpdateTask('payment');
    queueUpdateTask('summary');
  });

// Payment Form: Process Data

  $('#box-checkout .payment.wrapper').on('click', '.option:not(.active):not(.disabled)', function(){
    $('#box-checkout-payment .option').removeClass('active');
    $(this).find('input[name="payment[option_id]"]').prop('checked', true);
    $(this).addClass('active');
    var data = $('#box-checkout-payment .option.active :input').serialize();
    queueUpdateTask('payment', data, false);
    queueUpdateTask('summary');
  });

// Summary Form: Process Data

  $('#box-checkout-summary-wrapper').on('click', 'button[name="confirm_order"]', function(e) {
    if (window.customer_form_changed) {
      e.preventDefault();
      alert("<?php echo language::translate('warning_your_customer_information_unsaved', 'Your customer information contains unsaved changes.')?>");
    }
  });

  $('body').on('submit', 'form[name="checkout_form"]', function(e) {
    $('#box-checkout-summary button[name="confirm_order"]').css('display', 'none').before('<div class="btn btn-block btn-default btn-lg disabled"><?php echo functions::draw_fonticon('fa-spinner'); ?> <?php echo htmlspecialchars(language::translate('text_please_wait', 'Please wait')); ?>&hellip;</div>');
  });
</script>