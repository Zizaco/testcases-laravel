# TestCases-Laravel (Laravel4 Package)

[![ProjectStatus](http://stillmaintained.com/Zizaco/testcases-laravel.png)](http://stillmaintained.com/Zizaco/testcases-laravel)

A set of classes that aims to facilitate the preparation and writing tests using **PHPUnit** for applications built with **Laravel 4**.

## Features

**Current:**
- Pretend mail sending to speed up tests
- Simpler and flexible controller and request testing
- Specific assertions for controller testing
- Front-end testing with Selenium **(with selenium integration)**
- Specific assertions for front-end testing
- **Simple to use!**

If you are looking for BDD or Behat see [behat-laravel](https://github.com/GuilhermeGuitte/behat-laravel)

## Quick start

### Required setup

In the `require` key of `composer.json` file add the following

    "zizaco/testcases-laravel": "dev-master"

Run the Composer update comand

    $ composer update

### Testing models and libraries

To write tests for models and other classes, simply extend the `Zizaco\TestCases\TestCase`. For example:

    <?php

    class StuffTest extends Zizaco\TestCases\TestCase
    {
        /**
         * Clean collection between every test
         */
        public function setUp()
        {
            foreach( Stuff::all() as $s ) { $s->delete(); }
            parent::setUp(); // Don't forget this if you overwrite the setUp() method
        }

        /* Your tests here :) */
    }

To test a non-model class:

    <?php

    use Mockery as m;

    class StuffRepositoryTest extends Zizaco\TestCases\TestCase
    {

        public function testShouldCreateNew()
        {
            $repo = new StuffRepository;

            /* Something and some assertion */
        }

        /* Other tests */
    }

### Testing controllers

To write test for controllers, extend the `Zizaco\TestCases\ControllerTestCase`. For example

    <?php

    use Mockery as m;

    class StuffsControllerTest extends Zizaco\TestCases\ControllerTestCase
    {
        /**
         * Create action should always return 200
         *
         */
        public function testShouldCreate(){
            // Make request to action
            $this->requestAction('GET', 'StuffsControllerTest@create');

            // Asserts if the response code is 200
            $this->assertRequestOk();
            // $this->assertStatusCode( 200 ); // Is the same as assertRequestOk()
        }

        /**
         * Store action should redirect to index if success
         *
         */
        public function testShouldStoreValidContent(){

            // Mocks the StuffRepository to make sure that the storeNew
            // method will be called at least once
            $stuffRepo = m::mock(new StuffRepository);
            $stuffRepo->shouldReceive('storeNew')->once()->passthru();
            App::instance("StuffRepository", $stuffRepo);

            $input = array('name'=>'Something nice', 'slug'=>'something_nice');

            // Method chaining ;)
            $this->withInput($input)->requestAction('POST', 'StuffsController@store');

            // Asserts if the page was redirected to the correct place
            $this->assertRedirection(URL::action('StuffsController@index'));

            // Asserts if the session key "flash" has "success" word somewhere
            $this->assertSessionHas('flash','success');
        }

        /* Other tests and stuff */
    }

### Front-end / Integration tests

TestCases-Laravel makes integration tests really easy to write. First of all, download the [Selenium Server (formerly the Selenium RC Server)](http://docs.seleniumhq.org/download/) and place it in the `.selenium` directory in your home. For example: `~/.selenium/selenium-server-standalone-7.7.7.jar`. The `IntegrationTestCase` will automatically starts the server, start the php build in web server and run the tests.

To write an integration test, extend the `Zizaco\TestCases\IntegrationTestCase` class. Its also a good idea to `use Selenium\Locator as l;` on the beginning of the file in order to easly use the locators

    <?php

    use Selenium\Locator as l;

    class ManageContentTest extends Zizaco\TestCases\IntegrationTestCase
    {

        public function testCreateAStuff()
        {
            $this->browser
                ->open(URL::action('StuffController@index'))    // Visits the 'stuff' index
                ->click(l::id('btn-create-new-stuff'))          // Click in the new button
                ->waitForPageToLoad(1000)                       // Wait for the page to load
                ->type(l::IdOrName('name'), 'Something nice')   // Fill name
                ->type(l::IdOrName('slug'), 'something_nice')   // Fill slug
                ->select(l::IdOrName('importance'), 'High')     // Select a value in a selector/combobox
                ->click(l::css('.btn-primary'))                 // Click in the button
                ->waitForPageToLoad(1000);                      // Wait for page to load

            // Asserts if at the end the user is at the stuff index again
            $this->assertLocation( URL::action('StuffController@index') );

            // Asserts if inside the element #stuff-index there is the text "Something nice"
            // what means that the "stuff" has been created sucessfully :)
            $this->assertElementHasText(l::id('stuff-index'), 'Something nice');
        }

        /* Other fancy tests */
    }

## Usage in detail

**Zizaco\TestCases\TestCase:**

1. An alternative to the default TestCase that comes in the `app/tests` directory
2. Simply loads up the application in the `setUp()` method. This permits that your application is ready for each test
3. Useful to test Models, Libraries, Repositories and other classes that are not controllers.

**Zizaco\TestCases\ControllerTestCase:**

1. Build on top of the `Zizaco\TestCases\TestCase`, this class aims to facilitate the writing of controller tests.
2. Thought method chaining, you will be able to write requests in a simple and legible way: `$this->withInput($someArray)->requestUrl($url)->assertRedirection($otherUrl);`
2. Contains a set of custom assertions (`assertStatusCode`, `assertRequestOk`, `assertRedirection`, `assertSessionHas`, `assertBodyHasHtml`, `assertBodyHasText`) that helps you to evaluate if your controllers are working properly.

**Zizaco\TestCases\IntegrationTestCase:**

1. Build on top of the `Zizaco\TestCases\TestCase`, this class aims to facilitate the writing of tests using [Selenium](http://en.wikipedia.org/wiki/Selenium_\(software\)).
2. Checks if the Selenium server is running in the port 4444. If not, tries to find and run a selenium .jar file within the `.selenium` directory in your *home* path.
3. Uses the [alexandresalome/PHP-Selenium](https://github.com/alexandresalome/PHP-Selenium) to interact with selenium. So [these are the commands](https://github.com/alexandresalome/PHP-Selenium/blob/master/src/Selenium/Browser.php) that you will be able to use and [these](https://github.com/alexandresalome/PHP-Selenium/blob/master/src/Selenium/Locator.php) are the possible locator options.

## Troubleshooting

__PHP Fatal error:  Class 'Something' not found in your/app/directory/some/File.php on line 20__

If you overwrite the `setUp()` method in your test, make sure to call `parent::setUp()`:

    public function setUp(){

        parent::setUp() // Don't forget this

        // Your stuff
    }

__Error: Selenium not found. Please run the selenium server (in port 4444) or place the selenium .jar file in the '.selenium'__

Means that the selenium server cannot be found. `Zizaco\TestCases\IntegrationTestCase` will check if the Selenium server is running in the port 4444. If not, tries to find and run a selenium .jar file within the `.selenium` directory in your *home* path.

You can download the [Selenium Server (formerly the Selenium RC Server)](http://docs.seleniumhq.org/download/) and place it in the `.selenium` directory in your *home*, for example: `~/.selenium/selenium-server-standalone-7.7.7.jar` to run the integration tests properly.

## License

TestCases-Laravel is free software distributed under the terms of the MIT license

## Aditional information

Any questions, feel free to contact me or ask [here](http://zizaco.net)

Any issues, please [report here](https://github.com/Zizaco/testcases-laravel/issues)
