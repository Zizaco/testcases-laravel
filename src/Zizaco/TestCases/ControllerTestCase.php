<?php namespace Zizaco\TestCases;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Route, Config, URL, Session;

class ControllerTestCase extends TestCase{

    /**
     * Will contain the parameters of the next request
     *
     * @var array
     */
    protected $requestInput = array();

    /**
     * Will the last HttpException caught
     *
     * @var HttpException
     */
    protected $lastException;

    /**
     * The Synfony's DomCrawler of the last request
     *
     * @var Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;

    /**
     * Set session and enable Laravel filters
     *
     */
    public function setUp()
    {
        parent::setUp();

        // Enable filters
        Route::enableFilters();

        // Set session driver as array
        Config::set('session.driver', 'array');
    }

    /**
     * Request an URL by the action name
     * 
     * @param string $method
     * @param string $action
     * @return ControllerTestCase this for method chaining.
     */
    public function requestAction( $method, $action, $params = array())
    {
        $action_url = URL::action($action, $params);

        if( $action_url == '' )
            trigger_error("Action '$action' does not exist");

        return $this->requestUrl( $method, $action_url, $params );
    }

    /**
     * Request an URL
     * 
     * @param string $method
     * @param string $url
     * @return ControllerTestCase this for method chaining.
     */
    public function requestUrl( $method, $url, $params = array() )
    {
        try
        {
            // The following method returns Synfony's DomCrawler
            $this->crawler =
                $this->client->request( $method, $url, array_merge($params, $this->requestInput) );
        }
        catch(\HttpException $e)
        {
            // Store the HttpException in order to check it later
            $this->lastException = $e;
        }

        return $this; // for method chaining
    }

    /**
     * Set the post parameters and return this for chainable
     * method call
     * 
     * @param array $params Post paratemers array.
     * @return mixed this.
     */
    public function withInput( $params )
    {
        $this->requestInput = $params;

        return $this;
    }

    /**
     * Asserts if the status code is correct
     *
     * @param $code Correct status code
     * @return void
     */
    public function assertStatusCode( $code )
    {
        if($this->lastException)
        {
            $realCode = $this->lastException->getStatusCode();
        }
        else
        {
            $realCode = $this->client->getResponse()->getStatusCode();
        }

        $this->assertEquals( $code, $realCode, "Response was not $code, status code was $realCode" );
    }

    /**
     * Asserts if the request was Ok (200)
     *
     * @return void
     */
    public function assertRequestOk()
    {
        $this->assertStatusCode( 200 );
    }

    /**
     * Asserts if page was redirected correctly
     *
     * @param $location Location where it should be redirected
     * @return void
     */
    public function assertRedirection( $location = null )
    {
        $response = $this->client->getResponse();

        if($this->lastException)
        {
            $statusCode = $this->lastException->getStatusCode();
        }
        elseif( $response )
        {
            $statusCode = $response->getStatusCode();
        }
        else
        {
            $statisCode = null;
        }

        $isRedirection = in_array($statusCode, array(201, 301, 302, 303, 307, 308));

        $this->assertTrue( $isRedirection, "Last request was not a redirection. Status code was ".$statusCode );

        if( $location )
        {
            if(! strpos( $location, '://' ))
                $location = 'http://:'.$location;

            $this->assertEquals( $location, $response->headers->get('Location'), 'Page was not redirected to the correct place' );
        }

    }

    /**
     * Asserts if the session variable is correct
     * 
     * @param string $name  Session variable name.
     * @param mixed $value Session variable value.
     * @return void.
     */
    public function assertSessionHas( $name, $value = null )
    {
        $this->assertTrue( Session::has($name), "Session doens't contain '$name'" );

        if( $value )
        {
            $this->assertContains( $value, Session::get($name), "Session '$name' are not equal to $value" );
        }
    }

    public function getBodyHtml()
    {
        return $this->crawler->html();
    }

    public function assertBodyHasHtml($needle)
    {
        $html = $this->getBodyHtml();

        $needle = (array)$needle;

        foreach ($needle as $singleNiddle) {
            $this->assertContains($singleNiddle, $html, "Body text does not contain '$singleNiddle'");
        }
    }

    public function getBodyText()
    {
        $text = $this->getBodyHtml();
        $text = strip_tags($text); // Strip tags
        $text = str_replace("\n", " ", $text); // Replaces newline with space
        $text = preg_replace('/\s\s+/', ' ', $text);// Trim spaces bewtween words

        return $text;
    }

    public function assertBodyHasText($needle)
    {
        $text = $this->getBodyText();

        $needle = (array)$needle;

        foreach ($needle as $singleNiddle) {
            $this->assertContains($singleNiddle, $text, "Body text does not contain '$singleNiddle'");
        }
    }
}
