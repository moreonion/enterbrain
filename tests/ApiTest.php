<?php

namespace Drupal\enterbrain;

use Drupal\little_helpers\Webform\Submission;
use Drupal\little_helpers\Webform\Webform;

/**
 * Test the enterbrain API client.
 */
class ApiTest extends \DrupalUnitTestCase {

  /**
   * Test generating sonstigeInfo data.
   */
  public function testSonstigeInfo() {
    $submission = $this->getMockBuilder(Submission::class)
      ->disableOriginalConstructor()
      ->getMock();
    $submission->webform = $this->getMockBuilder(Webform::class)
      ->disableOriginalConstructor()
      ->getMock();
    $submission->webform->method('componentsByType')->willReturn([]);
    $submission->tracking = (object) [
      'other' => 'wc_test',
    ];
    $api = $this->getMockBuilder(Api::class)
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    $info = $api->sonstigeInfo($submission);
    $this->assertEqual("WCintern=wc_test, Verwendungszweck: , Newsletter: false, CRM-ID: ", $info);
  }

}
