<?php

namespace Drupal\enterbrain;

use Drupal\campaignion_opt_in\Values;
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
    $properties['tracking'] = (object) [
      'other' => 'wc_test',
    ];
    $properties['opt_in'] = $this->createMock(Values::class);
    $submission->method('__get')->will(
      $this->returnCallback(function($prop) use ($properties) {
        return $properties[$prop];
      })
    );
    $api = $this->getMockBuilder(Api::class)
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    $defaults = (new \ReflectionClass($api))->getProperty('defaults');
    $defaults->setAccessible(TRUE);
    $defaults->setValue($api, Api::$defaultConfig['defaults']);
    $info = $api->sonstigeInfo($submission);
    $this->assertEqual("WCintern=wc_test, Verwendungszweck: , Newsletter: false, CRM-ID: ", $info);
  }

}
