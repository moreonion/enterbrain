Drupal.behaviors.enterbrain = {
  attach: function (context, settings) {

    var $ibanField = $('[data-enterbrain-field=iban]', context);
    if (!$ibanField.length) {
      return;
    }
    var $ibanFieldWrapper = $ibanField.closest('.form-item');
    var $ibanMessage = $('<div class="enterbrain-iban-message">').appendTo($ibanFieldWrapper);
    var $bicField = $('[data-enterbrain-field=bic]', context);
    var $bicFieldWrapper = $bicField.closest('.form-item');
    var $methodForm = $ibanField.closest('.payment-method-form');
    var $selector = $methodForm.closest('.paymethod-select-wrapper').find('.paymethod-select-radios');
    var valid = false;
    var active = !$selector.length;

    var firstTry = true;
    var querying = false;
    var lastValidatedIban = null;
    var lastValidatedBic = null;

    // Run only in the right context.
    if (!$ibanField.length || !$bicField.length) {
      return;
    }

    function checkActive() {
      active = !$selector.length || $selector.find('.form-radio:checked').val() == $methodForm.data('pmid');
      disableSubmitButton(!valid);
    }
    $selector.change(checkActive);
    checkActive();

    // Hide the bic field only if it’s empty (it might not be due to server side validation!)
    if (!$bicField.val()) {
      $bicFieldWrapper.hide();
    }

    function preValidateIban(iban) {
      // Check for special characters other than spaces and hyphens.
      // The IBAN library is too forgiving here.
      var match = iban.match(/[0-9a-zA-Z -]+/)
      if (match === null || match[0] !== iban) {
        return false;
      }
      // Validate iban.
      if (typeof IBAN !== 'undefined' && !IBAN.isValid(iban)) {
        return false;
      }
      return true;
    }

    function validateIban(iban) {
      if (querying) {
        return;
      }

      // Space and hyphen are the only characters that pass prevalidation.
      var strippedIban = iban.replace(/[ -]/g, '');

      // Don’t validate the same iban twice.
      if (strippedIban === lastValidatedIban) {
        if (lastValidatedBic) {
          $bicField.val(lastValidatedBic);
          showMessage('show-bic');
        } else {
          showMessage('ok');
        }
        return;
      }

      if (preValidateIban(iban)) {
        firstTry = false;
        querying = true;
        // Lock the field while checking.
        $ibanField.prop('disabled', true);
        showMessage('wait');
        $.get('/enterbrain/check-iban/' + strippedIban).done(function (response) {
          if (response.valid) {
            lastValidatedIban = strippedIban;
            if (response.bic) {
              lastValidatedBic = response.bic;
              $bicField.val(response.bic);
              showBicField(false);
              showMessage('show-bic');
            } else {
              lastValidatedBic = null;
              resetBicField();
              showBicField(true);
              $bicField[0].focus();
              showMessage('ok');
            }
          } else {
            resetBicField();
            showMessage('bad');
          }
        }).fail(function (response) {
          resetBicField();
          showBicField(true);
          $bicField[0].focus();
          showMessage('error');
        }).always(function () {
          querying = false;
          // Release the iban field.
          $ibanField.prop('disabled', false);
        });
      } else {
        // Prevalidation failed.
        resetBicField();
        showMessage('bad');
      }
    }

    function resetBicField() {
      if ($bicFieldWrapper.is(':hidden')) {
        $bicField.val('');
      }
    }

    function showBicField(show) {
      if (show) {
        $bicFieldWrapper.slideDown(200);
      } else {
        $bicFieldWrapper.slideUp(200);
      }
    }

    function showMessage(type) {
      if (firstTry) {
        return;
      }
      var message = '';
      var cls = '';
      if (type == 'wait') {
        message = '<span class="enterbrain-icon-spinner"></span>' + Drupal.t('Checking your IBAN...');
        disableSubmitButton(true);
      } else if (type == 'ok') {
        message = '<span class="enterbrain-icon-ok"></span>' + Drupal.t('IBAN checked. Please enter your BIC below.');
        cls = 'field-success';
        disableSubmitButton(false);
      } else if (type == 'show-bic') {
        message = '<span class="enterbrain-icon-ok"></span>' + Drupal.t('BIC: @bic', {'@bic': lastValidatedBic});
        cls = 'field-success';
        disableSubmitButton(false);
      } else if (type == 'bad') {
        message = '<span class="enterbrain-icon-bad"></span>' + Drupal.t('Please enter a valid IBAN.');
        cls = 'field-error';
        disableSubmitButton(true);
      } else if (type == 'error') {
        message = Drupal.t('Unable to check IBAN.');
        disableSubmitButton(false);
      }
      $ibanMessage.html(message);
      $ibanFieldWrapper.removeClass('field-success field-error').addClass(cls);
    }

    function disableSubmitButton(state) {
      valid = !state;
      state = state && active;
      $ibanField.closest('form').find('input.webform-submit').prop('disabled', state);
    }

    $ibanField.on('change', function () {
      firstTry = false;
      validateIban(this.value);
    });

    $ibanField.on('input', function () {
      validateIban(this.value);
    });
  }
};
