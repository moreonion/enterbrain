### Summary

The enterbrain module integrates campaignion with the enterbrain system in the
following ways:

1. It sends successful payments to enterbrain.
2. It uses the enterbrain API to validate IBAN/BIC values and to pre-fill the
   BIC based on the IBAN (for german IBANs).

This projects contains 2 modules dealing with different parts of the
integration:

#### enterbrain

The enterbrain module sends all successful payments to enterbrain. It also
modifies direct debit forms so that the IBAN/BIC is validated using the
enterbrain API.

#### enterbrain_fields

By default the enterbrain module uses a site-wide default for the `project_name`,
`project_id` and `wc` field. Using the field_collection provided by the 
enterbrain_fields module these default can be overridden on a action-by-action
basis. To force an action-by-action configuration these fields may be set to be
mandatory.

### Installation & configuration

1. Enable the enterbrain module
2. Go to «admin - config - services - enterbrain» and set the API-key,
   endpoint and default codes.
3. If you want node specific configuration activate the enterbrain_fields
   sub-module and add the field collection to your donation node types. 


### Technical overview

#### Sending a donation

Whenever a payment via [webform_paymethod_select](https://www.drupal.org/project/webform_paymethod_select)
is successful the module takes note of the payment and queues it for a later API
transfer (see `enterbrain_payment_status_change()`).

The cron-job is invoked in configurable intervals (see [ultimate_cron](https://www.drupal.org/project/ultimate_cron)).
Each time it is invoked it sends donations to enterbrain for a configurable amount of seconds.

The following data is combined for this (see `\Drupal\enterbrain\Api::sendPayment()`):
  - Payment data from the payment object (amount, interval, IBAN, BIC, …)
  - Personal data about the payee — as provided in the accompanying form submission.
  - Metadata about the donation form from the action specific configuration or the site-wide
    defaults if not available.

The API-call `BrainBUND_NeuerFoerderer2` is invoked with the compiled data.

If the API-call returns a negative `returncode` an error is thrown and the transfer
will be retried at a later time.

#### Personal data mapping

On the module configuration page (`/admin/config/services/enterbrain`) the
mapping of form fields to enterbrain fields can be configured. The default
mapping is:

| Enterbrain API field | form keys | remarks |
|----------------------|-----------|---------|
| titel | title |  |
| vorname | first_name |  |
| name | last_name |  |
| strasse | street_name, street_address | By default street name and address are combined into one field in campaignion. The extra fields need to be added to the field palette in the installation profile. This has been done for the amnesty-at profile. |
| hausnr | street_number |  |
| adrzus1 | adrzus1 | No field in the palette, but a simple textfield with this form_key can be added to any form. |
| plz | zip_code, postcode |  |
| ort | city, ort |  |
| email | email |  |
| tel | phone_number |  |
| gebdat | date_of_birth |  |
| anrede_id | salutation | Values like `mr`, `mrs`, … as used by default in campaignion are mapped to numeric ids for the enterbrain API. |

The form keys in the default mapping match the fields in the campaignion field
palette as far as possible.

#### IBAN/BIC validation and prefill

The enterbrain module modifies the the payment form as provided by [manual_direct_debit](https://www.drupal.org/project/manual_direct_debit) in several ways.

On the server-side (see `enterbrain_payment_forms_payment_form_alter()`)
  - It hides the account holder form element.
  - It adds the JavaScripts (`enterbrain.js` and a library for IBAN checksums) 
    to the page.
  
On the client-side (see `enterbrain.js`)
  - It hides the BIC form element by default.
  - Once a valid IBAN is entered (the checksum is correct) it calls the server
    (`/enterbrain/check-iban/%` and `\Drupal\enterbrain\Api::checkIBAN()`) which
    in turn makes a `BrainBank_TestIban` API-call for further validation.
  - If a BIC is returned the BIC field stays hidden, only a text with the BIC is
    displayed. If no BIC is returned the BIC form element is shown.
  - An actual form submit is only allowed if this additional IBAN validation was
    successful.

The callback `/enterbrain/check-iban/%` is rate-limited to 60 calls per hour.

If this extra validation fails for technical reasons  (ie. because the
enterbrain API was not reachable) the form submission is passed directly to the
server side validation.

### Extensibility

Other drupal modules may manipulate the data sent to enterbrain using 
`hook_enterbrain_payment_data_alter()`. The module provides two integrations of
this hook out-of-the box:

- for [manual_direct_debit](https://www.drupal.org/project/manual_direct_debit)
  to add the IBAN and BIC to the data.
- for [payone_payment](https://www.drupal.org/project/payone_payment) to add `Paypal` or `Kreditkarte` as `quelle` and the PayONE
  transaction ID as `transnr`.

