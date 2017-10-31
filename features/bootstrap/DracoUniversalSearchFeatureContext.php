<?php
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Mink\Exception\ElementHtmlException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Gherkin\Node\TableNode;
use Drupal\draco_udi\Entity\ContentTitle;
use Drupal\draco_udi\Entity\ContentOnDemandSchedule;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\draco_udi\Entity\ContentOnDemandFlight;
/**
 * Behat steps for testing the Universal Search module.
 *
 * @codingStandardsIgnoreStart
 */
class DracoUniversalSearchFeatureContext extends RawDrupalContext implements SnippetAcceptingContext {
    /**
     * Setup for the test suite, enable some required modules and add content
     * title.
     *
     * @BeforeSuite
     */
    public static function prepare(BeforeSuiteScope $scope) {
        /** @var \Drupal\Core\Extension\ModuleHandler $moduleHandler */
        $moduleHandler = \Drupal::service('module_handler');
        if (!$moduleHandler->moduleExists('draco_universal_search_demo')) {
            \Drupal::service('module_installer')->install(['draco_universal_search_demo', 'draco_universal_search_test']);
        }
        // Also uninstall the inline form errors module for easier testing.
        if ($moduleHandler->moduleExists('inline_form_errors')) {
            \Drupal::service('module_installer')->uninstall(['inline_form_errors']);
        }
        // Clear the Universal Search Config so we start from scratch each time.
        // This is mostly handy when developing these tests locally.
        \Drupal::configFactory()->getEditable('draco_universal_search.settings')->delete();
        // Add a test content title entity.
        $content_title_values = [
            'status' => TRUE,
            'changed' => time(),
            'label' => 'BDD Toy Story',
            'title_id' => 12345,
            'title_type' => 'Feature Film',
            'length_in_seconds' => 7200,
            'release_year' => 2004,
            'external_storyline' => 'Toys come to life with hilarious consequences.',
            'short_storyline' => 'Toys, flight.',
            'storylines' => json_encode([
                (object) [
                    'Type' => 'Baseline',
                    'Language' => 'ENGLISH',
                    'Description' => 'Toys come to life with hilarious consequences.',
                    'TypeId' => 3,
                    'LanguageId' => 13,
                ],
            ]),
            'genres' => json_encode([
                (object) [
                    'Name' => 'Family',
                    'GenreId' => 5,
                ],
                (object) [
                    'Name' => 'Humor',
                    'GenreId' => 6,
                ],
            ]),
            'ratings' => json_encode([
                (object) [
                    'RatingSystem' => 'MPAA',
                    'RatingSystemId' => 2,
                    'RatingDescriptors' => [
                        (object) [
                            'Rating' => 'G',
                            'RatingId' => 8,
                        ],
                    ],
                ],
            ]),
            'participants' => json_encode([
                (object) [
                    'ParticipantId' => 1,
                    'Name' => 'Tim "The Tool-man" Taylor',
                    'ParticipantType' => 'Person',
                    'ParticipantTypeId' => 1,
                    'IsOnScreen' => TRUE,
                    'RoleType' => 'Actor',
                    'RoleTypeId' => 3,
                    'IsKey' => TRUE,
                    'SortOrder' => 5,
                ],
            ]),
        ];
        $content_title = ContentTitle::create($content_title_values);
        $content_title->save();
        // Add a test On Demand Schedule and Flight.
        // Flight.
        $flight_values = [
            'status' => TRUE,
            'imported' => time(),
            'changed' => time(),
            'airing_id' => 'BDD RADS1008301700053392',
            // Start now.
            'start' => DrupalDateTime::createFromTimestamp(time(), new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s'),
            // End tomorrow.
            'end' => DrupalDateTime::createFromTimestamp(time() + 60 * 60 * 24, new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s'),
            'destinations' => json_encode([
                (object) [
                    'name' => 'APPLE',
                    'authenticationRequired' => TRUE,
                ],
            ]),
        ];
        $flight = ContentOnDemandFlight::create($flight_values);
        $flight->save();
        // Schedule.
        $content_json = new \stdClass();
        $content_json->options = new \stdClass();
        $content_json->options->titles = [];
        $json_title = new \stdClass();
        $json_title->titleId = $content_title->getTitleId();
        $content_json->options->titles[] = $json_title;
        $schedule_values = [
            'status' => TRUE,
            'imported' => time(),
            'changed' => time(),
            'airing_id' => 'BDD RADS1008301700053392',
            'label' => 'BDD A Fistful of Meg',
            'type' => 'Episode',
            'length_in_seconds' => $content_title->getLengthInSeconds(),
            'display_minutes' => $content_title->getLengthInSeconds(),
            'brand' => 'TBS',
            'titles' => [$content_title],
            'title_ids' => [$content_title->getTitleId()],
            'flights' => [$flight],
            'content_json' => json_encode($content_json),
        ];
        $schedule = ContentOnDemandSchedule::create($schedule_values);
        $schedule->save();
        return $schedule;
    }
    /**
     * Remove the test data.
     *
     * @AfterSuite
     */
    public static function cleanUp(AfterSuiteScope $scope) {
        // Delete flights.
        $storage = \Drupal::entityTypeManager()->getStorage('content_on_demand_flight');
        $ids = $storage->getQuery()
            ->condition('airing_id', 'BDD', 'STARTS_WITH')
            ->execute();
        $entities = $storage->loadMultiple($ids);
        $storage->delete($entities);
        // Delete content titles and one demand schedules.
        foreach (['content_title', 'content_on_demand_schedule'] as $entity_type) {
            $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
            $ids = $storage->getQuery()
                ->condition('label', 'BDD', 'STARTS_WITH')
                ->execute();
            $entities = $storage->loadMultiple($ids);
            $storage->delete($entities);
        }
    }
    /**
     * @BeforeFeature @set-config
     */
    public static function setConfig(BeforeFeatureScope $scope) {
        $config = \Drupal::configFactory()->getEditable('draco_universal_search.settings');
        $config->setData([
            'title' => 'Title',
            'description' => 'Description',
            'deep_link_protocol' => 'watchtbs',
            'apple_enabled' => TRUE,
            'apple_odt_destination' => 'APPLE',
            'team_id' => 'team_id',
            'service_id' => 'service_id',
            'catalog_id' => 'catalog_id',
            'default_locale' => 'en',
        ])
            ->save();
    }
    /**
     * @Given I open the Draco Universal Search Settings form
     */
    public function iOpenTheDracoUniversalSearchSettingsForm() {
        $this->visitPath('admin/config/draco-universal-search');
    }
    /**
     * Drupal now emits the 'required' attribute, prevent browsers from submitting
     * the form at all when required values are missing.
     *
     * @When I submit the form skipping HTML 5 required validation
     */
    public function iSubmitTheFormSkippingHtml5RequiredValidation() {
        $this->getSession()->evaluateScript("jQuery('form.draco-universal-search-settings').submit();");
    }
    /**
     * @Given I disable HTML 5 required validation on the :field field
     */
    public function iDisableHtmlRequiredValidationOnTheField($field) {
        $id = $this->getSession()->getPage()->findField($field)->getAttribute('id');
        $this->getSession()->evaluateScript("jQuery('#$id').removeAttr('required');");
    }
    /**
     * @Given I disable HTML 5 required validation on the fields:
     */
    public function iDisableHtmlRequiredValidationOnTheFields(TableNode $fields) {
        foreach ($fields->getHash() as $key => $value) {
            $field = trim($value['field']);
            $this->iDisableHtmlRequiredValidationOnTheField($field);
        }
    }
    /**
     * Checks, that form field with specified id|name|label|value is required.
     *
     * @Then /^the "(?P<field>(?:[^"]|\\")*)" field should be required$/
     */
    public function theFieldIsRequired($field) {
        $field = $this->fixStepArgument($field);
        $fieldElement = $this->getSession()->getPage()->findField($field);
        if (!$fieldElement->getAttribute('required')) {
            throw new ElementHtmlException(sprintf("%s is not required", $field), $this->getSession(), $fieldElement);
        }
    }
    /**
     * Checks, that form field with specified id|name|label|value is required.
     *
     * @Then /^the "(?P<field>(?:[^"]|\\")*)" field should not be required$/
     */
    public function theFieldIsNotRequired($field) {
        $field = $this->fixStepArgument($field);
        $fieldElement = $this->getSession()->getPage()->findField($field);
        if ($fieldElement->getAttribute('required')) {
            throw new ElementHtmlException(sprintf("%s is required", $field), $this->getSession(), $fieldElement);
        }
    }
    /**
     * Validate a config setting, useful for password fields.
     *
     * @Then the configuration item :name with key :key should be :value
     */
    public function theConfigSettingIs($name, $key, $value) {
        // We need to clear caches so the static config cache is cleared.
        $this->getDriver()->getCore()->clearCache();
        $actual = $this->getDriver()->configGet($name, $key);
        if ($actual != $value) {
            throw new ExpectationException(sprintf("%s %s is %s", $name, $key, $actual), $this->getSession()->getDriver());
        }
    }
    /**
     * Returns fixed step argument (with \\" replaced back to ")
     *
     * @param string $argument
     *
     * @return string
     */
    protected function fixStepArgument($argument) {
        return str_replace('\\"', '"', $argument);
    }
}